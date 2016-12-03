<?php

namespace Bixie\Taxonomy\Model;

use Pagekit\Application as App;
use Pagekit\Database\ORM\ModelTrait;

trait TermModelTrait
{
    use ModelTrait {
        find as modelFind;
    }

    protected static $terms;

    /**
     * Retrieves a term by its item_id.
     *
     * @param  mixed $id
     * @param  bool  $cached
     * @return Term
     */
    public static function find($id, $cached = false)
    {
        if (!$cached || !isset(self::$terms[$id])) {
            self::$terms[$id] = self::modelFind($id);
        }

        return self::$terms[$id];
    }

    /**
     * @param $slug
     * @return Term
     */
    public static function findBySlug ($slug) {
        return self::query()->where(compact('slug'))->first();
    }

    /**
     * Retrieves all terms for item.
     * @param  string $taxonomy
     * @param  int    $item_id
     * @return Term[]
     */
    public static function fromItemId($taxonomy, $item_id)
    {
       return self::query('t.*')->from('@taxonomy_term t')
           ->leftJoin('@taxonomy_term_item ti', 'ti.term_id = t.id')
           ->where([
               't.taxonomy' => $taxonomy,
               'ti.item_id' => $item_id,
           ])
           ->orderBy('t.title')->get();
    }

    /**
     * Sets parent_id of orphaned terms to zero.
     *
     * @return int
     */
    public static function fixOrphanedNodes()
    {
        if ($orphaned = self::getConnection()
            ->createQueryBuilder()
            ->from('@taxonomy_term n')
            ->leftJoin('@taxonomy_term c', 'c.id = n.parent_id AND c.taxonomy = n.taxonomy')
            ->where(['n.parent_id <> 0', 'c.id IS NULL'])
            ->execute('n.id')->fetchAll(\PDO::FETCH_COLUMN)
        ) {
            return self::query()
                ->whereIn('id', $orphaned)
                ->update(['parent_id' => 0]);
        }

        return 0;
    }

    /**
     * @Saving
     */
    public static function saving($event, Term $term)
    {
        $db = self::getConnection();

        $i = 2;
        $id = $term->id;

        if (!$term->slug) {
            $term->slug = $term->title;
        }

        // A node cannot have itself as a parent
        if ($term->parent_id === $term->id) {
            $term->parent_id = 0;
        }

        // Ensure unique slug
        while (self::where(['slug = ?', 'parent_id= ?'], [$term->slug, $term->parent_id])->where(function ($query) use ($id) {
            if ($id) $query->where('id <> ?', [$id]);
        })->first()) {
            $term->slug = preg_replace('/-\d+$/', '', $term->slug).'-'.$i++;
        }

        // Update own path
        $path = '/'.$term->slug;
        if ($term->parent_id && $parent = Term::find($term->parent_id) and $parent->menu == $term->menu) {
            $path = $parent->path.$path;
        } else {
            // set Parent to 0, if old parent is not found
            $term->parent_id = 0;
        }

        // Update children's paths
        if ($id && $path != $term->path) {
            $db->executeUpdate(
                'UPDATE '.self::getMetadata()->getTable()
                .' SET path = REPLACE ('.$db->getDatabasePlatform()->getConcatExpression($db->quote('//'), 'path').", {$db->quote('//' . $term->path)}, {$db->quote($path)})"
                .' WHERE path LIKE '.$db->quote($term->path.'//%'));
        }

        $term->path = $path;

        // Set priority
        if (!$id) {
            $term->priority = 1 + $db->createQueryBuilder()
                    ->select($db->getDatabasePlatform()->getMaxExpression('priority'))
                    ->from('@taxonomy_term')
                    ->where(['parent_id' => $term->parent_id])
                    ->execute()
                    ->fetchColumn();
        }
    }

    /**
     * @Deleting
     */
    public static function deleting($event, Term $term      )
    {
        // Update children's parents
        foreach (self::where('parent_id = ?', [$term->id])->get() as $child) {
            $child->parent_id = $term->parent_id;
            $child->save();
        }
    }
}

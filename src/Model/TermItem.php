<?php

namespace Bixie\Taxonomy\Model;

use Pagekit\Application as App;
use Pagekit\Database\ORM\ModelTrait;

/**
 * @Entity(tableClass="@taxonomy_term_item")
 */
class TermItem implements \JsonSerializable
{
    use ModelTrait;

    /** @Column(type="integer") @Id */
    public $id;

    /** @Column(type="integer") */
    public $item_id;

    /** @Column(type="integer") */
    public $term_id;

    /** @Column(type="integer") */
    public $term_ordering = 0;

    /**
     * Retrieves all terms for a slug.
     * @param  string $taxonomy
     * @param  string $slug
     * @return array
     */
    public static function itemIdsFromSlug($taxonomy, $slug)
    {
        return self::getConnection()
            ->createQueryBuilder()->from('@taxonomy_term_item ti')
            ->leftJoin('@taxonomy_term t', 't.id = ti.term_id')
            ->where([
                't.taxonomy' => $taxonomy,
                't.slug' => $slug,
            ])
            ->orderBy('t.title')
            ->execute('ti.item_id')
            ->fetchAll(\PDO::FETCH_COLUMN);
    }

    //todo term ordering

}

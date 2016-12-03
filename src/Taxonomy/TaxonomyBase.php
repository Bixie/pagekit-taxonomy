<?php


namespace Bixie\Taxonomy\Taxonomy;



use Bixie\Taxonomy\Model\Term;
use Bixie\Taxonomy\Model\TermItem;

class TaxonomyBase {
    /**
     * @var string
     */
    public $name;
    /**
     * Type of relation of the terms: single|hierarchical
     * @var string
     */
    public $type;
    /**
     * @var string
     */
    public $label_single;
    /**
     * @var string
     */
    public $label_plural;
    /**
     * @var string
     */
    public $route;
    /**
     * @var array
     */
    public $options;

    /**
     * TaxonomyBase constructor.
     * @param string $name
     * @param array  $config
     */
    public function __construct ($name, array $config)
    {
        $this->name = $name;
        $this->label_single = $config['label_single'];
        $this->label_plural = $config['label_plural'];

        $this->type = $config['type'];
        $this->route = $config['route'];

        $this->options = $config['options'];

    }

    /**
     * @param string $slug
     * @return static[]
     */
    public function itemIds ($slug)
    {
        return TermItem::itemIdsFromSlug($this->name, $slug);
    }

    /**
     * @param int $item_id
     * @return Term[]
     */
    public function itemTerms ($item_id)
    {
        return Term::fromItemId($this->name, $item_id);
    }

    /**
     * @param int $item_id
     * @param array $terms
     */
    public function saveTerms ($item_id, $terms)
    {
        $term_ids = array_map(function ($term) {
            return $term['id'];
        }, $terms);
        $existing = $this->itemTerms($item_id);
        $remove = array_diff(array_keys($existing), $term_ids);
        //remove deleted
        foreach ($remove as $term_id) {
            if ($xref = TermItem::where([
                'item_id' => $item_id,
                'term_id' => $term_id,])->first()) {
                $xref->delete();
            }
        }
        //save new
        foreach ($terms as $term) {
            if (!isset($existing[$term['id']])) {
                $xref = TermItem::create([
                    'item_id' => $item_id,
                    'term_id' => $term['id'],
                ]);
                $xref->save();
            }
        }
    }

}
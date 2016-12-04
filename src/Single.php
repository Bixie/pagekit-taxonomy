<?php


namespace Bixie\Taxonomy;


use Bixie\Taxonomy\Model\Term;

class Single extends TaxonomyBase {

    /**
     * @param Term $term
     * @return Term[]
     */
    public function getPath (Term $term) {
        return [$term];
    }

    /**
     * @param Term $term
     * @return Term[]
     */
    public function getChildren (Term $term) {
        return [];
    }

    /**
     * @param  array  $parameters
     * @return Term|null
     */
    public function getRoot($parameters = [])
    {
        return null;
    }

}
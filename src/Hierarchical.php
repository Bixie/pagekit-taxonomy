<?php


namespace Bixie\Taxonomy;


use Bixie\Taxonomy\Model\Term;

class Hierarchical extends TaxonomyBase {

    /**
     * @param Term $term
     * @return Term[]
     */
    public function getPath (Term $term) {
        $terms = $this->terms();
        $path = [$term];
        while ($parent_id = $term->parent_id) {
            if (isset($terms[$parent_id]) and $term = $terms[$parent_id]) {
                $path[] = $term;
            }
        }
        return array_reverse($path);
    }

    /**
     * @param Term $term
     * @return Term[]
     */
    public function getChildren (Term $term) {
        return array_filter($this->terms(), function ($t) use ($term) {
            return $t->parent_id == $term->id;
        });
    }

    /**
     * @param  array  $parameters
     * @return Term|null
     */
    public function getRoot($parameters = [])
    {
        $parameters = array_replace([
            'start_level' => 1,
            'depth' => PHP_INT_MAX,
            'mode' => 'all'
        ], $parameters);

        $startLevel = (int) $parameters['start_level'] ?: 1;
        $maxDepth = $startLevel + ($parameters['depth'] ?: PHP_INT_MAX);

        $terms = Term::byTaxonomy($this->name);
        $terms[0] = new Term(['path' => '/']);
        $terms[0]->status = Term::STATUS_PUBLISHED;
        $terms[0]->parent_id = null;

        $rootPath = '/';

        foreach ($terms as $term) {

            $depth = substr_count($term->path, '/');
            $parent = isset($terms[$term->parent_id]) ? $terms[$term->parent_id] : null;

            if ($term->status !== Term::STATUS_PUBLISHED
                || $depth >= $maxDepth
                || !($parameters['mode'] == 'all'
                    || 0 === strpos($term->path.'/', $rootPath)
                    || $depth == $startLevel)
            ) {
                $term->setParent();
                continue;
            }

            $term->setParent($parent);

            if ($depth == $startLevel - 1) {
                $root = $term;
            }

        }

        if (!isset($root)) {
            return null;
        }

        $root->setParent();

        return $root;
    }


}
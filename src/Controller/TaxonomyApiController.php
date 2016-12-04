<?php

namespace Bixie\Taxonomy\Controller;

use Bixie\Taxonomy\Model\Term;
use Pagekit\Application as App;

/**
 * @Access("site: manage site")
 */
class TaxonomyApiController {

    /**
     * @Route("/", methods="GET")
     * @Request({"taxonomyName": "string", "filter": "array", "page":"int", "limit":"int"})
     */
    public function indexAction($taxonomyName, $filter = [], $page = 0, $limit = 0)
    {
        if (!$taxonomy = App::taxonomy($taxonomyName)) {
            return App::abort(400, 'Taxonomy not found');
        }

        $query  = Term::query()->where(['taxonomy' => $taxonomy->name]);
        $filter = array_merge(array_fill_keys(['status', 'search', 'role', 'order', 'access'], ''), $filter);
        extract($filter, EXTR_SKIP);

        if (is_numeric($status)) {
            $query->where(['status' => (int) $status]);
        }

        if ($search) {
            $query->where(function ($query) use ($search) {
                $query->orWhere(['title LIKE :search', 'slug LIKE :search'], ['search' => "%{$search}%"]);
            });
        }

        if (preg_match('/^(title|slug|priority)\s(asc|desc)$/i', $order, $match)) {
            $order = $match;
        } else {
            $order = [1=>'priority', 2=>'asc'];
        }

        $default = 20;
        $limit   = min(max(0, $limit), $default) ?: $default;
        $count   = $query->count();
        $pages   = ceil($count / $limit);
        $page    = max(0, min($pages - 1, $page));
        $terms   = array_values($query->offset($page * $limit)->limit($limit)->orderBy($order[1], $order[2])->get());
        $statuses = Term::getStatuses();

        return compact('taxonomy', 'terms', 'pages', 'count', 'statuses');

    }

    /**
     * @Route("/", methods="POST")
     * @Route("/{id}", methods="POST", requirements={"id"="\d+"})
     * @Request({"term": "array", "id": "int"}, csrf=true)
     */
    public function saveAction($data, $id = 0)
    {
        if (!$term = Term::find($id)) {
            $term = Term::create();
            unset($data['id']);
        }

        if (!$data['slug'] = App::filter($data['slug'] ?: $data['title'], 'slugify')) {
            App::abort(400, __('Invalid slug.'));
        }

        $term->save($data);

        return ['message' => 'success', 'term' => $term];
    }

    /**
     * @Route("/updateOrder", methods="POST")
     * @Request({"taxonomyName", "terms": "array"}, csrf=true)
     */
    public function updateOrderAction($taxonomyName, $terms_data = [])
    {
        $terms = App::taxonomy($taxonomyName)->terms($taxonomyName);
        foreach ($terms_data as $data) {

            if ($term = $terms[$data['id']]) {

                $term->save([
                    'priority' => $data['order'],
                    'parent_id' => $data['parent_id'] ?: 0,
                ]);
            }
        }

        return ['message' => 'success'];
    }
    /**
     * @Route("/{id}", methods="DELETE", requirements={"id"="\d+"})
     * @Request({"id": "int"}, csrf=true)
     */
    public function deleteAction($id)
    {

        if ($term = Term::find($id)) {
            $term->delete();
        }

        return ['message' => 'success'];
    }

    /**
     * @Route("/bulk", methods="POST")
     * @Request({"terms": "array"}, csrf=true)
     */
    public function bulkSaveAction($terms = [])
    {
        foreach ($terms as $data) {
            $this->saveAction($data, isset($data['id']) ? $data['id'] : 0);
        }

        return ['message' => 'success'];
    }

    /**
     * @Route("/bulk", methods="DELETE")
     * @Request({"ids": "array"}, csrf=true)
     */
    public function bulkDeleteAction($ids = [])
    {
        foreach (array_filter($ids) as $id) {
            $this->deleteAction($id);
        }

        return ['message' => 'success'];
    }

    /**
     * @Route("/item", methods="GET")
     * @Request({"taxonomyName": "string", "item_id": "int"})
     */
    public function itemAction($taxonomyName, $item_id)
    {
        if (!$taxonomy = App::taxonomy($taxonomyName)) {
            return App::abort(400, 'Taxonomy not found');
        }

        $terms = array_values(App::taxonomy($taxonomyName)->itemTerms($item_id));
        $statuses = Term::getStatuses();

        return compact('taxonomy', 'terms', 'statuses');
    }

    /**
     * @Route("/item", methods="POST")
     * @Request({"taxonomyName": "string", "item_id": "int", "terms": "array"}, csrf=true)
     */
    public function saveItemAction($taxonomyName, $item_id, $terms)
    {
        if (!$taxonomy = App::taxonomy($taxonomyName)) {
            return App::abort(400, 'Taxonomy not found');
        }

        try {

            App::taxonomy($taxonomyName)->saveTerms($item_id, $terms);

        } catch (App\Exception $e) {
            return App::abort(500, $e->getMessage());
        }

        $terms = App::taxonomy($taxonomyName)->itemTerms($item_id);

        return ['message' => 'success', 'terms' => array_values($terms)];
    }


}

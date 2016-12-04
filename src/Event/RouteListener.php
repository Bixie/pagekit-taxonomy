<?php

namespace Bixie\Taxonomy\Event;

use Pagekit\Application as App;
use Pagekit\Event\EventSubscriberInterface;
use Pagekit\Site\Model\Node;

class RouteListener implements EventSubscriberInterface
{
    /**
     * Registers category routes
     */
    public function onRequest()
    {

        foreach (App::taxonomy()->all() as $taxonomy) {
            //mount term routes
            if (($taxonomy->type == 'hierarchical' && $taxonomy->route)
                and ($taxonomy->options['term_controller'] && $taxonomy->options['item_controller'])
                and $node = Node::query()->where(['link' => $taxonomy->route])->first()) {

                foreach ($taxonomy->terms() as $term) {

                    $route = [
                        'label' => $term->title,
                        'defaults' => [
                            '_node' => $node->id,
                            'term_id' => $term->id,
                            'term_slug' => $term->slug,
                            'taxonomy' => $taxonomy->name,
                        ],
                        'path' => $node->path . $term->path,
                    ];
                    //category view
                    App::routes()->add(array_merge([
                        'name' => $term->link,
                        'controller' => $taxonomy->options['term_controller']
                    ], $route));
                    //detail page views
                    App::routes()->add(array_merge([
                        'name' => $taxonomy->route . $term->path . '/item',
                        'controller' => $taxonomy->options['item_controller']
                    ], $route));

                }

            }
        }

    }

    /**
     * Registers terms items route alias.
     */
    public function onConfigureRoute($event, $route)
    {
        $name = $route->getName();

        if ($taxonomyName = $route->getDefault('taxonomy') and substr($name, -4) == 'item') {

            if ($taxonomy = App::taxonomy($taxonomyName)
                and ($taxonomy->type == 'hierarchical' && $taxonomy->route)
                and (count($taxonomy->options['item_resolver']))) {

                //create alias for items with term
                App::routes()->alias(
                    $route->getPath() . $taxonomy->options['item_resolver']['pattern'],
                    $name,
                    ['_resolver' => $taxonomy->options['item_resolver']['resolver']]
                );

            }
        }

    }

    /**
     * {@inheritdoc}
     */
    public function subscribe()
    {
        return [
            'request' => ['onRequest', 120],
            'route.configure' => 'onConfigureRoute',
        ];
    }
}

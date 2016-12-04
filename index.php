<?php

return [

	'name' => 'taxonomy',

	'type' => 'extension',

	'main' => function ($app) {

        $app['taxonomy'] = function ($app) {
            return new Bixie\Taxonomy\TaxonomyManager($app);
        };

    },

	'autoload' => [

		'Bixie\\Taxonomy\\' => 'src'

	],
	'routes' => [

		'/api/taxonomy' => [
			'name' => '@taxonomy/api',
			'controller' => [
				'Bixie\\Taxonomy\\Controller\\TaxonomyApiController'
			]
		]

	],

	'resources' => [

		'taxonomy:' => ''

	],

	'config' => [
	],

	'events' => [
        'boot' => function ($event, $app) {
            $app->subscribe(
                new \Bixie\Taxonomy\Event\RouteListener()
            );
        },

        'view.scripts' => function ($event, $scripts) use ($app) {
            $scripts->register('taxonomy', 'taxonomy:app/bundle/taxonomy.js', ['vue', 'editor', 'uikit-nestable']);
        },
	]

];

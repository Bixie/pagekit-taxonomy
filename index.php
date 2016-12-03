<?php

return [

	'name' => 'taxonomy',

	'type' => 'extension',

	'main' => 'Bixie\\Taxonomy\\TaxonomyModule',

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
        'view.scripts' => function ($event, $scripts) use ($app) {
            $scripts->register('taxonomy', 'taxonomy:app/bundle/taxonomy.js', ['vue']);
        },
	]

];

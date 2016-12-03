<?php

namespace Bixie\Taxonomy;

use Pagekit\Application as App;
use Pagekit\Module\Module;
use Bixie\Taxonomy\Taxonomy\TaxonomyManager;

class TaxonomyModule extends Module {

	/**
	 * {@inheritdoc}
	 */
	public function main (App $app) {

		$app['taxonomy'] = function ($app) {
		    return new TaxonomyManager($app);
		};

	}


}

<?php


namespace Bixie\Taxonomy\Taxonomy;

use Pagekit\Application;
use Pagekit\Application\Exception;

class TaxonomyManager {

    /**
     * @var Application
     */
    protected $app;

    /**
     * @var TaxonomyBase[]
     */
    protected $taxonomies = [];

    protected $types = [
        'single' => 'Bixie\\Taxonomy\\Taxonomy\\Single',
        'hierarchical' => 'Bixie\\Taxonomy\\Taxonomy\\Hierarchical',
    ];

    /**
     * Constructor.
     *
     * @param Application $app
     */
    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    /**
     * Get shortcut.
     *
     * @see get()
     * @param string $name
     * @return TaxonomyBase|null
     */
    public function __invoke($name)
    {
        return $this->get($name);
    }

    /**
     * Get a registered taxonomy
     * @param string $name
     * @return TaxonomyBase|null
     */
    public function get ($name)
    {
        return isset($this->taxonomies[$name]) ? $this->taxonomies[$name] : null;
    }

    /**
     * Register a taxonomy
     * @param string $name
     * @param array  $config
     * @return $this
     */
    public function register ($name, array $config)
    {

        if (!isset($config['type']) || !isset($this->types[$config['type']])) {
            throw new Exception('Taxonomy type not found');
        }

        $this->taxonomies[$name] = new $this->types[$config['type']]($name, array_merge([
            'type' => 'single',
            'label_single' => __('Tag'),
            'label_plural' => __('Tags'),
            'route' => '',
            'options' => [
                'term_type' => 'term-raw',
            ],
        ], $config));

        return $this;
    }

}
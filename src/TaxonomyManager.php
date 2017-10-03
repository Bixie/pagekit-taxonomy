<?php


namespace Bixie\Taxonomy;

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

    /**
     * @var array
     */
    protected $types = [
        'single' => 'Bixie\\Taxonomy\\Single',
        'hierarchical' => 'Bixie\\Taxonomy\\Hierarchical',
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
            'label_single' => 'Tag',
            'label_plural' => 'Tags',
            'link' => [],
            'route' => '',
            'options' => [
                'term_type' => 'term-raw',
                'term_controller' => '',
                'item_controller' => '',
                'item_resolver' => [],
            ],
        ], $config));

        return $this;
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
     * Get all taxonomies
     * @return TaxonomyBase[]
     */
    public function all ()
    {
        return $this->taxonomies;
    }

}
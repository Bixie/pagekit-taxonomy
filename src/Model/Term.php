<?php

namespace Bixie\Taxonomy\Model;

use Bixie\Taxonomy\Taxonomy\TaxonomyBase;
use Pagekit\Application as App;
use Pagekit\System\Model\DataModelTrait;
use Pagekit\System\Model\NodeInterface;
use Pagekit\System\Model\NodeTrait;

/**
 * @Entity(tableClass="@taxonomy_term")
 */
class Term implements NodeInterface, \JsonSerializable
{
    use DataModelTrait, TermModelTrait, NodeTrait;

    /** Term published. */
    const STATUS_PUBLISHED = 1;

    /** Term unpublished. */
    const STATUS_UNPUBLISHED = 0;

    /** @Column(type="integer") @Id */
    public $id;

    /** @Column(type="integer") */
    public $parent_id = 0;

    /** @Column(type="integer") */
    public $priority = 0;

    /** @Column(type="integer") */
    public $status = 0;

    /** @Column(type="string") */
    public $title;

    /** @Column(type="string") */
    public $slug;

    /** @Column(type="string") */
    public $path;

    /** @Column(type="string") */
    public $link;

    /** @Column(type="string") */
    public $type;

    /** @Column(type="string") */
    public $taxonomy;
    /**
     * @var TaxonomyBase
     */
    protected $_taxonomy;

    /** @var array */
    protected static $properties = [
        'url' => 'getUrl',
    ];

    /**
     * @return TaxonomyBase
     */
    public function getTaxonomy () {
        if (!isset($this->_taxonomy)) {
            $this->_taxonomy = App::taxonomy($this->taxonomy);
        }
        return $this->_taxonomy;
    }

    /**
     * @return array
     */
    public static function getStatuses () {
        return [
            self::STATUS_PUBLISHED => __('Published'),
            self::STATUS_UNPUBLISHED => __('Unpublished')
        ];
    }

    /**
     * @return string
     */
    public function getStatusText () {
        $statuses = self::getStatuses();
        return isset($statuses[$this->status]) ? $statuses[$this->status] : __('Unknown');
    }

    /**
     * Gets the node URL.
     *
     * @param  mixed  $referenceType
     * @return string
     */
    public function getUrl($referenceType = false)
    {
        return App::url($this->getTaxonomy()->route, ['slug' => $this->slug], $referenceType);
    }

    /**
     * {@inheritdoc}
     */
    public function jsonSerialize()
    {
        return $this->toArray(['url' => $this->getUrl('base')]);
    }
}

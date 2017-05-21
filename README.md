# Taxonomy

Taxonomy for Pagekit. This code is an alpha version. It is the intention to move this module to the Pagekit namespace when finished.
Developers are encouraged to test it with their extensions. Feedback is appreciated.

#### Todo

- Set default term for canonical link
- Set canonical link for items with multiple terms
- optimize loading and caching in the models
- ordering of terms and of items within terms

## Registering a taxonomy

By default a simple tag-taxonomy is added. Only define the route where your taxonomy overview page will live. 
For configuring the route see [routing](#routing).

```php
$app->on('boot', function ($event, $app) {
    $app['taxonomy']->register('extension.item.tag', [
        'route' => '@extenstion/item/tag',
    ]);
});
```

You can define more complex taxonomies:

Attributes;

```php
$app['taxonomy']->register('extension.product.color', [
    'type' => 'single',
    'label_single' => __('Color'),
    'label_plural' => __('Colors'),
    'route' => '@extension/product/color',
    'options' => [
        'term_type' => 'term-raw',
    ],
]);
```

For hierarchical terms, things get a bit more complicated. More route-info and code from your extension is needed to create 
the correct routes for the terms.

```php
$app['taxonomy']->register('extension.product.category', [
    'type' => 'hierarchical',
    'label_single' => __('Category'),
    'label_plural' => __('Categories'),
    'route' => '@extension/item/category',
    'options' => [
        'term_type' => 'term-content',
        'term_controller' => '\Vendor\Extension\Controller\ItemSiteController::categoryAction',
        'item_controller' => '\Vendor\Extension\Controller\ItemSiteController::itemAction',
        'item_resolver' => [
            'pattern' => '/{slug}',
            'resolver' => '\Vendor\Extension\ItemUrlResolver',
        ],
    ],
]);
```

See [routing](#routing) for more details.

### Single Taxonomy

One level of terms will be available. Used for properties, tags, etc.

### Hierarchical Taxonomy

Terms can be nested into levels. Used for categorizing.

### Term type-raw/type-content

A raw term only contains a label and slug. The content type can also have an image and content. The parsed markdown 
content is available in the json-property `content` or via `$term->getContent()`.

In the future it should be possible to register your own term types.

## Taxonomy javascript interface

When using the Vue app, make sure to include the taxonomy javascript in your view:

```php
$view->script('taxonomy');
```

or simply add it to your dependancies;

```php
$view->script('item-edit', 'vendor/extension:app/bundle/item-edit.js', ['vue', 'taxonomy']); ?>
```

### Manage terms

There is a Vue app that you can simply call in any view of your extension.

For single taxonomies;

```html
<div is="terms-single" taxonomy-name="extension.product.color"></div>
```

For hierarchical taxonomies;

```html
<div is="terms-hierarchical" taxonomy-name="extension.product.category"></div>
```

### Attach terms to your items

Use the input-components to select the term(s) for your items. Just pass the items id and the taxonomy name.

For one term per item;

```html
<input-terms-one taxonomy-name="extension.product.color" :item_id="item.id"></input-terms-one>
```

For many terms per item;

```html
<input-terms-many taxonomy-name="extension.item.tag" :item_id="item.id"></input-terms-many>
```

## API

The taxonomy module provides an API to manage your taxonomies and items.

### Get taxonomy

Get the registered Taxonomy object from the manager.

```php
$taxonomy = App::taxonomy('extension.item.tag');
```
or
```php
$taxonomy = $app['taxonomy']('extension.item.tag');
```

### Save terms to item

Use the [Vue components](#attach-terms-to-your-items) to manage your terms or do it yourself via the API:

```php
/**
 * @param int $item_id
 * @param array $terms
 */
$taxonomy = App::taxonomy('extension.item.tag')->saveTerms($item_id, $terms);
```

Where `$terms` is an array of associative array of term data, at least including the id.

### Get all taxonomies 

To retrieve all registered taxonomies:

```php
/**
 * @return TaxonomyBase[]
 */
$taxonomy = App::taxonomy()->all();
```

### Get all terms 

To retrieve all published terms of a taxonomy:

```php
/**
 * @return Term[]
 */
$taxonomy = App::taxonomy('extension.item.tag')->terms();
```

### Get terms root

Retrieves the root node of the taxonomy terms. This node can be iterated to create a term tree. Only available in hierarchical taxonomies.

```php
/**
 * @param  array  $parameters ['start_level' => 1, 'depth' => PHP_INT_MAX, 'mode' => 'all']
 * @return Term|null
 */
$root_term = App::taxonomy('extension.product.category')->getRoot($parameters);
```

For more details on usage of the root, see the menu documentation.

### Get terms of an item

To retrieve the published terms attached to an item:

```php
/**
 * @param int $item_id
 * @return Term[]
 */
$taxonomy = App::taxonomy('extension.item.tag')->itemTerms($item_id);
```

### Get item ids of a term

To retrieve the item ids attached to a term, you can get them from the slug of the term:

```php
/**
 * @param string $slug
 * @return array of item ids
 */
$taxonomy = App::taxonomy('extension.item.tag')->itemIds($slug);
```

### Get a term

Use the Term model directly to retrieve a term by slug or id:

```php
/**
 * @param string $slug
 * @return Term
 */
$term = App::taxonomy('extension.item.tag')->termBySlug($slug);
/**
 * @param int $id
 * @return Term
 */
$term = App::taxonomy('extension.item.tag')->termById($slug);
```

### Get path of a term

To retrieve the path of ancestors including the term itself from a hierarchical typed term;

```php
/**
 * @param  $term
 * @return Term[]
 */
$taxonomy = App::taxonomy('extension.item.tag')->getPath($term);
```

### Get children of a term

To retrieve the direct children from a hierarchical typed term;

```php
/**
 * @param Term $term
 * @return Term[]
 */
$taxonomy = App::taxonomy('extension.item.tag')->getChildren($term);
```

## Routing

Routing can be done directly from your extensions controllers.

### Attach a route for a single term

To route the view for the single term (eg tag) to link to, register the route `/tag/{slug}` in the controller whos route-name 
was registered in the taxonomy.
The slug of the term is appended to the functions arguments.

```php
/**
 * @Route("/tag/{slug}")
 * @return array
 */
public function tagAction ($slug) {

    if (!$taxonomy = App::taxonomy('game2art.tag.game2art_comic')) {
        return App::abort(400, 'Taxonomy not found');
    }

    $term = $taxonomy->termBySlug($slug);
    $item_ids = $taxonomy->itemIds($slug);

    $items = Item::query()->whereInSet('id', $item_ids)->orderBy('title')->get();

    return [
        '$view' => [
            'title' => __('Items with tag %tag%', ['%tag%' => $term->title]),
            'name' => 'vendor/extension/items_tag.php'
        ],
        'items' => array_values($items),
        'tag' => $term,
    ];
}
```

### Attach routes for a hierarchical term

For reference, the example of a hierarchical taxonomy:

```php
$app['taxonomy']->register('extension.product.category', [
    'type' => 'hierarchical',
    'label_single' => __('Category'),
    'label_plural' => __('Categories'),
    'route' => '@extension/item',
    'options' => [
        'term_type' => 'term-content',
        'term_controller' => '\Vendor\Extension\Controller\ItemSiteController::categoryAction',
        'item_controller' => '\Vendor\Extension\Controller\ItemSiteController::itemAction',
        'item_resolver' => [
            'pattern' => '/{slug}',
            'resolver' => '\Vendor\Extension\ItemUrlResolver',
        ],
    ],
]);
```

#### Term route

Taxonomy will create a separate route for every term in the hierarchical taxonomy. They are appended to the route defined 
in the taxonomy and ending in `/term`. In the example code that could become: `@extension/item/category/cat-2/term`, 
`@extension/item/category/cat-1/cat-2/term` etc. The link is stored in the `link` property of the `Term` object.
The term link is mounted to the `term_controller` provided in the opions.

In the controller the term id is appended to the arguments of the function.

```php
/**
 * @Request({"filter": "array", "page":"int"})
 * @return array
 */
public function categoryAction ($filter = [], $page = null, $term_id = 0) {

    if (!$taxonomy = App::taxonomy('extension.product.category')) {
        return App::abort(400, 'Taxonomy not found');
    }

    $term = $taxonomy->termById($term_id);
    $item_ids = $taxonomy->itemIds($term->slug);
    
    //apply filtering and pagination
    
    $items = Item::query()->whereInSet('id', $item_ids)->orderBy('title')->get();
    
    return [
        '$view' => [
            'title' => __('Items in category %category%', ['%category%' => $term->title]),
            'name' => 'vendor/extension/items_category.php'
        ],
        'items' => array_values($items),
        'subcategories' => $taxonomy->getChildren($term),
        'category' => $term,
    ];
}
```

#### Term item route

A route for the items that belong to the terms is mounted to the `item_controller` provided in the opions. In the example code 
that could become: `@extension/item/category/cat-2/item`, `@extension/item/category/cat-1/cat-2/item` etc. You can use 
these links to generate urls for your items.

When an url resolver is provided, an url alias will be registered for all item-routes. This way you can generate sef links 
to your items. In the example the pattern is appended to the link and attached to the resolver.

`@extension/item/category/cat-2/item/{slug}` => `\Vendor\Extension\ItemUrlResolver`

In the controller the term id is appended to the arguments of the function, your url resolver should convert the slug to an id.

```php
/**
 * @Route("/{id}", name="/id")
 */
public function itemAction ($id = 0, $term_id = 0) {

    //access checks etc
    $item = Item::find($id);

    if ($term_id) {
        $terms = App::taxonomy('extension.product.category')->itemTerms($id);
        if (!isset($terms[$term_id])) {
            App::abort(404, __('Item not found in category.'));
        }
    }
    
    return [
        '$view' => [
            'title' => __('Item details'),
            'name' => 'vendor/extension/item.php'
        ],
        'item' => $item,
        'category' => $terms[$term_id],
    ];
}
```



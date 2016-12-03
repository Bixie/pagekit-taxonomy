# Taxonomy

Taxonomy for Pagekit

### Register a taxonomy

By default a simpe tag-taxonomy is added. Only define the route where your taxonomy overview page will live.

```php
$app['taxonomy']->register('extension.item.tag', [
    'route' => '@extenstion/item/tag',
]);
```

You can define more complex taxonomies:

Categories;

```php
$app['taxonomy']->register('extension.product.category', [
    'type' => 'hierarchical',
    'label_single' => __('Category'),
    'label_plural' => __('Categories'),
    'route' => '@extension/product/category',
    'options' => [
        'term_type' => 'term-content',
    ],
]);
```

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
<input-terms-one :taxonomy-name="extension.product.color" :item_id="item.id"></input-terms-many>
```

For many terms per item;

```html
<input-terms-many :taxonomy-name="extension.item.tag" :item_id="item.id"></input-terms-many>
```

### API

The taxonomy module provides an API to manage your taxonomies and items.

#### Get taxonomy

Get the registered Taxonomy object from the manager.

```php
$taxonomy = App::taxonomy('extension.item.tag');
```
or
```php
$taxonomy = $app['taxonomy']('extension.item.tag');
```

#### Save terms to item

Use the [Vue components](#attach-terms-to-your-items) to manage your terms or do it yourself via the API:

```php
$taxonomy = App::taxonomy('extension.item.tag')->saveTerms($item_id, $terms);
```

Where `$terms` is an array of associative array of term data, at least including the id.

#### Get terms of an item

To retrieve the terms attached to an item:

```php
$taxonomy = App::taxonomy('extension.item.tag')->itemTerms($item_id);
```

#### Get item ids of a term

To retrieve the item ids attached to a term, you can get them from the slug of the term:

```php
$taxonomy = App::taxonomy('extension.item.tag')->itemIds($slug);
```

#### Get a term

Use the Term model directly to retrieve a term by id or slug:

```php
$term = Term::findBySlug($slug);
$term = Term::find($id);
```

Remember to include the correct use statement.

### Attach a view for a term

To route the view for the tag to link to, just register the route via your controller.

```php
/**
 * @Route("/tag/{slug}")
 * @return array
 */
public function tagAction ($slug) {

    $term = Term::findBySlug($slug);

    $item_ids = App::taxonomy('extension.item.tag')->itemIds($slug);

    $items = Item::query()->whereInSet('id', $item_ids)->orderBy('title')->get();

    return [
        '$view' => [
            'title' => __('Items with tag %tag%', ['%tag%' => $term->title]),
            'name' => 'vendor/extension/items_tag.php'
        ],
        'items' => array_values($items),
        'term' => $term,
    ];
}
```


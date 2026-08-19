<?php
require_once __DIR__ . '/../../includes/api_auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    apiError('Method not allowed.', 405);
}

$categories = getPDO()->query('SELECT id, name, slug FROM categories ORDER BY name')->fetchAll();

$businessCategories = array_map(
    fn($slug, $label) => ['slug' => $slug, 'label' => $label],
    array_keys(BUSINESS_CATEGORIES),
    BUSINESS_CATEGORIES
);

apiRespond([
    'success' => true,
    'live_categories' => array_map(fn($c) => ['id' => (int) $c['id'], 'name' => $c['name'], 'slug' => $c['slug']], $categories),
    'business_categories' => $businessCategories,
]);

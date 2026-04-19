<?php
// If uninstall is not called from WordPress, exit
if (!defined('WP_UNINSTALL_PLUGIN')) {
    die;
}

// Cleanup all post meta for '_disabled_scripts' and '_disabled_styles'.
// Meta keys to delete.
$meta_keys = ['_disabled_scripts', '_disabled_styles'];

// Get all posts (all types).
$args = [
    'post_type'   => 'any',
    'post_status' => 'any',
    'fields'      => 'ids',
    'numberposts' => -1,
];
$posts = get_posts($args);

// Delete metadata.
foreach ($posts as $post_id) {
    foreach ($meta_keys as $meta_key) {
        delete_post_meta($post_id, $meta_key);
    }
}

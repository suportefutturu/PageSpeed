<?php
namespace ScriptStyleManager\Dequeue;

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

// Dequeue assets based on transient data.
add_action( 'wp_enqueue_scripts', function () {
    if ( is_admin() ) {
        return;
    }

    if (is_singular()) {
        $page_id = get_queried_object_id();
        // Get disabled scripts and styles for this page
        $disabled_scripts = get_post_meta($page_id, '_disabled_scripts', true) ?: [];
        $disabled_styles  = get_post_meta($page_id, '_disabled_styles', true) ?: [];
        // Dequeue scripts
        foreach ($disabled_scripts as $handle) {
            wp_dequeue_script($handle);
        }

        // Dequeue styles
        foreach ($disabled_styles as $handle) {
            wp_dequeue_style($handle);
        }
    }
}, 100 );

<?php
namespace ScriptStyleManager\Admin;

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

// Enqueue admin assets.
add_action( 'admin_enqueue_scripts', function () {
    wp_enqueue_script(
        'scripts-and-styles-manager',
        plugin_dir_url( __FILE__ ) . '../assets/js/scripts-and-styles-manager.js',
        [ 'wp-element', 'wp-api-fetch' ],
        filemtime( plugin_dir_path( __FILE__ ) . '../assets/js/scripts-and-styles-manager.js' ),
        true
    );

    wp_enqueue_style(
        'scripts-and-styles-manager',
        plugin_dir_url( __FILE__ ) . '../assets/css/scripts-and-styles-manager.css',
        [],
        filemtime( plugin_dir_path( __FILE__ ) . '../assets/css/scripts-and-styles-manager.css' )
    );

    wp_localize_script( 'scripts-and-styles-manager', 'ScriptStyleManagerSettings', [
        'apiUrl' => esc_url_raw( rest_url( 'scripts-and-styles-manager/v1' ) ),
        'nonce'  => wp_create_nonce('wp_rest'),
    ]);
});

// Add admin menu.
add_action( 'admin_menu', function () {
    add_menu_page(
        __( 'Script & Style Manager', 'scripts-and-styles-manager' ),
        __( 'Assets Manager', 'scripts-and-styles-manager' ),
        'edit_pages',
        'scripts-and-styles-manager',
        function () {
            echo '<div id="scripts-and-styles-manager-root"></div>';
        },
        plugin_dir_url(__FILE__) . '../assets/icon.svg',
        100
    );
});

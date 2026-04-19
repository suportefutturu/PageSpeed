<?php
namespace ScriptStyleManager\RestAPI;

use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

// Register REST API routes.
add_action( 'rest_api_init', function () {
    // Register `/pages` endpoint.
    register_rest_route( 'scripts-and-styles-manager/v1', '/pages', [
        'methods'             => 'GET',
        'callback'            => __NAMESPACE__ . '\\get_pages',
        'permission_callback' => function () {
            return current_user_can( 'edit_pages' );
        },
    ]);

    // Register `/assets` endpoint.
    register_rest_route( 'scripts-and-styles-manager/v1', '/assets', [
        'methods'             => 'GET',
        'callback'            => __NAMESPACE__ . '\\get_registered_assets',
        'permission_callback' => function () {
            return current_user_can( 'edit_pages' );
        },
    ]);

    // Register `/toggle-asset` endpoint.
    register_rest_route( 'scripts-and-styles-manager/v1', '/toggle-asset', [
        'methods'             => 'POST',
        'callback'            => __NAMESPACE__ . '\\toggle_page_asset',
        'args'     => [
            'page_id' => [
                'required' => true,
                'type'     => 'integer',
                'description' => 'The ID of the page to update.',
            ],
            'asset_type' => [
                'required' => true,
                'type'     => 'string',
                'enum'     => ['script', 'style'],
                'description' => 'The type of the asset to toggle (script/style).',
            ],
            'handle' => [
                'required' => true,
                'type'     => 'string',
                'description' => 'The handle of the asset to toggle.',
            ],
            'enabled' => [
                'required' => true,
                'type'     => 'boolean',
                'description' => 'Whether the asset should be enabled or disabled.',
            ],
        ],
        'permission_callback' => function () {
            return current_user_can( 'edit_pages' );
        },
    ]);
});

/**
 * Get a list of pages.
 *
 * @return WP_REST_Response|WP_Error
 */
function get_pages() {
    $args = [
        'post_type'      => 'page',
        'post_status'    => 'publish',
        'posts_per_page' => -1,
    ];

    $query  = new \WP_Query( $args );
    $pages  = [];


    foreach ( $query->posts as $post ) {
        $pages[] = [
            'id'    => $post->ID,
            'title' => $post->post_title,
        ];
    }

    return new WP_REST_Response( $pages, 200 );
}

/**
 * Get a list of all registered scripts and styles per page.
 *
 * @return WP_REST_Response|WP_Error
 */
function get_registered_assets(WP_REST_Request $request) {
    // Page ID for which to get scripts and styles
    $page_id = $request->get_param('page_id') ?: 0;
    $page = $request->get_param('page') ?: 1;
    $per_page = $request->get_param('per_page') ?: 10;
    $search = $request->get_param('search') ?: '';

    // Validate the page ID
    $post = get_post($page_id);
    if (!$post) {
        return new WP_REST_Response('Page not found', 404);
    }

    // Set up the global post to simulate loading the page
    $GLOBALS['post'] = $post;
    setup_postdata($post);
    // Enqueue scripts and styles
    wp_enqueue_scripts();

    // Access global scripts and styles registries
    global $wp_scripts, $wp_styles;

    // Collect enqueued scripts
    $scripts = array_map(function ($handle) use ($wp_scripts) {
        return [
            'handle' => $handle,
            'src'    => $wp_scripts->registered[$handle]->src ?? '',
            'type'   => 'script',
        ];
    }, $wp_scripts->queue);

    // Collect enqueued styles
    $styles = array_map(function ($handle) use ($wp_styles) {
        return [
            'handle' => $handle,
            'src'    => $wp_styles->registered[$handle]->src ?? '',
            'type'   => 'style',
        ];
    }, $wp_styles->queue);
    // Get disabled scripts and styles
    $disabled_scripts = get_post_meta( $page_id, '_disabled_scripts', true );
    $disabled_styles  = get_post_meta( $page_id, '_disabled_styles', true );

    $disabled_scripts = is_array( $disabled_scripts ) ? $disabled_scripts : [];
    $disabled_styles  = is_array( $disabled_styles ) ? $disabled_styles : [];

    // Add "enabled" state to each script and style
    $scripts = array_map( function ( $script ) use ( $disabled_scripts ) {
        $script['enabled'] = ! in_array( $script['handle'], $disabled_scripts );
        return $script;
    }, $scripts );


    // Filter out WP core styles 
    $styles = array_filter( $styles, function ( $style ) {
        // Exclude core styles by checking their src or handle
        return isset( $style['src'] ) && strpos( $style['src'], '/wp-content/plugins/' ) !== false;
    });
    // Reset the array keys to ensure a zero-indexed array
    $styles = array_values( $styles );

    $styles = array_map( function ( $style ) use ( $disabled_styles ) {
        $style['enabled'] = ! in_array( $style['handle'], $disabled_styles );
        return $style;
    }, $styles );
    
    $all_assets = array_merge($scripts, $styles);

    // Filter assets based on search term
    if (!empty($search)) {
        $all_assets = array_filter($all_assets, function ($asset) use ($search) {
            return stripos($asset['handle'], $search) !== false ||
                   stripos($asset['type'], $search) !== false ||
                   stripos($asset['src'], $search) !== false;
        });
    }

    $total_items = count($all_assets);
    $total_pages = ceil($total_items / $per_page);

    // Slice the data for the requested page
    $offset = ($page - 1) * $per_page;
    $paginated_assets = array_slice($all_assets, $offset, $per_page);
    // Reset the global post data
    wp_reset_postdata();

    // Return paginated data with metadata
    return new WP_REST_Response([
        'data' => $paginated_assets,
        'meta' => [
            'total_items' => $total_items,
            'total_pages' => $total_pages,
            'current_page' => (int) $page,
            'per_page' => (int) $per_page,
        ],
    ]);
}

/**
 * Toggle the asset for a page.
 *
 * @param WP_REST_Request $request
 * @return WP_REST_Response|WP_Error
 */
function toggle_page_asset( WP_REST_Request $request ) {

    $page_id    = $request->get_param('page_id');
    $asset_type = $request->get_param('asset_type');
    $handle     = $request->get_param('handle');
    $enabled    = $request->get_param('enabled');

    if ( ! $page_id || empty( $handle ) ) {
        return new WP_Error( 'invalid_data', __( 'Invalid page ID or handle.', 'scripts-and-styles-manager' ), [ 'status' => 400 ] );
    }
    // Fetch the current disabled assets from the database
    $meta_key = $asset_type === 'script' ? '_disabled_scripts' : '_disabled_styles';
    $disabled_assets = get_post_meta($page_id, $meta_key, true);
    if (!is_array($disabled_assets)) {
        $disabled_assets = [];
    }

    // Update the disabled assets list
    if ($enabled) {
        // Remove the handle if enabling
        $disabled_assets = array_diff($disabled_assets, [$handle]);
    } else {
        // Add the handle if disabling
        if (!in_array($handle, $disabled_assets, true)) {
            $disabled_assets[] = $handle;
        }
    }

    // Save the updated list back to the database
    update_post_meta($page_id, $meta_key, $disabled_assets);

    return new WP_REST_Response(['success' => true, 'updated_assets' => $disabled_assets], 200);
}
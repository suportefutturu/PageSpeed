<?php
/**
 * Plugin Name: Scripts and Styles Manager
 * Description: A plugin to manage scripts and styles on a page level.
 * Requires PHP: 7.2
 * Requires at least: 5.0
 * Version: 1.0.0
 * Author: Abdelhalim Khouas
 * Text Domain: scripts-and-styles-manager
 * License: GPL v3 or later
 * License URI: https://www.gnu.org/licenses/gpl-3.0.html
 */

namespace ScriptStyleManager;

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

// Include plugin files.
require_once plugin_dir_path( __FILE__ ) . 'includes/rest-api.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/admin.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/dequeue.php';
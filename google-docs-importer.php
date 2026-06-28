<?php
/**
 * Plugin Name: Google Docs Importer
 * Description: Import Google Docs directly into WordPress as Gutenberg blocks.
 * Version: 1.0.0
 * Author: Chris Brosky
 * Requires at least: 6.8
 * Requires PHP: 8.1
 */

defined( 'ABSPATH' ) || exit;

define( 'GDI_VERSION', '1.0.0' );
define( 'GDI_PATH', plugin_dir_path( __FILE__ ) );
define( 'GDI_URL', plugin_dir_url( __FILE__ ) );

require_once GDI_PATH . 'includes/class-gutenberg-converter.php';
require_once GDI_PATH . 'includes/class-google-auth.php';
require_once GDI_PATH . 'includes/class-google-docs.php';
require_once GDI_PATH . 'includes/class-image-importer.php';
require_once GDI_PATH . 'includes/class-importer.php';
require_once GDI_PATH . 'includes/class-admin.php';

new GDI_Admin();
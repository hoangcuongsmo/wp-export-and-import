<?php
/*
Plugin Name: WP Export and Import
Plugin URI: https://huongphan.online/plugins/wp-export-and-import
Description: A plugin to export and import WordPress data
Version: 1.0.0
Author: Huong Phan
Author URI: https://huongphan.online
License: GPL2
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Text Domain: wp-export-and-import
Domain Path: /languages
*/

// Prevent direct access

use WPEAI\WPEAI_Admin;
use WPEAI\WPEAI_users;

if (! defined('ABSPATH')) {
  exit;
}
require __DIR__ . '/vendor/autoload.php';

// Define plugin constants


// Load plugin text domain
add_action('plugins_loaded', function () {
  load_plugin_textdomain('wp-export-and-import', false, dirname(plugin_basename(__FILE__)) . '/languages');
});

// Activation hook
register_activation_hook(__FILE__, function () {
  // Perform setup tasks
});

// Deactivation hook
register_deactivation_hook(__FILE__, function () {
  // Perform cleanup tasks
});

// Wordpress Admin
new WPEAI_Admin;
new WPEAI_users;

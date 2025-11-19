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

use WPEAI\Helper;
use WPEAI\WPEAI_Admin;
use WPEAI\WPEAI_posts;
use WPEAI\WPEAI_terms;
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
new WPEAI_posts;
new WPEAI_terms;

add_action('init', function () {

  add_filter('intermediate_image_sizes', function ($default_sizes) {
    return ['thumbnail', 'medium', 'large'];
  });
  
  if (isset($_GET['debug'])) {
    var_dump(get_intermediate_image_sizes());
  }
  if (isset($_GET['dev'])) {
    global $wpdb;
    $_posters = $wpdb->get_results("SELECT * FROM cpc_postmeta WHERE meta_key = 'poster'");
    foreach ($_posters as $key => $_poster) {
      $post_id = $_poster->post_id;
      if (!empty($_poster->meta_value)) {
        $old_media_id = $_poster->meta_value;
        $new_media = Helper::get_new_post_from_old_id($old_media_id);
        if ($new_media) {
          $new_media_id = $new_media->post_id;
          $_wp_attached_file = Helper::get_wp_attached_file($new_media_id);
          $file_url = 'https://chiieongc35.sg-host.com/wp-content/uploads/' . $_wp_attached_file->meta_value;
          Helper::get_file($file_url, $new_media_id);
          update_post_meta($post_id, 'poster', $new_media_id, $old_media_id);
          update_post_meta($new_media_id, 'media_replaced', 1);
        }
      }
    }
  }


  // if (isset($_GET['dev'])) {
  //   global $wpdb;
  //   $_posters = $wpdb->get_results("SELECT * FROM cpc_postmeta WHERE meta_key = '_thumbnail_id'");
  //   foreach ($_posters as $key => $_poster) {
  //     $post_id = $_poster->post_id;
  //     if (!empty($_poster->meta_value)) {
  //       $old_media_id = $_poster->meta_value;
  //       $new_media = Helper::get_new_post_from_old_id($old_media_id);
  //       if ($new_media) {
  //         $new_media_id = $new_media->post_id;
  //         $_wp_attached_file = Helper::get_wp_attached_file($new_media_id);
  //         $file_url = 'https://chiieongc35.sg-host.com/wp-content/uploads/' . $_wp_attached_file->meta_value;
  //         if (!Helper::media_replaced($new_media_id)) {
  //           Helper::get_file($file_url, $new_media_id);
  //           update_post_meta($post_id, '_thumbnail_id', $new_media_id, $old_media_id);
  //           update_post_meta($post_id, '_thumbnail_id', $new_media_id, $old_media_id);
  //         }
  //       }
  //       echo '<br>';
  //     }
  //     // var_dump($key);
  //   }
  // }
});

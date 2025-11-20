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
    // var_dump(get_post_meta(10478, 'old_site_id', true));
    global $wpdb;
    $attachments = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}postmeta INNER JOIN {$wpdb->prefix}posts on {$wpdb->prefix}posts.ID = {$wpdb->prefix}postmeta.post_id WHERE {$wpdb->prefix}posts.post_type = 'post' AND meta_key = '_thumbnail_id'");
    foreach ($attachments as $attachment) {
      if ((int)$attachment->post_id === 10478) {
        var_dump(get_post_meta($attachment->meta_value, 'old_site_id', true));
        var_dump(get_post_meta($attachment->meta_value, 'media_replaced', true));
      }
    }
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


  if (isset($_GET['_thumbnail_id'])) {
    global $wpdb;
    $_posters = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}postmeta WHERE meta_key = '_thumbnail_id'");
    foreach ($_posters as $key => $_poster) {
      $post_id = $_poster->post_id;
      if (!empty($_poster->meta_value)) {
        $old_media_id = $_poster->meta_value;
        $new_media = Helper::get_new_post_from_old_id($old_media_id);
        if ($new_media) {
          $new_media_id = $new_media->post_id;
          $media_replaced = get_post_meta($new_media_id, 'media_replaced', true);
          if (!$media_replaced) {
            $_wp_attached_file = Helper::get_wp_attached_file($new_media_id);
            $file_url = 'https://chiieongc35.sg-host.com/wp-content/uploads/' . $_wp_attached_file->meta_value;
            if (!Helper::media_replaced($new_media_id)) {
              Helper::get_file($file_url, $new_media_id);
              update_post_meta($post_id, '_thumbnail_id', $new_media_id, $old_media_id);
              update_post_meta($new_media_id, 'media_replaced', 1);
            }
          } else {
            update_post_meta($post_id, '_thumbnail_id', $new_media_id, $old_media_id);
          }
        }
      }
    }
  }

  if (isset($_GET['medias'])) {
    global $wpdb;
    $prefix = $wpdb->prefix;
    $posts = $wpdb->get_results("SELECT * FROM {$prefix}posts WHERE post_type = 'post'");
    foreach ($posts as $key => $post) {
      $post_id = (int)$post->ID;
      $poster = get_post_meta($post_id, 'poster', true);
      $background = get_post_meta($post_id, 'background', true);
      $_thumbnail_id = get_post_meta($post_id, '_thumbnail_id', true);
      if ($poster) {
        $poster_new_media = Helper::get_new_post_from_old_id($poster);
        if ($poster_new_media) {
          $poster_new_media_replaced = get_post_meta($poster_new_media->post_id, 'media_replaced', true);
          if ($poster_new_media_replaced !== '1') {
            $_wp_attached_file = Helper::get_wp_attached_file($poster_new_media->post_id);
            $file_url = 'https://chiieongc35.sg-host.com/wp-content/uploads/' . $_wp_attached_file->meta_value;
            Helper::get_file($file_url, $poster_new_media->post_id);
            update_post_meta($post_id, 'poster', $poster_new_media->post_id, $poster);
            update_post_meta($poster_new_media->post_id, 'media_replaced', 1);
          } else {
            update_post_meta($post_id, 'poster', $poster_new_media->post_id, $poster);
          }
        }
      }

      if ($background) {
        $background_new_media = Helper::get_new_post_from_old_id($background);
        if ($background_new_media) {
          $background_new_media_replaced = get_post_meta($background_new_media->post_id, 'media_replaced', true);
          if ($background_new_media_replaced !== '1') {
            $_wp_attached_file = Helper::get_wp_attached_file($background_new_media->post_id);
            $file_url = 'https://chiieongc35.sg-host.com/wp-content/uploads/' . $_wp_attached_file->meta_value;
            Helper::get_file($file_url, $background_new_media->post_id);
            update_post_meta($post_id, 'background', $background_new_media->post_id, $background);
            update_post_meta($background_new_media->post_id, 'media_replaced', 1);
          } else {
            update_post_meta($post_id, 'background', $background_new_media->post_id, $background);
          }
        }
      }

      if ($_thumbnail_id) {
        $_thumbnail_id_new_media = Helper::get_new_post_from_old_id($_thumbnail_id);
        if ($_thumbnail_id_new_media) {
          $_thumbnail_id_new_media_replaced = get_post_meta($_thumbnail_id_new_media->post_id, 'media_replaced', true);
          if ($_thumbnail_id_new_media_replaced !== '1') {
            $_wp_attached_file = Helper::get_wp_attached_file($_thumbnail_id_new_media->post_id);
            $file_url = 'https://chiieongc35.sg-host.com/wp-content/uploads/' . $_wp_attached_file->meta_value;
            Helper::get_file($file_url, $_thumbnail_id_new_media->post_id);
            update_post_meta($post_id, '_thumbnail_id', $_thumbnail_id_new_media->post_id, $_thumbnail_id);
            update_post_meta($_thumbnail_id_new_media->post_id, 'media_replaced', 1);
          } else {
            update_post_meta($post_id, '_thumbnail_id', $_thumbnail_id_new_media->post_id, $_thumbnail_id);
          }
        }
      }
    }
  }
});

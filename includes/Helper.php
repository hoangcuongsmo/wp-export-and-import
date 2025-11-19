<?php

namespace WPEAI;

use WP_Error;

class Helper
{
  public static function fields_to_sql(array $fields)
  {
    return implode(',', $fields);
  }

  public static function values_to_sql(array $values, array $fields)
  {
    $values_str = '';
    foreach ($fields as $key => $field) {
      if ($key !== 0) $values_str .= ',';
      $values_str .= "`$values[$field]`";
    }
    return $values_str;
  }

  public static function get_new_post_from_old_id($old_id)
  {
    global $wpdb;
    return $wpdb->get_row("SELECT * FROM {$wpdb->prefix}postmeta WHERE meta_key = 'old_site_id' AND meta_value = $old_id");
  }

  public static function get_wp_attached_file($media_id)
  {
    global $wpdb;
    return $wpdb->get_row("SELECT * FROM {$wpdb->prefix}postmeta WHERE meta_key = '_wp_attached_file' AND post_id = $media_id");
  }

  public static function get_file($url, $media_id)
  {
    require_once(ABSPATH . 'wp-admin/includes/image.php');
    require_once(ABSPATH . 'wp-admin/includes/media.php');
    require_once(ABSPATH . 'wp-admin/includes/file.php');
    $upload_dir = wp_upload_dir();

    $filename = wp_basename(parse_url($url, PHP_URL_PATH));

    $file_path = trailingslashit($upload_dir['path']) . $filename;

    // $headers = @get_headers($url);

    // if (!$headers || strpos($headers[0], '200') === false) {
    //   return new WP_Error('download_failed', 'Không tải được ảnh từ URL');
    // }

    $file_data = file_get_contents($url);

    if (! $file_data) {
      return new WP_Error('download_failed', 'Không tải được ảnh từ URL');
    }

    file_put_contents($file_path, $file_data);

    $relative_path = str_replace($upload_dir['basedir'] . '/', '', $file_path);

    update_post_meta($media_id, '_wp_attached_file', $relative_path);

    $filetype = wp_check_filetype($filename, null);

    wp_update_post([
      'ID' => $media_id,
      'post_mime_type' => $filetype['type'],
    ]);

    $attach_data = wp_generate_attachment_metadata($media_id, $file_path);
    wp_update_attachment_metadata($media_id, $attach_data);

    return true;
  }

  public static function media_replaced($media_id)
  {
    if (get_post_meta($media_id, 'media_replaced', true)) {
      return true;
    }
    return false;
  }
}

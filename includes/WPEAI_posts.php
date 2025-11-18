<?php

namespace WPEAI;

use wpdb;

class WPEAI_posts
{

  public $wpdb;
  public $posts_table_name;
  public $postmeta_table_name;
  public $term_relationships_table_name;

  public function __construct()
  {
    global $wpdb;
    $this->wpdb = $wpdb;
    $this->posts_table_name = $wpdb->posts;
    $this->postmeta_table_name = $wpdb->postmeta;
    $this->term_relationships_table_name = $wpdb->term_relationships;
    add_action('admin_init', [$this, 'export']);
    add_action('admin_init', [$this, 'import']);
  }

  protected function fields($without_id = false)
  {
    $user_table_fields = ['ID', 'user_login', 'user_pass', 'user_nicename', 'user_email', 'user_url', 'user_registered', 'user_activation_key', 'user_status', 'display_name'];
    if ($without_id) {
      $user_table_fields = ['user_login', 'user_pass', 'user_nicename', 'user_email', 'user_url', 'user_registered', 'user_activation_key', 'user_status', 'display_name'];
    }
    return apply_filters('WPEAI_users_table_fields', $user_table_fields);
  }

  protected function meta_fields()
  {
    $usermeta_table_fields = ['umeta_id', 'user_id', 'meta_key', 'meta_value'];
    return apply_filters('WPEAI_usermeta_table_fields', $usermeta_table_fields);
  }

  public function export()
  {

    if (!isset($_POST['export_user'])) return;

    $query_str = "SELECT * FROM $this->user_table_name";
    $users = $this->wpdb->get_results($query_str);

    $json_data = [];
    foreach ($users as $user_key => $user) {

      $fields = $this->fields();

      // Get user data to json file
      foreach ($fields as $field_key => $field) {
        $json_data[$user_key][$field] = $user->$field;
      }

      // Get user metas and add to json file
      $user_metas = $this->wpdb->get_results($this->wpdb->prepare("SELECT * FROM cpc_usermeta WHERE user_id = %d", $user->ID));
      foreach ($user_metas as $user_meta) {
        $json_data[$user_key]['usermeta'][] = [
          'umeta_id' => $user_meta->umeta_id,
          'user_id' => $user_meta->user_id,
          'meta_key' => $user_meta->meta_key,
          'meta_value' => $user_meta->meta_value,
        ];
      }
    }

    $json_string = json_encode($json_data, JSON_PRETTY_PRINT);

    header('Content-Type: application/json; charset=utf-8');
    header('Content-Disposition: attachment; filename="user-' . date('d-m-Y-H-i-s') . '.json"');
    echo $json_string;
    exit;
  }

  public function export_user_data($usermetas) {}

  public function import()
  {
    if (!isset($_POST['import_user'])) return;
    $file = $_FILES['import_user_file'] ?? null ?: null;
    $replace = $_POST['import_replace'] ?? 'no' ?: 'no';
    if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
      echo "File upload lỗi!";
      return;
    }
    $file_content = file_get_contents($file['tmp_name']);
    if (empty($file_content)) return;

    $users = json_decode($file_content, true);
    foreach ($users as $key => $user) {
      $this->import_user($user);
    }
  }

  public function import_user($user)
  {
    $fields = $this->fields(true);
    $fields_str = implode(',', $fields);
    $values = $this->implode_user_values($user);
    $this->wpdb->query("INSERT INTO $this->user_table_name ($fields_str) VALUES ($values)");

    $user_id = $this->wpdb->insert_id;
    var_dump($user_id);
    update_user_meta($user_id, 'old_site_id', $user['ID']);
    $usermeta = $user['usermeta'] ?? [];
    if (!empty($usermeta)) {
      foreach ($usermeta as $meta) {
        $this->wpdb->query(
          $this->wpdb->prepare("INSERT INTO $this->usermeta_table_name (user_id,meta_key,meta_value) VALUES (%d,%s,%s)", $user_id, $meta['meta_key'], $meta['meta_value'])
        );
      }
    }
  }

  public function implode_user_values($user)
  {
    $fields = $this->fields(true);
    $values = '';
    foreach ($fields as $key => $field) {
      if ($key !== 0) $values .= ',';
      $values .= "'$user[$field]'";
    }
    return $values;
  }

  public function import_user_data($usermetas) {}
}

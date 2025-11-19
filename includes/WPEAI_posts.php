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

  public function export()
  {
    if (!isset($_POST['export_posts'])) return;
    $sql_query = "SELECT 
      p.*,
      (
        SELECT JSON_OBJECTAGG(t.k, t.v)
        FROM (
          SELECT 
            CASE 
              WHEN meta_key IS NULL OR TRIM(meta_key) = '' THEN CONCAT('_null_key_', meta_id)
              ELSE meta_key
            END AS k,
            meta_value AS v
          FROM cpc_postmeta
          WHERE post_id = p.ID
        ) AS t
      ) AS meta
    FROM cpc_posts AS p
    WHERE p.post_type IN ('post','attachment','revision', 'research')
    ORDER BY p.ID";
    $results = $this->wpdb->get_results($sql_query);
    $posts = array_map(function ($row) {
      return (array) $row;
    }, $results);

    $json_string = json_encode($posts, JSON_PRETTY_PRINT);

    header('Content-Type: application/json; charset=utf-8');
    header('Content-Disposition: attachment; filename="user-' . date('d-m-Y-H-i-s') . '.json"');
    echo $json_string;
    exit;
  }

  public function fields()
  {
    $fields = ['post_author', 'post_date', 'post_date_gmt', 'post_content', 'post_title', 'post_excerpt', 'post_status', 'comment_status', 'ping_status', 'post_password', 'post_name', 'to_ping', 'pinged', 'post_modified', 'post_modified_gmt', 'post_content_filtered', 'post_parent', 'guid', 'menu_order', 'post_type', 'post_mime_type', 'comment_count'];
    return apply_filters('WPEAI_posts_fields', $fields);
  }

  public function import()
  {
    if (!isset($_POST['import_posts'])) return;
    $file = $_FILES['import_post_file'] ?? null;
    if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
      echo "File upload error!";
      return;
    }

    $file_content = file_get_contents($file['tmp_name']);
    if (empty($file_content)) {
      echo "File upload empty!";
      return;
    };

    $rows = json_decode($file_content, true);

    foreach ($rows as $key => $row) {
      $sql_query_fields = Helper::fields_to_sql($this->fields());
      $sql_query_values = Helper::values_to_sql($row, $this->fields());
      $sql_query = $this->wpdb->prepare(
        "INSERT INTO $this->posts_table_name ($sql_query_fields) VALUES 
          (%d,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%d,%s,%d,%s,%s,%d)",
        $row['post_author'],
        $row['post_date'],
        $row['post_date_gmt'],
        $row['post_content'],
        $row['post_title'],
        $row['post_excerpt'],
        $row['post_status'],
        $row['comment_status'],
        $row['ping_status'],
        $row['post_password'],
        $row['post_name'],
        $row['to_ping'],
        $row['pinged'],
        $row['post_modified'],
        $row['post_modified_gmt'],
        $row['post_content_filtered'],
        $row['post_parent'],
        $row['guid'],
        $row['menu_order'],
        $row['post_type'],
        $row['post_mime_type'],
        $row['comment_count']
      );

      $metas = json_decode($row['meta'], true);
      $this->wpdb->query($sql_query);
      $post_id = $this->wpdb->insert_id;
      update_post_meta($post_id, 'old_site_id', $row['ID']);
      $this->import_metas($post_id, $metas);
    }
  }

  public function import_metas($post_id, $metas)
  {
    if (is_array($metas)) {
      foreach ($metas as $meta_key => $meta_value) {
        update_post_meta($post_id, $meta_key, $meta_value);
      }
    }
  }

  public function post_exists($post_id)
  {
    $result = $this->wpdb->get_row("SELECT * FROM $this->postmeta_table_name WHERE meta_key = 'old_site_id' AND meta_value = $post_id");
    return $result;
  }
}

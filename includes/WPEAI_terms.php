<?php

namespace WPEAI;

class WPEAI_terms
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
    if (!isset($_POST['export_terms'])) return;
    $sql_query = "SELECT
        t.term_id,
        t.name,
        t.slug,
        tt.taxonomy,
        tt.description,
        tt.parent,
        tt.count,
        (
            SELECT JSON_OBJECTAGG(tm.meta_key, tm.meta_value)
            FROM cpc_termmeta tm
            WHERE tm.term_id = t.term_id
        ) AS meta,
        (
            SELECT JSON_ARRAYAGG(tr.object_id)
            FROM cpc_term_relationships tr
            WHERE tr.term_taxonomy_id = tt.term_taxonomy_id
        ) AS related_posts
    FROM cpc_terms t
    JOIN cpc_term_taxonomy tt ON t.term_id = tt.term_id";
    $results = $this->wpdb->get_results($sql_query);
    $terms = array_map(function ($row) {
      return (array) $row;
    }, $results);

    $json_string = json_encode($terms, JSON_PRETTY_PRINT);

    header('Content-Type: application/json; charset=utf-8');
    header('Content-Disposition: attachment; filename="terms-' . date('d-m-Y-H-i-s') . '.json"');
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
    if (!isset($_POST['import_terms'])) return;
    $file = $_FILES['import_term_file'] ?? null;
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
      $term = wp_insert_term(
        $row['name'],
        $row['taxonomy'],
        [
          'slug' => $row['slug'],
          'description' => $row['description'],
          'count' => $row['count']
        ]
      );

      if (!is_wp_error($term)) {
        $term_id = $term['term_id'];
        $metas = $row['meta'] ? json_decode($row['meta'], true) : [];
        foreach ($metas as $meta_key => $meta_value) {
          update_term_meta($term_id, $meta_key, $meta_value);
        }
        $related_posts = $row['related_posts'] ? json_decode($row['related_posts'], true) : [];
        foreach ($related_posts as $post_id) {
          $new_post = $this->get_new_post($post_id);
          if ($new_post) {
            wp_set_post_terms($new_post->post_id, [$term_id], $row['taxonomy']);
          }
        }
      }
    }
  }

  public function get_new_post($old_site_id)
  {
    $row = $this->wpdb->get_row("SELECT * FROM {$this->wpdb->postmeta} WHERE meta_key = 'old_site_id' AND meta_value = $old_site_id");
    return $row;
  }
}

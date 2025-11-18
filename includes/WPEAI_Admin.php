<?php

namespace WPEAI;

class WPEAI_Admin
{
  public function __construct()
  {
    add_action('admin_menu', [$this, 'wp_ei_register_admin_page']);
  }

  public function wp_ei_register_admin_page()
  {
    add_menu_page(
      __('WP Export & Import', 'wp-ei'),
      __('WP Export & Import', 'wp-ei'),
      'manage_options',
      'wp-ei',
      [$this, 'wp_ei_render_admin_page'],
      'dashicons-migrate',
      80
    );
  }

  public function wp_ei_render_admin_page()
  {
    if (! current_user_can('manage_options')) {
      return;
    }
    if (isset($_POST['export_user'])) {
      $WPEAI_users = new WPEAI_users;
      $users = $WPEAI_users->export();
      var_dump($users);
    }
?>
    <div class="">
      <h2>Import and export users</h2>
      <form action="<?php echo admin_url('/admin.php?page=wp-ei'); ?>" id="iae-user" enctype="multipart/form-data" method="post">
        <input type="file" name="import_user_file" id="import_user_file">
        <div>
          <input type="checkbox" name="import_replace" id="import_replace" value="yes">
          <label for="import_replace">Replace exists data</label>
        </div>
        <br>
        <input type="submit" value="Export" name="export_user">
        <input type="submit" value="Import" name="import_user">
      </form>
    </div>
<?php
  }
}

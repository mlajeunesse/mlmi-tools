<?php
/**
*	Plugin Name: Outils MLMI
*	Plugin URI: https://mathieulajeunesse.com
*	Description: Outils de configuration de Wordpress par Mathieu Lajeunesse médias interactifs. Mis à jour pour la version 2019 de Wordpress / Bedrock.
*	Version: 1.5.4
*	Author: Mathieu Lajeunesse
*	Author URI: https://mathieulajeunesse.com
*	Text Domain: mlmi-tools
*/

/*
*	Basic configuration
*/
add_action('init', function() {
  /* Remove header actions */
  remove_action('wp_head', 'rsd_link');
  remove_action('wp_head', 'wlwmanifest_link');
  remove_action('wp_head', 'wp_generator');
  remove_action('wp_head', 'feed_links_extra', 3);
  remove_action('wp_head', 'wp_shortlink_wp_head');
  remove_action('wp_head', 'rel_canonical');
  remove_action('wp_head', 'print_emoji_detection_script', 7);
  remove_action('wp_head', 'wp_oembed_add_discovery_links');
  remove_action('wp_head', 'wp_oembed_add_host_js');
  remove_action('wp_print_styles', 'print_emoji_styles');
  remove_action('rest_api_init', 'wp_oembed_register_route');
  remove_filter('oembed_dataparse', 'wp_filter_oembed_result', 10);
  remove_action('wp_head', 'rest_output_link_wp_head', 10);
  remove_action('wp_head', 'wp_oembed_add_discovery_links', 10);
}, PHP_INT_MAX);

/*
*	Password-protected sites
*/
add_action('wp', function(){
  if (defined('BLOCK_WEBSITE') && BLOCK_WEBSITE && !is_user_logged_in() && !is_login_page() && !has_allowed_action()) {
    $redirect_url = esc_url(wp_login_url());
    wp_safe_redirect($redirect_url."?redirect_to=".$_SERVER['REQUEST_URI']);
    exit;
  }
});

/*
*	Admin cleanup
*/
define('WP_OPTIMIZE_NOADS_B', true);

/*
*	Default theme supports
*/
add_action('after_setup_theme', function() {
  add_filter('use_default_gallery_style', '__return_false');
  add_theme_support('post-thumbnails');
  add_theme_support('html5', array('gallery', 'caption'));
});

/*
*	Remove support for Gutenberg styles
*/
add_action('wp_enqueue_scripts', function() {
  wp_dequeue_style('wp-block-library');
});

/*
*	Rewrites in .htaccess
*/
add_action('generate_rewrite_rules', function($content) {
  global $wp_rewrite;
  $stylesheet_directory = explode('/themes/', get_template_directory());
  $theme_name = next($stylesheet_directory);
  $new_rules = array(
    'css/(.*)' => 'app/themes/'.$theme_name.'/assets/css/$1',
    'js/(.*)' => 'app/themes/'.$theme_name.'/assets/js/$1',
    'img/(.*)' => 'app/themes/'.$theme_name.'/assets/img/$1',
    'lang/(.*)' => 'app/themes/'.$theme_name.'/assets/lang/$1',
    'font/(.*)' => 'app/themes/'.$theme_name.'/assets/font/$1',
    'mail/(.*)' => 'app/themes/'.$theme_name.'/assets/mail/$1',
    'video/(.*)' => 'app/themes/'.$theme_name.'/assets/video/$1',
    'assets/(.*)' => 'app/themes/'.$theme_name.'/assets/$1',
    'wp-content/(.*)' => 'app/$1',
    'uploads/(.*)' => 'app/uploads/$1'
  );
  $wp_rewrite->non_wp_rules += $new_rules;
});

/*
*	Add post name to body class
*/
add_filter('body_class', function($classes = '') {
  global $post;
  if ($post) { $classes[] = $post->post_name; }
  return $classes;
});

/*
*	Clean up admin menu
*/
add_action('admin_menu', function() {
  remove_submenu_page('tools.php', 'tools.php');
  remove_submenu_page('tools.php', 'import.php');
  remove_submenu_page('tools.php', 'export.php');
  remove_submenu_page('tools.php', 'disable_comments_tools');
  remove_submenu_page('index.php', 'relevanssi/relevanssi.php');
  remove_submenu_page('index.php', 'relevanssi_admin_search');
  remove_submenu_page('themes.php', 'themes.php');
  remove_submenu_page('themes.php', 'theme-editor.php');
  remove_submenu_page('themes.php', 'customize.php?return='.urlencode($_SERVER['REQUEST_URI']));
  remove_submenu_page('plugins.php', 'plugin-editor.php');
  remove_submenu_page('options-general.php', 'loco-translate-settings-legacy');
}, PHP_INT_MAX);

/*
*	Cleanup dashboard
*/
add_action('wp_dashboard_setup', function() {
  global $wp_meta_boxes;
  unset($wp_meta_boxes['dashboard']['side']['core']['dashboard_quick_press']);
  unset($wp_meta_boxes['dashboard']['normal']['core']['dashboard_incoming_links']);
  unset($wp_meta_boxes['dashboard']['normal']['core']['dashboard_right_now']);
  unset($wp_meta_boxes['dashboard']['normal']['core']['dashboard_plugins']);
  unset($wp_meta_boxes['dashboard']['normal']['core']['dashboard_recent_drafts']);
  unset($wp_meta_boxes['dashboard']['normal']['core']['dashboard_recent_comments']);
  unset($wp_meta_boxes['dashboard']['normal']['core']['dashboard_activity']);
  unset($wp_meta_boxes['dashboard']['side']['core']['dashboard_primary']);
  unset($wp_meta_boxes['dashboard']['side']['core']['dashboard_secondary']);
  remove_action("try_gutenberg_panel", "wp_try_gutenberg_panel");
  remove_action('welcome_panel', 'wp_welcome_panel');
});

/*
*	Do not check for updates
*/
$mlmi_check_for_updates = true;
if (is_admin()) {
  if (defined('DISALLOW_FILE_MODS') && DISALLOW_FILE_MODS == true) {
    $mlmi_check_for_updates = false;
  } else {
    include_once(ABSPATH.'wp-admin/includes/plugin.php');
    if (is_plugin_active('disable-updates/disable-updates.php')) {
      $mlmi_check_for_updates = false;
    }
  }
  if ($mlmi_check_for_updates === false) {
    remove_action('admin_init', '_maybe_update_core');
    remove_action('admin_init', '_maybe_update_plugins');
    remove_action('admin_init', '_maybe_update_themes');
  }
}

/*
*	Hide admin bar
*/
add_filter('show_admin_bar', function() {
  return false;
});

/*
*	Block and cleanup dashboard
*/
add_action('admin_init', function() {
  /* Block dashboard for non admin */
  if ((!current_user_can('administrator') && !current_user_can('editor')) && (!defined('DOING_AJAX') || !DOING_AJAX) && !isset($_REQUEST['action'])) {
    wp_redirect(home_url()); exit;
  }
  
  /* Remove Wordpress update nag */
  global $mlmi_check_for_updates;
  if ($mlmi_check_for_updates == false) {
  	remove_action('admin_notices', 'update_nag', 3);
  }
}, 1);

/*
*	Custom excerpt ellipsis
*/
add_filter('excerpt_more', function($more) {
  return '...';
});

/*
*	Replace hard coded URLs in content
*/
add_filter('inline_urls', function($content) {
  $hosts = array(
    "https://localhost:3000",
    "https://localhost:3002",
  );
  foreach ($hosts as $host) {
    $content = str_replace($host, WP_HOME, $content);
  }
  return $content;
});

/*
*	Inline spaces
*/
add_filter('inline_spaces', function($content) {
  $content = str_replace(" :", "&nbsp;:", $content);
  $content = str_replace(" !", "&nbsp;!", $content);
  $content = str_replace(" ?", "&nbsp;?", $content);
  $content = str_replace(" »", "&nbsp;»", $content);
  $content = str_replace(" &raquo;", "&nbsp;»", $content);
  $content = str_replace(" &#187;", "&nbsp;»", $content);
  $content = str_replace("« ", "«&nbsp;", $content);
  $content = str_replace("&laquo; ", "«&nbsp;", $content);
  $content = str_replace("&#171; ", "«&nbsp;", $content);
  return $content;
});

/*
*	Add content filters to Wordpress content
*/
add_filter('the_content', function($content) {
  $content = apply_filters('inline_urls', $content);
  $content = apply_filters('inline_spaces', $content);
  return $content;
});

/*
*	Add content filters to ACF content
*/
add_filter('acf/load_value', function($field_value) {
  $field_value = apply_filters('inline_urls', $field_value);
  $field_value = apply_filters('inline_spaces', $field_value);
  return $field_value;
});

/*
*	Helper function: print preformatted text
*/
function pre($variable) {
  echo '<pre>';
  print_r($variable);
  echo '</pre>';
}

/*
*	Helper function: get random string
*/
function string_random($length, $base = "ABCDEFGHKLMNOPQRSTWXYZabcdefghjkmnpqrstwxyz123456789")
{
  $string = '';
  mt_srand((double)microtime()*1000000);
  while (strlen($string) < $length){
    $string .= $base{mt_rand(0, strlen($base) - 1)};
  }
  return $string;
}

/*
*	Log functions
*/
function mlmi_log($message, $type = 'echo') {
  if (!defined('LOG_FILE')) {
    return false;
  }
  if ($type == 'print' || $type == 'echo') {
    error_log(date('[Y-m-d H:i:s] '). $message.PHP_EOL, 3, LOG_FILE);
  } else if ($type == 'pre' || $type == "print_r") {
    ob_start();
    print_r($message);
    $message = ob_get_contents();
    ob_end_clean();
    error_log(date('[Y-m-d H:i:s] '). $message.PHP_EOL, 3, LOG_FILE);
  } else if ($type == 'dump' || $type == 'var_dump') {
    ob_start();
    var_dump($message);
    $message = ob_get_contents();
    ob_end_clean();
    error_log(date('[Y-m-d H:i:s] '). $message.PHP_EOL, 3, LOG_FILE);
  }
}
function mlmi_log_pre($message) {
  log($message, 'pre');
}
function mlmi_log_dump($message) {
  log($message, 'dump');
}

/*
*	Check if is login page
*/
function is_login_page() {
  $is_login_page = in_array($GLOBALS['pagenow'], array('wp-login.php', 'wp-register.php'));
  return apply_filters('is_login_page', $is_login_page);
}

/*
*	Check for allowed action
*/
function has_allowed_action() {
  $allowed_actions = array(
    /* WP Sync DB */
    'wpsdb_verify_connection_to_remote_site',
    'wpsdb_remote_initiate_migration',
    'wpsdb_process_pull_request',
    'wpsdb_fire_migration_complete',
    'wpsdb_backup_remote_table',
    'wpsdb_remote_finalize_migration',
    
    /* WP Sync DB Media Files */
    'wpsdbmf_determine_media_to_migrate',
    'wpsdbmf_get_remote_media_listing',
    'wpsdbmf_migrate_media'
  );
  $has_allowed_action = (isset($_POST['action']) && in_array($allowed_actions, $_POST['action']));
  return apply_filters('has_allowed_action', $has_allowed_action);
}

/*
* ACF WPML
*/
add_filter('wpml_custom_field_original_data', function() {
  return false;
});
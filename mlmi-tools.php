<?php
/**
 * Plugin Name: Outils MLMI
 * Plugin URI: http://mathieulajeunesse.com
 * Description: Outils de configuration de Wordpress par Mathieu Lajeunesse médias interactifs. Mis à jour pour la version 2018 de Wordpress / Bedrock.
 * Version: 1.3.6
 * Author: Mathieu Lajeunesse
 * Author URI: http://mathieulajeunesse.com
 * Text Domain: mlmi-tools
 */

/**
*	Basic configuration
*/

function mlmi_tools_init()
{
	// remove header actions
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

	// protect website with login form
	if (defined('BLOCK_WEBSITE') && BLOCK_WEBSITE && !is_user_logged_in() && !is_login_page()){
		$redirect_url = esc_url(wp_login_url());
		wp_safe_redirect($redirect_url."?redirect_to=".$_SERVER['REQUEST_URI']);
		exit;
	}
}
add_action('init', 'mlmi_tools_init', 9999);

function mlmi_tools_setup()
{
	// no default gallery style
	add_filter('use_default_gallery_style', '__return_false');

	// add support for thumbnails
	add_theme_support('post-thumbnails');

	// add support for HTML5
	add_theme_support('html5', array('gallery', 'caption'));
}
add_action('after_setup_theme', 'mlmi_tools_setup');

function mlmi_tools_add_rewrites($content)
{
	// adds some rewrite rules when loading the permalinks page
	global $wp_rewrite;
	$stylesheet_directory = explode('/themes/', get_stylesheet_directory());
	$theme_name = next($stylesheet_directory);
	$new_rules = array(
		'css/(.*)'		=> 'app/themes/'.$theme_name.'/assets/css/$1',
		'js/(.*)'		=> 'app/themes/'.$theme_name.'/assets/js/$1',
		'img/(.*)'		=> 'app/themes/'.$theme_name.'/assets/img/$1',
		'lang/(.*)'		=> 'app/themes/'.$theme_name.'/assets/lang/$1',
		'font/(.*)'		=> 'app/themes/'.$theme_name.'/assets/font/$1',
		'mail/(.*)'		=> 'app/themes/'.$theme_name.'/assets/mail/$1',
		'video/(.*)'	=> 'app/themes/'.$theme_name.'/assets/video/$1',
		'assets/(.*)'	=> 'app/themes/'.$theme_name.'/assets/$1',
		'uploads/(.*)'	=> 'app/uploads/$1'
	);
	$wp_rewrite->non_wp_rules += $new_rules;
}
add_action('generate_rewrite_rules', 'mlmi_tools_add_rewrites');

function mlmi_tools_remove_basic_styles($buttons)
{
	// remove basic styles from TinyMCE editor
	array_unshift($buttons, 'styleselect');
	if (($key = array_search('formatselect', $buttons)) !== false){
		unset($buttons[$key]);
	}
	return $buttons;
}
add_filter('mce_buttons_2', 'mlmi_tools_remove_basic_styles');

// body classes
function mlmi_tools_body_class($classes = '')
{
	// add post name to body class
	global $post;
	if ($post){
		$classes[] = $post->post_name;
	}
	return $classes;
}
add_filter('body_class', 'mlmi_tools_body_class');

// clean up admin menu
function mlmi_tools_admin_menu_custom()
{
	// remove unused pages from the admin menu
	remove_submenu_page('themes.php', 'themes.php');
	remove_submenu_page('themes.php', 'theme-editor.php');
	remove_submenu_page('themes.php', 'customize.php?return='.urlencode($_SERVER['REQUEST_URI']));
	remove_submenu_page('plugins.php', 'plugin-editor.php');
	remove_submenu_page('options-general.php', 'loco-translate-settings-legacy');
}
add_action('admin_menu', 'mlmi_tools_admin_menu_custom', 9999);

// admin bar
function mlmi_tools_admin_bar()
{
	return false;
}
add_filter('show_admin_bar', 'mlmi_tools_admin_bar');

// block dashboard for non-admins
function mlmi_tools_block_admin_for_users()
{
	if (!current_user_can('administrator') && (!defined('DOING_AJAX') || !DOING_AJAX)){
		wp_redirect(home_url()); exit;
	}
}
add_action('admin_init', 'mlmi_tools_block_admin_for_users');

// custom excerpt ellipsis
function mlmi_tools_excerpt_more($more)
{
	return '...';
}
add_filter('excerpt_more', 'mlmi_tools_excerpt_more');

/**
*	Functions and helpers
*/

// print preformatted text
function pre($variable)
{
	echo '<pre>';
	print_r($variable);
	echo '</pre>';
}

// log functions
function mlmi_log($message, $type = 'echo')
{
	if (!defined('LOG_FILE')){
		return false;
	}
	if ($type == 'print' || $type == 'echo'){
		error_log(date('[Y-m-d H:i:s] '). $message.PHP_EOL, 3, LOG_FILE);
	} else if ($type == 'pre' || $type == "print_r"){
		ob_start();
		print_r($message);
   		$message = ob_get_contents();
   		ob_end_clean();
		error_log(date('[Y-m-d H:i:s] '). $message.PHP_EOL, 3, LOG_FILE);
	} else if ($type == 'dump' || $type == 'var_dump'){
		ob_start();
		var_dump($message);
   		$message = ob_get_contents();
   		ob_end_clean();
		error_log(date('[Y-m-d H:i:s] '). $message.PHP_EOL, 3, LOG_FILE);
	}
}
function mlmi_log_pre($message){
	log($message, 'pre');
}
function mlmi_log_dump($message){
	log($message, 'dump');
}

// is login page
function is_login_page()
{
	return in_array($GLOBALS['pagenow'], array('wp-login.php', 'wp-register.php'));
}

// do not check for updates
if (is_admin()){
	include_once(ABSPATH.'wp-admin/includes/plugin.php');
	if (is_plugin_active('disable-updates/disable-updates.php')){
		remove_action('admin_init', '_maybe_update_core');
		remove_action('admin_init', '_maybe_update_plugins');
		remove_action('admin_init', '_maybe_update_themes');
	}
}
?>

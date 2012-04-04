<?php
/**
 * @package Urtak
 * @version 0.9.9.2
 */

/*
Plugin Name: Urtak
Plugin URI: http://wordpress.org/extend/plugins/urtak-for-wordpress/
Description: Urtak is collaborative polling — everyone can ask questions. It's easy to engage a great number of people in a structured conversation that produces thousands of responses.
Author: Kunal Shah, Urtak
Version: 0.9.9.2
Author URI: https://github.com/urtak/urtak_for_wordpress
*/

require_once dirname( __FILE__ ) . '/urtak-php/urtak_api.php';
include_once dirname( __FILE__ ) . '/widget.php';
include_once dirname( __FILE__ ) . '/config.php';
include_once dirname( __FILE__ ) . '/urtak_questions.php';

// CONSTANTS
define ('URTAK_PLUGINDIR'   , WP_PLUGIN_URL . '/' . str_replace (basename (__FILE__), '', plugin_basename (__FILE__)));
define ('URTAK_IMAGESDIR'   , URTAK_PLUGINDIR . "images");

// HOOKS

// Add the urtak menu to the new post page
add_action( 'admin_init', 'urtak_menus', 1);

// Add a link to the configuration within the plugins tab
add_action( 'admin_menu', 'urtak_config_page' );

// Add a filter to display the _Urtak Widget_ (if the embed option is not manual)
add_filter( 'the_content', 'add_urtak_widget' );

// Add a link to the configuration within the plugins link
add_filter( 'plugin_action_links', 'urtak_plugin_action_links', 10, 2 );

// Expose API calls from the New/Edit Post Page
add_action('wp_ajax_update_urtak_question', 'update_urtak_question');
add_action('wp_ajax_create_urtak_question', 'create_urtak_question');

// Update the permalink and titles on save
add_action( 'save_post', 'update_urtak' );

// Global CSS
add_action( 'admin_head', 'urtak_css' );

// JS for Question Asking in Post Pages Only
add_action( 'admin_head', 'urtak_js' );
add_action( 'admin_head-post-new.php', 'urtak_post_js' );
add_action( 'admin_head-post.php',     'urtak_post_js' );

// JS for Urtak Configuration
add_action( 'admin_head-plugins_page_urtak-config', 'urtak_config_js' );

register_activation_hook(  __FILE__, 'urtak_activate');
register_deactivation_hook(__FILE__, 'urtak_deactivate');

// Nag Users
urtak_admin_warnings();

// register sidebar widget
wp_register_sidebar_widget(
  'urtak_widget',
  'Urtak',
  'make_urtak_widget',
  array(
    'description' => 'Urtak.com widget, make sure you visit the configuration page!'
  )
);

function urtak_api() {
  global $wp_version;

  // Build configuration array
  $configuration = array(
    'email'           => get_option('urtak_email'),
    'publication_key' => get_option('urtak_publication_key'),
    'api_key'         => get_option('urtak_api_key'),
    'api_home'        => get_option('urtak_api_home'),
    'urtak_home'      => get_option('urtak_home'),
    'client_name'     => "Urtak for Wordpress v0.9.9.2, running on WP ".$wp_version
  );
  
  // Instantiate an Urtak API Wrapper Object
  return new Urtak($configuration);
}

// LINKS, BOXES, MENUS

// when writing a post
function urtak_menus() {
  add_meta_box(
    'urtak_questions',
    'Ask Questions with Urtak',
    'urtak_questions_box',
    'post'
  );
}

// Notify users to finish the signup & configuration process...
function urtak_admin_warnings() {
  if ( !get_option('urtak_api_key') && !isset($_POST['submit']) ) {

    function urtak_warning() {
      echo "
        <div id='urtak-warning' class='updated fade'>
          <p><strong>".__('Urtak is almost ready.')."</strong>"
            .sprintf(__(' You must <a href="%1$s">visit the configuration page</a> to complete your setup.'), "plugins.php?page=urtak-config")."
          </p>
        </div>
      ";
    }
    add_action('admin_notices', 'urtak_warning');
    return;

  } elseif ( !get_option('urtak_publication_key') && !isset($_POST['submit']) ) {

    function urtak_warning() {
      echo "
        <div id='urtak-warning' class='updated fade'>
          <p><strong>".__('Urtak is almost ready.')."</strong>"
            .sprintf(__(' You must <a href="%1$s">enter your Urtak publication key</a> for it to work.'), "plugins.php?page=urtak-config")."
          </p>
        </div>
      ";
    }
    add_action('admin_notices', 'urtak_warning');
    return;

  }
}

// sidebar link under plugins
function urtak_config_page() {
  if ( function_exists('add_submenu_page') )
    add_submenu_page('plugins.php', __('Urtak Configuration'), __('Urtak Configuration'), 'manage_options', 'urtak-config', 'urtak_conf');
}

// link from the plugins home
function urtak_plugin_action_links( $links, $file ) {
  if ( $file == plugin_basename( dirname(__FILE__).'/urtak.php' ) ) {
    $links[] = '<a href="plugins.php?page=urtak-config">'.__('Settings').'</a>';
  }

  return $links;
}
// Urtak Question Box CSS
function urtak_css() {
  echo "<link rel='stylesheet' href='".URTAK_PLUGINDIR."css/urtak.css' type='text/css' media='all' />";
}

// global vars
function urtak_js() {
  echo "<script type='text/javascript'>";
  echo "  var urtak_home = '".get_option('urtak_home')."';";
  echo "</script>";
}

// Urtak Question Box JS
function urtak_post_js() {
  echo "<script type='text/javascript'>";
  echo "  var post_id = '".get_the_id()."';";
  echo "  var spinner_url = '".esc_url(admin_url('images/wpspin_light.gif'))."';";
  echo "</script>";
  echo "<script type='text/javascript' src='".URTAK_PLUGINDIR."js/placeholder.min.js'></script>";
  echo "<script type='text/javascript' src='".URTAK_PLUGINDIR."js/raphael.js'></script>";
  echo "<script type='text/javascript' src='".URTAK_PLUGINDIR."js/pie.js'></script>";
  echo "<script type='text/javascript' src='".URTAK_PLUGINDIR."js/urtak_questions.js'></script>";
}

// Urtak Configuration Page JS
function urtak_config_js() {
  echo "<script type='text/javascript' src='".URTAK_PLUGINDIR."js/urtak_config.js'></script>";
}
?>

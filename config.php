<?php
/**
 * @package Urtak
 */
// Configuring the plugin

function urtak_activate() {
  if ( !get_option('urtak_publication_key') ) {
    update_option( 'urtak_publication_key', '' );
  }
  if ( !get_option('urtak_api_key') ) {
    update_option( 'urtak_api_key', '' );
  }
  if ( !get_option('urtak_api_home') ) {
    update_option( 'urtak_api_home', 'https://urtak.com/api' );
  }
  if ( !get_option('urtak_home') ) {
    update_option( 'urtak_home', 'https://urtak.com' );
  }
  if( !get_option('urtak_automatic_create') ) {
    update_option('urtak_automatic_create', '');
  }
  if( !get_option('urtak_embed') ) {
    update_option('urtak_embed', 'after_post');
  }
  if( !get_option('urtak_embed_on_homepage') ) {
    update_option('urtak_embed_on_homepage', 'true');
  }
  if( !get_option('urtak_widget_js_url') ) {
    update_option('urtak_widget_js_url', 'https://d39v39m55yawr.cloudfront.net/assets/clr.js');
  }
  if( !get_option('urtak_widget_js_protocol') ) {
    update_option('urtak_widget_js_protocol', 'https');
  }
  // set this so if it changes in the future we don't any issues
  if( !get_option('urtak_email') ) {
    update_option('urtak_email', get_option('admin_email'));
  }
}

function urtak_deactivate() {
  delete_option('urtak_api_home');
  delete_option('urtak_home');
  delete_option('urtak_automatic_create');
  delete_option('urtak_embed');
  delete_option('urtak_embed_on_homepage');
  delete_option('urtak_widget_js_url');
  delete_option('urtak_widget_js_protocol');
  delete_option('urtak_email');
}

function urtak_ready() {
  if((get_option('urtak_api_key') != "") && (get_option('urtak_publication_key') != "")) {
    return true;
  } else {
    return false;
  }
}

function urtak_conf() {
  // check to see that the API is accessible
  if ( ! function_exists( 'curl_init' ) || ! function_exists( 'curl_exec' ) ) {
    wp_die('PHP-CURL is not installed, please run apt-get install php5-curl');
  }

  // check priviledges
  if ( function_exists('current_user_can') && !current_user_can('manage_options') ) {
    wp_die("Unauthorized");
  }

  // If this is a post request, save variables
  if ( isset($_POST['submit']) ) {
    // API Tokens and Keys
    update_option( 'urtak_email',           $_POST['urtak_email'] );
    update_option( 'urtak_api_key',         $_POST['urtak_api_key'] );
    update_option( 'urtak_publication_key', $_POST['urtak_publication_key'] );

    // Set automatic include of embed code
    if (array_key_exists('urtak_automatic_create', $_POST)) {
      update_option( 'urtak_automatic_create', 'true' );
    } else {
      update_option( 'urtak_automatic_create', 'false' );
    };

    // Widget placement
    update_option( 'urtak_embed', $_POST['urtak_embed'] );

    // Include Urtaks on the homepage
    if (array_key_exists('urtak_embed_on_homepage', $_POST)) {
      update_option( 'urtak_embed_on_homepage', 'true' );
    } else {
      update_option( 'urtak_embed_on_homepage', 'false' );
    };


    $publication_options = array(
      'domains'    => get_bloginfo('wpurl'),
      'name'       => get_bloginfo('name'),
      'platform'   => 'wordpress',
      'moderation' => $_POST['urtak_moderation'],
      'language'   => $_POST['urtak_language'],
      'theme'      => 15
    );

    // We need a publication key to identify this install and to provide security
    if (get_option('urtak_publication_key') == "") {

      // We need only one thing to create an account:
      //    1. a valid token such as an email address
      if ((get_option('urtak_api_key') == "") && get_option('urtak_email') != "") {
        $account_response = urtak_api()->create_account(array('email' => get_option('urtak_email')));

        // A successful response is 'created'
        // If the response was a failure, there are two likely reasons:
        //    1. server error
        //    2. the user already has an account! Gracefully fail, let them know they can sign in
        if($account_response->success()) {
          // Part of the response should be an API Key, so set that now.
          update_option('urtak_api_key', $account_response->body['account']['api_key']);
        }
      }

      // An API Key was either provided, create a publication automatically
      if (get_option('urtak_api_key') != "") {
        $publication_response = urtak_api()->create_publication('email', get_option('urtak_email'), $publication_options);
        // Great! lets store the key!
        if($publication_response->success()) {
          update_option('urtak_publication_key', $publication_response->body['publication']['key']);
        }
      }

    // A publication key was provided! Update the publication with our current settings
    } else {
      $publication_response = urtak_api()->update_publication(get_option('urtak_publication_key'), $publication_options);
    }
  }

  // Finally, now test to see if we can reach the publication
  if((get_option('urtak_api_key') != "") && (get_option('urtak_publication_key') != "")) {
    $test_response = urtak_api()->get_publication(get_option('urtak_publication_key'));
  }

  include_once dirname( __FILE__ ) . '/config_page.php';
}
?>

<?php
/**
 * @package Urtak
 * @version 0.9.1
 */

/*
Plugin Name: Urtak
Plugin URI: http://wordpress.org/extend/plugins/urtak/
Description: Urtak is the best way for your visitors to respond to content. Engage your users, ask and answer questions to create structured conversations and use that to better understand your audience and quite possibly... the world. After activation go to the <a href="plugins.php?page=urtak-config">Urtak configuration</a> page. 
Author: Kunal Shah
Version: 0.9.1
Author URI: https://urtak.com/
*/

require_once dirname( __FILE__ ) . '/urtak_api.php';

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

// Global CSS
add_action( 'admin_head', 'urtak_css' );

// JS for Question Asking in Post Pages Only
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
    'publication_key' => get_option('urtak_publication_key'),
    'api_key'         => get_option('urtak_api_key'),
    'api_home'        => get_option('urtak_api_home'),
    'urtak_home'      => get_option('urtak_home'),
    'client_name'     => "Urtak for Wordpress v0.9.1, running on WP ".$wp_version
  );
  
  // Instantiate an Urtak API Wrapper Object
  return new Urtak($configuration);
}

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
    update_option('urtak_automatic_create', 'true');
  }
  if( !get_option('urtak_automatic_moderation') ) {
    update_option('urtak_automatic_moderation', 'true');
  }
  if( !get_option('urtak_embed') ) {
    update_option('urtak_embed', 'after_post');
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
  delete_option('urtak_automatic_moderation');
  delete_option('urtak_embed');
  delete_option('urtak_widget_js_url');
  delete_option('urtak_widget_js_protocol');
  delete_option('urtak_email');
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

// notify users to finish signup process or if API is broke ass
function urtak_admin_warnings() {
  global $display_error;
  if ( !get_option('urtak_api_key') && !isset($_POST['submit']) ) {
    function urtak_warning() {
      echo "
      <div id='urtak-warning' class='updated fade'><p><strong>".__('Urtak is almost ready.')."</strong> ".sprintf(__('You must <a href="%1$s">enter your Urtak API key</a> for it to work.'), "plugins.php?page=urtak-config")."</p></div>
      ";
    }
    add_action('admin_notices', 'urtak_warning');
    return;
  } elseif ( !get_option('urtak_publication_key') && !isset($_POST['submit']) ) {
    function urtak_warning() {
      echo "
      <div id='urtak-warning' class='updated fade'><p><strong>".__('Urtak is almost ready.')."</strong> ".sprintf(__('You must <a href="%1$s">enter your Urtak publication key</a> for it to work.'), "plugins.php?page=urtak-config")."</p></div>
      ";
    }
    add_action('admin_notices', 'urtak_warning');
    return;
  } elseif ( $display_error ) {
    function urtak_api_error() {
      echo "
      <div id='urtak-api-error' class='updated fade'><p>
        <strong>".__('Urtak error!')."</strong>".$display_error."</p></div>
      ";
    }
    add_action('admin_notices', 'urtak_api_error');
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

// WIDGET!
function make_urtak_widget() {
  if(should_show_widget()) {
    echo(urtak_widget());
  }
}

function add_urtak_widget( $content ) {
  if((get_option('urtak_embed') == 'after_post') && should_show_widget()) {
    return $content.urtak_widget();
  } else {
    return $content;
  }
}

function should_show_widget() {
  // to show the widget, this must be a single post.
  // The widget should not be explictly hidden, or if there is no post-meta, then check the default (handles legacy)
  $meta_value = get_post_meta(get_the_id(),'_show_urtak',true);
  
  if ((is_single()) && (($meta_value == 'show')) || (($meta_value == '') && (get_option('urtak_automatic_create') == 'true'))) {
    return true;
  } else {
    return false;
  }
}

function urtak_widget() {
  $widget = "";
  $widget.= "<script src=".get_option('urtak_widget_js_url')." type='text/javascript'></script>\n";
  $widget.= "<div\n";
  $widget.= "data-auto-urtak-key       = '".get_option('urtak_publication_key')."'\n";
  $widget.= "data-post-title           = '".get_the_title()."'\n";
  $widget.= "data-post-id              = '".get_the_ID()."'\n";
  $widget.= "data-post-permalink       = '".get_permalink()."'\n";
  $widget.= "data-post-created         = '".get_the_date()."'\n";
  $widget.= "data-auto-urtak-protocol  = '".get_option('urtak_widget_js_protocol')."'\n";
  $widget.= ">";
  $widget.= "</div>";
  return $widget;
}

// PAGES

// UI for Urtak Configuration
function urtak_conf() {
  // Form submitted, lets set things up!
  if ( isset($_POST['submit']) ) {

    // check priviledges
    if ( function_exists('current_user_can') && !current_user_can('manage_options') ) {
      wp_die("Unauthorized");
    }

    // --------------------------------------------------------------------
    // Set Sensible Defaults
    //      The activation hook should set the defaults. WP Admins may 
    //      override them here.
    // --------------------------------------------------------------------

    // API Tokens and Keys
    update_option( 'urtak_email',           $_POST['urtak_email'] );
    update_option( 'urtak_api_key',         $_POST['urtak_api_key'] );
    update_option( 'urtak_publication_key', $_POST['urtak_publication_key'] );

    // Set Automatic Creation
    if (array_key_exists('urtak_automatic_create', $_POST)) {
      update_option( 'urtak_automatic_create', 'true' );
    } else {
      update_option( 'urtak_automatic_create', 'false' );
    };

    // Set Community vs. Publisher Moderation
    if (array_key_exists('urtak_automatic_moderation', $_POST)) {
      update_option( 'urtak_automatic_moderation', 'true' );
    } else {
      update_option( 'urtak_automatic_moderation', 'false' );
    };

    update_option( 'urtak_embed', $_POST['urtak_embed'] );

    // --------------------------------------------------------------------
    // For Urtak.com Development Staff
    //      So we can test the plugin against staging. Don't burn yourself.
    // --------------------------------------------------------------------

    // Set API Endpoints
    update_option( 'urtak_home',                $_POST['urtak_home'] );
    update_option( 'urtak_api_home',            $_POST['urtak_api_home'] );

    // Set Widget Endpoints
    update_option( 'urtak_widget_js_url',       $_POST['urtak_widget_js_url'] );
    update_option( 'urtak_widget_js_protocol',  $_POST['urtak_widget_js_protocol'] );
    
    // --------------------------------------------------------------------
    // Automatic Signup
    //      We've captured all their preferences. If something is missing
    //      and we have enough information, we can sign the user up anyway
    // --------------------------------------------------------------------

    // If they haven't set a Publication Key, we are go for launch
    if (get_option('urtak_publication_key') == "") {

      // Okay, they've given us an email address at least, so lets make an account
      if ((get_option('urtak_api_key') == "") && get_option('urtak_email') != "") {
        $account_response = urtak_api()->create_account(array('email' => get_option('urtak_email')));
        
        // Ballerific! Let's create the publication now
        if($account_response->success()) {
          // We should have received an API Key in the response
          update_option('urtak_api_key', $account_response->body['account']['api_key']);

        // Nothing is going this gals way. We're so sorry.
        } else {
          // gracefail, this very likely means the user _already_ has an account under this email address
          // so then we just need to give em a modal or something so they can sign in and get their keys
        }
      }

      // Oh, we have an API Key? We should be able to find them
      if (get_option('urtak_api_key') != "") {

        $publication_response = urtak_api()->create_publication('email', get_option('urtak_email'), array(
          'domains'    => get_bloginfo('wpurl'),
          'name'       => get_bloginfo('name')
        ));
        
        // Great! lets store the key!
        if($publication_response->success()) {
          // We should have received an API Key in the response
          update_option('urtak_publication_key', $publication_response->body['publication']['key']);

        // Nothing is going this gals way. We're so sorry.
        } else {
          // gracefail
        }
      
      // No email, No API Key, No Pub Key... then we can't do anything! shucks! people still say shucks, right? right?
      } else {
        // ask nicely for an email address...
      }
    }

    if((get_option('urtak_api_key') != "") && (get_option('urtak_publication_key') != "")) {
      // FINALLY! Let's give her a test drive!
      $test_response = urtak_api()->get_urtaks(array());
    }
  }
?>

  <h1>Urtak Configuration</h1>

  <div style="margin: 0 0 0 20px; font-size:16px; width:600px; line-height:20px;" class="message">
    <?php
      if (isset($_POST['submit'])) {
        echo("<span style='font-size:13px;'>saved your settings... now let&apos;s make sure everything works...</span><br /><br />");

        if(isset($test_response)) {
          if($test_response->success()) {
            echo "<span style='color:green; font-weight:bold;'>Connected to Urtak successfully!</span>";
          } else {
            echo "<span style='color:red; font-weight:bold;'>Error! ".$test_response->error()."</span>";
          }
        } else {
          if(isset($publication_response)) {
            echo "<span style='color:red; font-weight:bold;'>Error setting up your publication!</span><br />";
            echo $publication_response->error();
          } else {
            if(get_option('urtak_email') == "") {
              echo "<span style='color:red; font-weight:bold;'>Enter your email address</span><br />";
              echo "Please enter your email address to proceed. If you already have an Urtak account, use the email address you registered with.<br /><br />";
            } else {
              echo "<span style='color:red; font-weight:bold;'>Hello old friend, get your keys!</span><br />";
              echo "Looks like you already have an account with Urtak.<br />";
            }
            echo "<a href='".get_option('urtak_home')."/api_keys' id='get_urtak_keys_popup'>Click here to sign into your account</a> and get your keys.";
          }
        }
      } elseif((get_option('urtak_api_key') == "") || (get_option('urtak_publication_key') == "")) {
    ?>
    <div style="font-size:13px; background-color: #FFFFE0; border:1px solid #E6DB55; padding:10px;">
      <strong>You&apos;ll need a free Urtak account to get started.</strong><br />
      Just make sure the email address below is correct and we&apos;ll sign you up.
      <br /><br />
      If you already have an account,
      <?php echo "<a href='".get_option('urtak_home')."/api_keys' id='get_urtak_keys_popup'>click here to sign in</a> and get your API key."; ?>
    </div>
    <?php
      }
    ?>
  </div>
  
  <form action="" method="post" id="urtak-configuration" style="margin: 0 0 0 20px; width: 600px; ">
    <h3><label for="urtak_email"><?php _e('Email Address'); ?></label></h3>
    <input id="urtak_email" name="urtak_email" type="text" size="40" value="<?php echo get_option('urtak_email') ?>" /> 

    <h3><label for="urtak_api_key"><?php _e('API Key (optional)'); ?></label></h3>
    <p>
      If you have one, you can get it here. Otherwise we&apos;ll create one for you.
    </p>
    <input id="urtak_api_key" name="urtak_api_key" type="text" size="40" maxlength="40" value="<?php echo get_option('urtak_api_key'); ?>" /> 

    <p class="submit" style="float:right;">
      <input type="submit" class="button-primary" name="submit" value="<?php _e('Let&apos;s Get Started &raquo;'); ?>" />
    </p>

    <div style="clear:both;">
      <a href="#" onclick="jQuery('#urtak_extra_options').toggle(); return false;"><h3>Toggle Advanced Options</h3></a>
    </div>

    <div id="urtak_extra_options" style="display:none;">
      <p>
        You do not need to set anything below. We will automatically create your publication key if you do not have one you&apos;d like to use.
      </p>

      <h3><label for="urtak_embed"><?php _e('Embedding Options'); ?></label></h3>
      <p>Choose where you&apos;d like to place the widget.</p>
      <input style="vertical-align:baseline;" name="urtak_embed" type="radio" value="after_post" <?php if(get_option('urtak_embed') == 'after_post') { echo("checked='checked'"); } ?>/> 
      <span style="font-size:14px;padding:5px;">After My Post</span><br />
      <p>The Urtak widget will be placed right after your content</p>

      <input style="vertical-align:baseline;" name="urtak_embed" type="radio" value="manual" <?php if(get_option('urtak_embed') == 'manual') { echo("checked='checked'"); } ?>/> 
      <span style="font-size:14px;padding:5px;">Widget / Manually</span><br />
      <p>
        If your WordPress theme is widgetized, you can drag and drop Urtak into an area on your template.
        Or, call the following php function in your template:
        <pre>
<?php echo(htmlspecialchars('<?php make_urtak_widget(); ?>')); ?>
        </pre>
      </p>

      <h3><label for="urtak_publication_key"><?php _e('Publication Key'); ?></label></h3>
      <p>
        Since you can use Urtak on your WordPress as well as other <a href="http://developer.urtak.com/#platforms" target="_blank">platforms</a>, you&apos;ll need a publication key for each so we know how to store your urtaks when you 
        visit the <a href="https://urtak.com/dashboard" target="_blank">dashboard</a>.
      </p>
      <p>
        Again, this field is optional since we will automatically generate a publication key for you if you do not have one you&apos;d like 
        to use.
      </p>
      <input id="urtak_publication_key" name="urtak_publication_key" type="text" size="40" maxlength="40" value="<?php echo get_option('urtak_publication_key'); ?>" /> 

      <h3><label for="urtak_automatic_post"><?php _e('Include Urtak by Default?'); ?></label></h3>
      <p>
        Just like comments, Urtak should be useful on every post. From the Add New / Edit Post screen you&apos;ll have the option to exclude 
        Urtak on an individual post. We highly recommend you keep this enabled.
      </p>
      <input id="urtak_automatic_post" name="urtak_automatic_create" type="checkbox" value="true" <?php echo check_add_urtak(); ?>/> Place an Urtak on each of my posts

      <h3><label for="urtak_automatic_moderation"><?php _e('Community Moderation?'); ?></label></h3>
      <p>
        We want Urtak to be very simple for you. Instead of requiring you to approve each and every question, we let the community 
        determine whether or not a question gets removed through the "Don&apos;t Care" option. The more the button gets hit, the less 
        frequently the question gets shown until it dissapears entirely. Click <a href="https://urtak.com/faq#moderation" target="_blank">here to learn more</a> about community moderation. 
      </p>
      <p>
        Opting out of community moderation means you&apos;ll either receive emails for each question or can moderate through the Add New / Edit Post interface.
      </p>
      <input id="urtak_automatic_moderation" name="urtak_automatic_moderation" type="checkbox" value="true" <?php echo check_moderate_urtak(); ?> /> Instead of sending me emails, let the community moderate for me

      <div id="urtak_development_options" style="display:none;">
      <h3>Developer Settings</h3>

      You do *not* need to change these settings. This is a convenience for plugin development and testing only.

      <h3>Urtak API Home</h3>
      <input id="urtak_api_home" name="urtak_api_home" type="text" size="40" maxlength="40" value="<?php echo get_option('urtak_api_home'); ?>" /> 

      <h3>Urtak.com Home</h3>
      <input id="urtak_home" name="urtak_home" type="text" size="40" maxlength="40" value="<?php echo get_option('urtak_home'); ?>" /> 

      <h3>Widget Javascript URL</h3>
      <input id="urtak_home" name="urtak_widget_js_url" type="text" size="40" value="<?php echo get_option('urtak_widget_js_url'); ?>" /> 

      <h3>Widget Javascript Protocol</h3>
      <input id="urtak_home" name="urtak_widget_js_protocol" type="text" size="40" maxlength="5" value="<?php echo get_option('urtak_widget_js_protocol'); ?>" /> 

      </div>
      <br />
      <a href="#" onclick="jQuery('#urtak_development_options').toggle(); return false;" style="font-size:10px;">toggle development options</a>
      <p class="submit" style="float:right;">
        <input type="submit" class="button-primary" name="submit" value="<?php _e('Save Options &raquo;'); ?>" />
      </p>
    </div>
  </form>

<?php
}

function create_urtak_question() {
  $post_id    = $_POST['post_id'];
  $questions  = array();
  $moderation = (array_key_exists('moderation', $_POST) ? 'community' : 'publisher');

  // set the basics
  $urtak = array(
    'post_id'     => $post_id,
    'permalink'   => get_permalink($post_id),
    'title'       => get_the_title($post_id),
    'moderation'  => $moderation
  );
  
  if($_POST['question_text'] != "") {
    $questions = array(0 => array('text' => $_POST['question_text']));
  }

  // Don't trust the WP post meta, always do an API call to see if an Urtak already exists
  $lookup = urtak_api()->get_urtak( 'post_id' , $post_id , array() );
  
  // if an Urtak exists, just create the question
  if($lookup->success()) {
    $urtak_id = $lookup->body['urtak']['id'];

    // Check to see if moderation settings were changed
    if($lookup->body['urtak']['moderation'] != $moderation) {
      $update = urtak_api()->update_urtak('id', array('id' => $urtak_id, 'moderation' => $moderation));
    }

    $response = urtak_api()->create_urtak_questions('id', $urtak_id, $questions);
    if($response->success()) {
      $question_id = array_pop(explode("/", $response->headers["Location"]));
    }

  // otherwise, create the Urtak and the question
  } else {
    $response = urtak_api()->create_urtak($urtak, $questions);

    if($response->success()) {
      $question    = array_pop(array_pop($response->body["urtak"]["questions"]));
      $urtak_id    = array_pop(explode("/", $response->headers["Location"]));
      $question_id = $question['id'];
    }
  }
  
  if(isset($question_id)) {
    // Set this key so the widget code is displayed explicitly
    update_post_meta( $post_id, '_show_urtak' , 'show' );
  
    // Do a third lookup for the question itself and return that JSON
    echo urtak_api()->get_urtak_question( 'id' , $urtak_id , $question_id )->raw_body;
  
  } else {
    echo $response->raw_body;
  }
  
  // this is (silly, but) required to return a proper result
  die(); 
}

function update_urtak_question() {
  $action   = $_POST['status'];
  $post_id  = $_POST['post_id'];

  preg_match('/\d+/', $_POST['question_id'], $question_match);
  $question_id = $question_match[0];
  
  if($action == 'reject') {
    $response = urtak_api()->reject_urtak_question( 'post_id' , $post_id , $question_id );
  } elseif($action == 'approve') {
    $response = urtak_api()->approve_urtak_question( 'post_id' , $post_id , $question_id );
  } elseif($action == 'mark_as_spam') {
    $response = urtak_api()->spam_urtak_question( 'post_id' , $post_id , $question_id );
  } elseif($action == 'mark_as_ham') {
    $response = urtak_api()->ham_urtak_question( 'post_id' , $post_id , $question_id );
  }
  
  if($response->success()) {
    echo "success";
  } else {
    echo $response->error();
  }
  
  die(); // this is (silly, but) required to return a proper result
}

// UI for Question Asking (post pages)
function urtak_questions_box( $post ) {

  // Query the API for a Questions + Urtak
  $response = urtak_api()->get_urtak_questions( 'post_id' , get_the_id() , array() );

  // Successful query
  if($response->success()) {
    // This is now explicit, so use the API value
    $check_urtak_automatic_moderation = (($response->body['questions']['urtak']['moderation'] == 'community') ? 'checked' : '');

  // Not found
  } else {
    // Use the WordPress default
    $check_urtak_automatic_moderation = check_moderate_urtak();
  }

  // The user may explicity ask to hide/show an Urtak
  $show_urtak = get_post_meta( get_the_id() , '_show_urtak' , true );

  // This was explicity set by the User or API
  if($show_urtak == 'show') {
    $check_add_urtak_to_post = 'checked';

  // Again, explicity set
  } elseif($show_urtak == 'hide') {
    $check_add_urtak_to_post = '';

  // No value, so use the WordPress default
  } else {
    $check_add_urtak_to_post = check_add_urtak();
  }

  // Use nonce for verification
  wp_nonce_field( plugin_basename( __FILE__ ), 'urtak_questions' );
?>

<span id='urtak_help'>
  Kick off the conversation by asking a <em>yes/no</em> question below
</span>

<?php if((!($response->not_found())) && ($response->failure())) { 
  echo "There was a problem connecting to Urtak ".$response->error();
} ?>

<div id='urtak_post_buttons'>
  <input type='button' class='button-secondary' name='more-options' value='Options' id='toggle_urtak_post_options'>
</div>

<div id='urtak_post_options'>
  <div id='urtak_post_options_content' style='display:none;'>
    <div class="urtak_post_option">
      <input type="checkbox" name="urtak_add_to_post" value="true" <?php echo($check_add_urtak_to_post); ?>> 
      I want Urtak enabled on this post
    </div>
    <div class="urtak_post_option">
      <input type="checkbox" name="urtak_community_moderation_on_post" value="true" <?php echo($check_urtak_automatic_moderation); ?>> 
      Let the community moderate questions for me
    </div>
  </div>
</div>

<input type='text' id='urtak_question_text' name='urtak_question_text' value='' size='60' />
<input type='button' class='button-primary' name='ask_question' value='Ask Question' onclick='ask_urtak_question(); return false;'>
<span id="urtak_ajax_spinner">
  <img src="<?php echo esc_url( admin_url( 'images/wpspin_light.gif' ) ); ?>" /> Adding Question...
</span>

<h4 id="urtak_recent_questions_title">Recently Asked Questions</h4>
<div id="urtak_recent_questions"></div>

<script type="text/javascript">
  initialize_urtak_questions(<?php echo($response->raw_body)?>);
</script>

<?php
}

function check_add_urtak() {
  return (get_option('urtak_automatic_create') == 'true') ? 'checked' : '';
}

function check_moderate_urtak() {
  return (get_option('urtak_automatic_moderation') == 'true') ? 'checked' : '';
}

// Urtak Question Box CSS
function urtak_css() {
?>
<style type='text/css'>
  #urtak_help, .urtak_post_option {
    font-size: 13px;
  }
  .urtak_post_option{
    margin:0 0 5px 0;
  }
  
  #urtak_ajax_spinner{
    float:right;
    display:none;
  }

  .urtak_question{
    clear:both;
    margin:0 0 2px 0;
  }

  #urtak_post_buttons {
    margin: 0 10px 5px 0;
    font-size: 14px;
    text-decoration:none;
    float:right;
  }

  #urtak_question_text {
    font-size: 16px;
    color:#333;
    margin:5px 0 5px 0px;
    line-height:22px;
  }
  .urtak_question_text {
    font-size: 13px;
    color:#333;
    margin:5px 0 5px 5px;
    line-height:22px;
  }
  
  #urtak_recent_questions { padding-bottom: 6px;}
  
  div.urtak_approved.urtak_on {
    background-image:url('<?php echo URTAK_IMAGESDIR ?>/qs-approved-active.png');
    height:22px;
    width:22px;
    float:left;
  }
  div.urtak_approved.urtak_off {
    background-image:url('<?php echo URTAK_IMAGESDIR ?>/qs-approved-inactive.png');
    height:22px;
    width:22px;
    float:left;
    cursor: pointer;
  }
  div.urtak_rejected.urtak_on {
    background-image:url('<?php echo URTAK_IMAGESDIR ?>/qs-rejected-active.png');
    height:22px;
    width:22px;
    float:left;
  }
  div.urtak_rejected.urtak_off {
    background-image:url('<?php echo URTAK_IMAGESDIR ?>/qs-rejected-inactive.png');
    height:22px;
    width:22px;
    float:left;
    cursor: pointer;
  }
  div.urtak_spam.urtak_on {
    background-image:url('<?php echo URTAK_IMAGESDIR ?>/qs-spam-active.png');
    height:22px;
    width:22px;
    float:left;
  }
  div.urtak_spam.urtak_off {
    background-image:url('<?php echo URTAK_IMAGESDIR ?>/qs-spam-inactive.png');
    height:22px;
    width:22px;
    float:left;
    cursor: pointer;
  }

  #urtak_post_options, #urtak_post_options_content {
    padding-top: 5px; 
    margin: 0;
  }
</style>
<?php
}

// Urtak Question Box JS
function urtak_post_js() {
?>
<script type='text/javascript'>
  function ask_urtak_question() {
    var question_input = jQuery('#urtak_question_text');

    var data = {
      action: 'create_urtak_question',
      question_text: question_input.attr('value'),
      post_id: <?php echo get_the_id(); ?>  
    };

    jQuery.post(ajaxurl, data, function(response) {
      try {
        response = jQuery.parseJSON(response);

        if(response['question']) {
          add_urtak_question(response['question']);
        } else {
          if(response['error']) {
            alert(response['error']['message']);
          }
        }
      } catch(error) {
        alert(response);
      }
    });

    question_input.attr('value', '');
    question_input.focus();
  }

  function initialize_urtak_questions(response) {    
    if(response['questions'] && response['questions']['question']) {
      var questions = response['questions']['question'];
      
      jQuery.each(questions, function(index, question) {
        add_urtak_question(question);
      });
    }
  }
  
  function add_urtak_question(question) {
    jQuery("#urtak_recent_questions").append(jQuery("<div>")
      .attr("id", "urtak-question-"+question['id'])
      .addClass("urtak_question")
      .append(
        jQuery("<div>")
          .addClass("urtak_approved")
          .addClass((question['status'] == 'approved') ? 'urtak_on' : 'urtak_off')
          .attr("data-action", "approve")
      )
      .append(
        jQuery("<div>")
          .addClass("urtak_rejected")
          .addClass((question['status'] == 'rejected') ? 'urtak_on' : 'urtak_off')
          .attr("data-action", "reject")
      )
      .append(
        jQuery("<div>")
          .addClass("urtak_spam")
          .addClass((question['status'] == 'spam') ? 'urtak_on' : 'urtak_off')
          .attr("data-action", "mark_as_spam")
      )
      .append(
        jQuery("<span>")
          .addClass("urtak_question_text")
          .addClass("urtak_on")
          .html(question['text'])
      )
    );
  }

  jQuery(document).ready(function() {
    jQuery('#toggle_urtak_post_options').click(function() {
      jQuery('#urtak_post_options_content').fadeToggle('fast');
      return false;
    });
    
    jQuery('#urtak_ajax_spinner').ajaxStart(function(){
      jQuery(this).show();
    });
    jQuery('#urtak_ajax_spinner').ajaxStop(function(){
      jQuery(this).hide();
    });
    
    jQuery('.urtak_off').live('click', function() {
      var question = jQuery(this);
      
      var data = {
        action: 'update_urtak_question',
        question_id: jQuery(this).parents('.urtak_question').attr('id'),
        post_id: <?php echo get_the_id(); ?>,
        status: jQuery(this).attr('data-action')
      };
     
      jQuery.post(ajaxurl, data, function(response) {
        if(response == 'success') {
          // if success, just update the icons
          question.removeClass('urtak_off');
          question.addClass('urtak_on');
          question.siblings().each(function() {
            jQuery(this).removeClass('urtak_on');
            jQuery(this).addClass('urtak_off');
          });
        } else {
          // return the error
          alert(response);
        }
      });
    });
    
  });

</script>
<?php
}

// Urtak Configuration Page JS
function urtak_config_js() {
?>
<script type="text/javascript">
  jQuery(document).ready(function() {
    jQuery('#get_urtak_keys_popup').click(function(ev) {
      window.open("<?php echo get_option('urtak_home'); ?>/api_keys?from=wordpress",
      "Get Your Urtak Account and API Keys","width=800,height=400");
      ev.preventDefault();
      return false;
    });
  });
</script>
<?php
}

?>

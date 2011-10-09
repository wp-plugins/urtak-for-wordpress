<?php
/**
 * @package Urtak
 * @version 0.9.6
 */

/*
Plugin Name: Urtak
Plugin URI: http://wordpress.org/extend/plugins/urtak/
Description: Urtak gathers your users’ opinions by enabling them to ask and answer questions about your content. Letting your audience actively contribute by generating content with questions results in spending more time on site and sharing your content with their friends.
Author: Kunal Shah
Version: 0.9.6
Author URI: https://urtak.com/
*/

require_once dirname( __FILE__ ) . '/urtak-php/urtak_api.php';

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
    'publication_key' => get_option('urtak_publication_key'),
    'api_key'         => get_option('urtak_api_key'),
    'api_home'        => get_option('urtak_api_home'),
    'urtak_home'      => get_option('urtak_home'),
    'client_name'     => "Urtak for Wordpress v0.9.6, running on WP ".$wp_version
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
  if( !get_option('urtak_moderation') ) {
    update_option('urtak_moderation', 'community');
  }
  if( !get_option('urtak_language') ) {
    update_option('urtak_language', 'en');
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
  delete_option('urtak_moderation');
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
      <div id='urtak-warning' class='updated fade'><p><strong>".__('Urtak is almost ready.')."</strong> ".sprintf(__('You must <a href="%1$s">visit the configuration page</a> to complete your setup.'), "plugins.php?page=urtak-config")."</p></div>
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

    // Set Language
    update_option( 'urtak_language', $_POST['urtak_language'] );

    // Set Automatic Creation
    if (array_key_exists('urtak_automatic_create', $_POST)) {
      update_option( 'urtak_automatic_create', 'true' );
    } else {
      update_option( 'urtak_automatic_create', 'false' );
    };

    // Set Community vs. Publisher Moderation
    if (array_key_exists('urtak_moderation', $_POST)) {
      update_option( 'urtak_moderation', 'community' );
    } else {
      update_option( 'urtak_moderation', 'publisher' );
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
          'name'       => get_bloginfo('name'),
          'moderation' => get_option('urtak_moderation'),
          'language'   => get_option('urtak_language')
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

      <h3><label for="urtak_language"><?php _e('Language'); ?></label></h3>
      <p>
        Choose the language used in the widget your users see.
      </p>
      <input style="vertical-align:baseline;" id="urtak_language" name="urtak_language" type="radio" value="en" <?php if(get_option('urtak_language') == 'en') {echo("checked='checked'");} ?>/> 
      <span style="font-size:14px;padding:5px;">English</span><br />
      <input style="vertical-align:baseline;" id="urtak_language" name="urtak_language" type="radio" value="es" <?php if(get_option('urtak_language') == 'es') {echo("checked='checked'");} ?>/>
      <span style="font-size:14px;padding:5px;">Español</span><br />

      <h3><label for="urtak_automatic_post"><?php _e('Include Urtak by Default?'); ?></label></h3>
      <p>
        Just like comments, Urtak should be useful on every post. From the Add New / Edit Post screen you&apos;ll have the option to exclude 
        Urtak on an individual post. We highly recommend you keep this enabled.
      </p>
      <input id="urtak_automatic_post" name="urtak_automatic_create" type="checkbox" value="true" <?php echo check_add_urtak(); ?>/> Place an Urtak on each of my posts

      <h3><label for="urtak_moderation"><?php _e('Community Moderation?'); ?></label></h3>
      <p>
        We want Urtak to be very simple for you. Instead of requiring you to approve each and every question, we let the community 
        determine whether or not a question gets removed through the "Don&apos;t Care" option. The more the button gets hit, the less 
        frequently the question gets shown until it dissapears entirely. Click <a href="https://urtak.com/faq#moderation" target="_blank">here to learn more</a> about community moderation. 
      </p>
      <p>
        Opting out of community moderation means you&apos;ll either receive emails for each question or can moderate through the Add New / Edit Post interface.
      </p>
      <input id="urtak_moderation" name="urtak_moderation" type="checkbox" value="true" <?php echo check_moderate_urtak(); ?> /> Instead of sending me emails, let the community moderate for me

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
  
  if($_POST['question_text'] != "") {
    $questions = array(0 => array('text' => stripslashes($_POST['question_text'])));
  }
  
  // Don't trust the WP post meta, always do an API call to see if an Urtak already exists
  $lookup = urtak_api()->get_urtak( 'post_id' , $post_id , array() );
  
  // if an Urtak exists, just create the question
  if($lookup->success()) {
    $urtak_id = $lookup->body['urtak']['id'];

    $response = urtak_api()->create_urtak_questions('id', $urtak_id, $questions);
    if($response->success()) {
      $question_id = array_pop(explode("/", $response->headers["Location"]));
    }

  // otherwise, create the Urtak and the question
  } else {
    // set the basics
    $urtak = array(
      'post_id'     => $post_id,
      'permalink'   => get_permalink($post_id),
      'title'       => get_the_title($post_id),
    );

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
  // If we should be able to connec, than query the API for this post's Questions and Urtak
  if(urtak_ready()) {
    $response = urtak_api()->get_urtak_questions( 'post_id' , get_the_id() , array() );

    // 404'ing is okay, but any other error in 400-500 is bad, so display it
    if((!($response->not_found())) && ($response->failure())) { 
      echo "<strong>Sorry, but there was a problem connecting with Urtak ".$response->error()."</strong><br />";
    }
?>

<input type='text' id='urtak_question_text' name='urtak_question_text' placeholder='Write a yes or no question, then click ask' size='60' />
<input type='button' class='button-primary' name='ask_question' value='Ask Question' onclick='ask_urtak_question(); return false;'>
<input type='button' class='button-secondary' name='help' value='Help' id='toggle_urtak_help'>

<div id='urtak_post_help' style='display:none;'>
  <h4>Question Status</h4>
  <p>A question waiting for approval will appear like this:</p>

  <div class="spanner"></div>
  <div class="urtak_approved urtak_off"></div>
  <div class="urtak_rejected urtak_off"></div>
  <div class="urtak_spam urtak_off"></div>
  <span class="urtak_question_text">Do you like dogs?</span>
  <br />

  <p>Click the desired status to approve, reject, or mark a question as spam:</p>

  <div class="spanner"></div>
  <div class="urtak_approved urtak_on"></div>
  <div class="urtak_rejected urtak_off"></div>
  <div class="urtak_spam urtak_off"></div>
  <span class="urtak_question_text">Do you like dogs?</span>
  <br />

  <div class="spanner"></div>
  <div class="urtak_approved urtak_off"></div>
  <div class="urtak_rejected urtak_on"></div>
  <div class="urtak_spam urtak_off"></div>
  <span class="urtak_question_text">What kind of dog do you have?</span>
  <br />

  <div class="spanner"></div>
  <div class="urtak_approved urtak_off"></div>
  <div class="urtak_rejected urtak_off"></div>
  <div class="urtak_spam urtak_on"></div>
  <span class="urtak_question_text">Call 1-800-BUY-A-DOG today</span>
  <br />

  <p>Approved questions will enter into the Urtak stream and bad questions will be removed. Don’t like a question anymore? You can return to this interface anytime to adjust a question’s status.</p>

  <h4>How to Ask Question</h4>
  <p>
  To obtain the best results, be sure to ask questions that can be answered with Yes, No, or Don’t Care. The more questions that your audience answers, the better insight you’ll get so keep them answering questions by making each one simple and engaging. Being overly lengthy and detailed can cause people to quit, likewise vague questions may have a similar outcome. As always, the don’t care option will help you ask the right questions by letting your audience show you what questions they’re interested in.
  </p>
</div>

<h4 id="urtak_recent_questions_title">Recently Asked Questions</h4>
<div id="urtak_recent_questions"></div>
<div id="urtak_pagination_links"></div>

<script type="text/javascript">
  initialize_urtak_questions(<?php echo($response->raw_body)?>);
</script>

<?php
function urtak_results($l) {
  if($l['rel']=='results') { 
    return true;
  } else {
    return false;
  }
}
    // Display link to full dashboard results.
    if($response->success() && $response->body['questions']['urtak']) {
      $link = array_pop(array_filter($response->body['questions']['urtak']['link'], 'urtak_results'));
      echo("<br /><em>View and analyze the full results via <a href=".$link['href']." target='_blank'>your dashboard</a>.</em>");
    }

  // Urtak is not ready, display a warning
  } else {
    echo "<p><strong>".__('Urtak is almost ready.')."</strong> ".sprintf(__('You must <a href="%1$s">visit the configuration page</a> to complete your setup.'), "plugins.php?page=urtak-config")."</p>";
  }
}

function check_add_urtak() {
  return (get_option('urtak_automatic_create') == 'true') ? 'checked' : '';
}

function urtak_ready() {
  if((get_option('urtak_api_key') != "") && (get_option('urtak_publication_key') != "")) {
    return true;
  } else {
    return false;
  }
}

function check_moderate_urtak() {
  return (get_option('urtak_moderation') == 'community') ? 'checked' : '';
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

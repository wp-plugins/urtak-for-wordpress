<?php
/**
 * @package Urtak
 * @version 0.0.1
 */
/*
Plugin Name: Urtak
Plugin URI: http://wordpress.org/extend/plugins/urtak/
Description: Urtak is the best way for your visitors to respond to content. Engage your users, ask and answer questions to create structured conversations and use that to better understand your audience and quite possibly... the world. After activation go to the <a href="plugins.php?page=urtak-config">Urtak configuration</a> page. 
Author: Kunal Shah
Version: 0.0.1
Author URI: http://urtak.com/
License: GPL2
*/

/*  Copyright 2011  Kunal Shah  (email : kunal@urtak.com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as 
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
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

// Add a link to the configuration within the plugins link
add_filter( 'plugin_action_links', 'urtak_plugin_action_links', 10, 2 );

// Expose API calls from the New/Edit Post Page
add_action('wp_ajax_update_urtak_question', 'update_urtak_question');

// After a post is saved, create and populate the urtak
add_action( 'save_post', 'urtak_create_and_add_questions' );

// CSS for Question Asking
add_action( 'admin_head', 'urtak_css' );

// JS for Question Asking
add_action( 'admin_head', 'urtak_js' );

register_activation_hook( __FILE__, 'urtak_activate' );

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
  // Build configuration array
  $configuration = array(
    'publication_key' => get_option('urtak_publication_key'),
    'api_key'         => get_option('urtak_api_key'),
    'api_home'        => get_option('urtak_api_home'),
    'urtak_home'      => get_option('urtak_home'),
    'client_name'     => "Urtak for Wordpress v0.1, running on WP ".$wp_version
  );
  
  // Instantiate an Urtak API Wrapper Object
  return new Urtak($configuration);
}

function urtak_activate() {
  if ( !get_option('urtak_api_home') ) {
    update_option( 'urtak_api_home', 'https://api.urtak.com' );
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
  if( !get_option('urtak_widget_js_url') ) {
    update_option('urtak_widget_js_url', 'https://d39v39m55yawr.cloudfront.net/assets/clr.js');
  }
  if( !get_option('urtak_widget_js_protocol') ) {
    update_option('urtak_widget_js_protocol', 'https');
  }
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
  global $urtak_response;

  if ( !get_option('urtak_publication_key') && !isset($_POST['submit']) ) {
    function urtak_warning() {
      echo "
      <div id='urtak-warning' class='updated fade'><p><strong>".__('Urtak is almost ready.')."</strong> ".sprintf(__('You must <a href="%1$s">enter your urtak API key</a> for it to work.'), "plugins.php?page=urtak-key-config")."</p></div>
      ";
    }
    add_action('admin_notices', 'urtak_warning');
    return;
  } elseif ( isset($urtak_response) && array_key_exists("error", $urtak_response) ) {
    function urtak_api_error() {
      echo "
      <div id='urtak-api-error' class='updated fade'><p>
        <strong>".__('Urtak error!')."</strong>".$urtak_response["error"]["message"]."</p></div>
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
  if (is_single()) {
    // unless explicitly hidden, make the widget
    if(get_post_meta( get_the_id() , '_show_urtak' , true ) != 'hide') {
?>
  <script src="<?php echo get_option('urtak_widget_js_url'); ?>" type="text/javascript"></script>
  <div
    data-auto-urtak-key       = "<?php echo get_option('urtak_publication_key'); ?>"
    data-post-title           = "<?php echo the_title(); ?>"
    data-post-id              = "<?php echo the_ID(); ?>"
    data-post-permalink       = "<?php echo the_permalink(); ?>"
    data-post-created         = "<?php echo get_the_date(); ?>"
    data-auto-urtak-protocol  = "<?php echo get_option('urtak_widget_js_protocol'); ?>"
  >
  </div>
<?php
    };
  };
}

// PAGES

// UI for Urtak Configuration
function urtak_conf() {
  // check if this a post request
  if ( isset($_POST['submit']) ) {
    // check priviledges if so
    if ( function_exists('current_user_can') && !current_user_can('manage_options') )
      die(__('Cheatin&#8217; uh?'));

    // then update settings

    // set the publication key
    update_option( 'urtak_publication_key', $_POST['urtak_publication_key'] );

    // set the api key
    update_option( 'urtak_api_key', $_POST['urtak_api_key'] );

    // now overwrite
    update_option( 'urtak_api_key', $_POST['urtak_api_key'] );

    // the default behavior should be to include automatically so users can ask questions
    if (array_key_exists('urtak_automatic_create', $_POST)) {
      update_option( 'urtak_automatic_create', 'true' );
    } else {
      update_option( 'urtak_automatic_create', 'false' );
    };

    if (array_key_exists('urtak_automatic_moderation', $_POST)) {
      update_option( 'urtak_automatic_moderation', 'true' );
    } else {
      update_option( 'urtak_automatic_moderation', 'false' );
    };

    // API Endpoints
    update_option( 'urtak_api_home', $_POST['urtak_api_home'] );
    update_option( 'urtak_home', $_POST['urtak_home'] );
    update_option( 'urtak_widget_js_url', $_POST['urtak_widget_js_url'] );
    update_option( 'urtak_widget_js_protocol', $_POST['urtak_widget_js_protocol'] );
    
    $test_response = urtak_api()->get_urtaks(array());

?>
  <div style="margin: 0 0 0 20px; font-size:16px; font-weight:bold;">
    Settings Updated!<br /><br />

<?php
  if($test_response->success()) {
    echo "<span style='color:green;'>Connected to Urtak successfully!</span>";
  } else {
    echo "<span style='color:red;'>Error! ".$test_response->error()."</span>";
  }
?>

  </div>
<?php
  }

  $check_add_urtak_to_post = (get_option('urtak_automatic_create') == 'true') ? 'checked' : '';
  $check_urtak_automatic_moderation = (get_option('urtak_automatic_moderation') == 'true') ? 'checked' : '';

?>
  <form action="" method="post" id="urtak-configuration" style="margin: 0 0 0 20px; width: 600px; ">
    <h2>Configure Urtak for Your Publication</h2>
    
    To get your keys, <a href="https://urtak.com?signup=wordpress" target="_blank">sign up for Urtak here</a>. If you've already signed up, but don't know your keys, <a href="https://urtak.com/dashboard/#keys" target="_blank">click here</a>.

    <h3><label for="urtak_publication_key"><?php _e('Publication Key'); ?></label></h3>
    <p>
      Since you can use Urtak on your WordPress as well as other <a href="https://developer.urtak.com/platforms" target="_blank">platforms</a>, you&apos;ll need a publication key for each so we know how to store your urtaks when you 
      visit the <a href="https://urtak.com/dashboard" target="_blank">dashboard</a>.
    </p>
    <input id="urtak_publication_key" name="urtak_publication_key" type="text" size="40" maxlength="40" value="<?php echo get_option('urtak_publication_key'); ?>" /> 

    <h3><label for="urtak_api_key"><?php _e('Secret (API) Key'); ?></label></h3>
    <p>
      For security, all messages from the admin panel to Urtak are transmitted over SSL and further authenticated with your 
      API Key. Don&apos;t share it or make it public! Your API Key can also be <a href="https://urtak.com/dashboard#keys" target="_blank">found at the dashboard</a>.
    </p>
    <input id="urtak_api_key" name="urtak_api_key" type="text" size="40" maxlength="40" value="<?php echo get_option('urtak_api_key'); ?>" /> 

    <h3><label for="urtak_automatic_post"><?php _e('Automatically Enable Urtak?'); ?></label></h3>
    <p>
      Just like comments, Urtak is useful on every post. From the Add New / Edit Post screen you&apos;ll have the option to exclude 
      Urtak. We highly recommend you keep this enabled.
    </p>
    <input id="urtak_automatic_post" name="urtak_automatic_create" type="checkbox" value="true" <?php echo $check_add_urtak_to_post; ?>/> Place an Urtak on my posts

    <h3><label for="urtak_automatic_moderation"><?php _e('Community Moderation?'); ?></label></h3>
    <p>
      We want Urtak to be very simple for you. Instead of requiring you to approve each and every question, we let the community 
      determine whether or not a question gets removed through the "Don&apos;t Care" option. The more the button gets hit, the less 
      frequently the question gets shown until it dissapears entirely. Click <a href="https://urtak.com/faq#moderation" target="_blank">here to learn more</a> about community moderation. 
    </p>
    <p>
      Opting out of community moderation means you&apos;ll either receive emails for each question or can moderate through the Add New / Edit Post interface.
    </p>
    <input id="urtak_automatic_moderation" name="urtak_automatic_moderation" type="checkbox" value="true" <?php echo $check_urtak_automatic_moderation; ?> /> Let the community moderate

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
    <p class="submit">
      <a href="#" onclick="jQuery('#urtak_development_options').toggle(); return false;" style="font-size:10px;">toggle development options</a>
      <input type="submit" class="button-primary" name="submit" value="<?php _e('Save Options &raquo;'); ?>" />
    </p>
  </form>

<?php
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
  $response = urtak_api()->get_urtak_questions( 'post_id' , get_the_id() , array() );

  // Query the API for this Urtak
  if($response->success()) {
    $questions = $response->body['questions']['question'];
    $check_urtak_automatic_moderation = (($response->body['questions']['urtak']['moderation'] == 'community') ? 'checked' : '');
  } else {
    $questions = array();
    $check_urtak_automatic_moderation = (get_option('urtak_automatic_moderation') == 'true' ? 'checked' : '');
  }

  $questions_count = count($questions);
  
  $show_urtak = get_post_meta( get_the_id() , '_show_urtak' , true );

  if($show_urtak == 'show') {
    // check it off
    $check_add_urtak_to_post = 'checked';
  } elseif($show_urtak == 'hide') {
    // don't say nuthin!
    $check_add_urtak_to_post = '';
  } else {
    //  not set, use master default
    $check_add_urtak_to_post = (get_option('urtak_automatic_create') == 'true') ? 'checked' : '';
  }

  // Use nonce for verification
  wp_nonce_field( plugin_basename( __FILE__ ), 'urtak_questions' );

  // The actual fields for data entry
?>
<span id='urtak_help'>
  Kick off the conversation by asking a few <em>yes/no</em> questions below
</span>

<?php if((!($response->not_found())) && ($response->failure())) { 
  echo "Urtak Error: ". $response->error();
} ?>

<div id='urtak_post_buttons'>
  <input type='button' class='button-secondary' name='more-options' value='More Options' id='toggle_urtak_post_options_link'>
  <input type='button' class='button-primary' name='new_question' value='Add Question' onclick='new_urtak_question(); return false;'>
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

<div id='urtak_questions_holder'>
<?php if($questions_count < 3) { ?>
  <div class='urtak_question'>
  <input type='text' class='urtak_question_input' name='urtak_question[][text]' value='' size='60' /><input type='button' class='button-secondary' name='remove_question' value='remove' onclick='remove_urtak_question(this); return false;'>
  </div>
<?php } ?>
</div>

<?php if($questions_count > 0) { ?>
  <div id="urtak_recent_questions">
    <div id="urtak_ajax_spinner"><img src="<?php echo esc_url( admin_url( 'images/wpspin_light.gif' ) ); ?>" /></div>
    <h4 id="urtak_recent_questions_title">Recently Asked Questions</h4>
    <?php foreach ( $questions as $question ) { ?>
      <div class='urtak_question' id='urtak-question-<?php echo $question["id"]; ?>'>
        <div class="urtak_approved <?php echo(($question["state"] == 'approved') ? 'urtak_on' : 'urtak_off'); ?>" data-action='approve'></div>
        <div class="urtak_rejected <?php echo(($question["state"] == 'rejected') ? 'urtak_on' : 'urtak_off'); ?>" data-action='reject'></div>
        <div class="urtak_spam <?php echo(($question["state"] == 'spam') ? 'urtak_on' : 'urtak_off'); ?>" data-action='mark_as_spam'></div>
        <span class='urtak_question_text'><?php echo $question["text"]; ?></span>
      </div>
    <?php } ?>
  </div>
<?php } ?>

<?php
}

// Backend for Question Asking (post pages)
function urtak_create_and_add_questions( $post_id ) {
  global $urtak_already_executed;
  if($urtak_already_executed==1) {
    return;
  }
  
  // verify if this is an auto save routine. 
  // If it is our form has not been submitted, so we dont want to do anything
  if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) 
      return;

  // verify this came from the our screen and with proper authorization,
  // because save_post can be triggered at other times
  // if ( isset( $_POST['urtak_questions'] ) && !( wp_verify_nonce( $_POST['urtak_questions'] )) )

  // Check permissions
  if ( !(isset( $_POST['post_type']) )) {
    return;
  }
  
  if ( isset( $_POST['post_type'] ) && !('post' == $_POST['post_type'])) {
    return;
  }

  // interpret the moderation setting
  $moderation = (array_key_exists('urtak_community_moderation_on_post', $_POST) ? 'community' : 'publisher');
  
  // set the basics
  $urtak = array(
    'post_id'     => get_the_id(),
    'permalink'   => get_permalink(),
    'title'       => get_the_title(),
    'created_at'  => get_the_date(),
    'moderation'  => $moderation
  );
  
  // if the key doesn't exist, we'll just have a blank array
  $questions = array();
  if (array_key_exists('urtak_question', $_POST)) {
    // but if it does, remove empties
    foreach ($_POST['urtak_question'] as $key => $value) {
      if(trim($value['text']) == "") {
        unset($_POST['urtak_question'][$key]);
      }
    }

    // empties were unset, so we can use this again
    $questions = $_POST['urtak_question'];
  }

  if (array_key_exists('urtak_add_to_post', $_POST)) {
    // don't trust the WP post meta, always do an API call to see if urtak already exists
    $lookup_response = urtak_api()->get_urtak( 'post_id' , get_the_id() , array() );

    if($lookup_response->success()) {
      // Sweet! Now make a call if settings were updated, or new questions were asked
      
      // for now, this is the only option they can change
      if($lookup_response->body['urtak']['moderation'] != $moderation) {
        // change it!
        $update_response = urtak_api()->update_urtak('id', array('id' => $lookup_response->body['urtak']['id'], 'moderation' => $moderation));

        // need to combine this with the question create (below) somehow...
        if($update_response->success()) {
          add_action('admin_notices', 'urtak_updated_notice');
        } else {
          add_action('admin_notices', 'urtak_update_error_notice');
        }
      }
      
      // don't make a call unless some questions are being added
      if(count($questions) > 0) {
        // oh, there are? indeed!
        $create_response = urtak_api()->create_urtak_questions('id', $lookup_response->body['urtak']['id'], $questions);

        // need to combine this with the urtak update somehow...
        if($create_response->success()) {
          add_action('admin_notices', 'urtak_updated_notice');
        } else {
          add_action('admin_notices', 'urtak_update_error_notice');
        }
      }

    } else {
      // Oh Snap! Okay, no problem, lets make one
      $create_response = urtak_api()->create_urtak($urtak, $questions);

      if($create_response->success()) {
        add_action('admin_notices', 'urtak_created_notice');
      } else {
        add_action('admin_notices', 'urtak_creation_error_notice');
      }
    }

    // finally, set this key so the widget code is displayed
    update_post_meta( get_the_id(), '_show_urtak' , 'show' );

  } else {
    // just leave it at this, hide the widget code, there is no need 
    // to destroy one if it already exists, etc.
    // 
    // note - this WP function overwrites the existing key
    update_post_meta( get_the_id(), '_show_urtak' , 'hide' );
  };

  $urtak_already_executed = 1;
}

function urtak_created_notice() {
  echo "<div id='urtak-notice' class='updated fade'><p><strong>".__('Urtak Created.')."</strong></p></div>";
}

function urtak_creation_error_notice() {
  echo "<div id='urtak-notice' class='error'><p><strong>".__('There was a problem creating your Urtak!')."</strong></p></div>";
}

function urtak_updated_notice() {
  echo "<div id='urtak-notice' class='updated fade'><p><strong>".__('Urtak Updated.')."</strong></p></div>";
}

function urtak_update_error_notice() {
  echo "<div id='urtak-notice' class='error'><p><strong>".__('There was a problem updating your Urtak!')."</strong></p></div>";
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

  .urtak_question_input {
  }
  
  #urtak_ajax_spinner{
    float:right;
    display:none;
  }

  .urtak_question{
    clear:both;
    margin:0 0 2px 0;
  }

  .urtak_remove {
    float:right;
    vertical-align:middle;
  }

  #urtak_key {
    font-family: 'Courier New', Courier, mono;
    font-size: 16px;
  }

  #urtak_post_buttons {
    margin: 0 10px 5px 0;
    font-size: 14px;
    text-decoration:none;
    float:right;
  }

  .urtak_question_action, .urtak_question_action a, .urtak_question_action img {
    text-decoration:none;
    border:0;
  }

  #urtak_questions_holder{
    clear:both;
    margin-right:20px;
  }

  .urtak_question_text {
    font-size: 13px;
    color:#333;
    margin:5px 0 5px 4px;
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

  #toggle_urtak_post_options_link, #toggle_urtak_post_options_link a{
    font-size:11px;
    font-weight:bold;
    text-decoration:none;
  }
</style>
<?php
}

// Urtak Question Box JS
function urtak_js() {
?>
<script type='text/javascript'>
  function new_urtak_question() {
    // grab all questions
    var questions_holder   = document.getElementById('urtak_questions_holder');

    // create the new div, input, delete icon
    var new_question_input        = document.createElement('input');
    var new_question_div          = document.createElement('div');
    var new_question_remove_button= document.createElement('input');

    // set attributes input
    new_question_input.setAttribute('type', 'text');
    new_question_input.setAttribute('size', 60);
    new_question_input.setAttribute('class','urtak_question_input');
    new_question_input.setAttribute('name','urtak_question[][text]');
  
    // set attributes on remove icon
    new_question_remove_button.setAttribute('type' , 'button');
    new_question_remove_button.setAttribute('class', 'button-secondary');
    new_question_remove_button.setAttribute('name' , 'remove_question');
    new_question_remove_button.setAttribute('value', 'remove');
    new_question_remove_button.setAttribute('onclick', 'remove_urtak_question(this); return false;');

    // set attributes on div
    new_question_div.setAttribute('class', 'urtak_question');

    // put the input and remove button the question div
    new_question_div.appendChild(new_question_input);
    new_question_div.appendChild(new_question_remove_button);

    // add the new question to the rest and focus
    questions_holder.appendChild(new_question_div);
    new_question_input.focus();
  }

  function remove_urtak_question(q_link) {
    q_link.parentNode.parentNode.removeChild(q_link.parentNode);
  }

  function toggle_urtak_post_options(q_link) {
    q_link.parentNode.parentNode.removeChild(q_link.parentNode);
  }
  
  jQuery(document).ready(function() {
    jQuery('#toggle_urtak_post_options_link').click(function() {
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

?>

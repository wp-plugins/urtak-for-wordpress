<?php
$status = 'warning';

if(isset($test_response) && $test_response->success()) {
  $check_community = ($test_response->body['publication']['moderation']=='community') ? "checked='checked'" : '';
  $check_publisher = ($test_response->body['publication']['moderation']=='publisher') ? "checked='checked'" : '';
  $check_english   = ($test_response->body['publication']['language']=='en') ? "checked='checked'" : '';
  $check_spanish   = ($test_response->body['publication']['language']=='es') ? "checked='checked'" : '';
} else {
  // defaults
  $check_community = "checked='checked'";
  $check_publisher = "";
  $check_english = "checked='checked'";
  $check_spanish = "";
}

// this worked, shout our praises.
if(isset($test_response) && $test_response->success()) {
  $status  = "success";
  $message = "<span style='font-weight:bold;'>Success! You're done!</span>";

// this failed, deny our failures. print publication response failures first
} elseif(isset($publication_response) && $publication_response->failure()) {
  $status  = "error";
  $message = "<span style='font-weight:bold;'>An error occurred setting up your publication!</span><br />
              Please check that you entered your API Key correctly and try again.";

// print test response failures next
} elseif(isset($test_response) && $test_response->server_error()) {
  $status  = "error";
  $message = "<span style='font-weight:bold;'>An error occurred while contacting Urtak!</span><br />
              Check Urtak.com and try again shortly";

// print test response failures next
} elseif(isset($test_response) && $test_response->not_found()) {
  $status  = "error";
  $message = "<span style='font-weight:bold;'>Publication not found!</span><br />
              Your account was found but your publication was not.
              If you entered your own publication key manually, please make sure it is correct.";

// print test response failures next
} elseif(isset($test_response) && $test_response->error()) {
  $status  = "error";
  $message = "<span style='font-weight:bold;'>An error occurred with the plugin's request!</span>";

// We tried to create an account and failed, they probably have an account
} elseif(isset($account_response) && $account_response->failure()) {

  if(get_option('urtak_email') == "") {
    $status  = "error";
    $message = "<span style='font-weight:bold;'>Enter your email address</span> 
                If you already have an Urtak account, use the email address you registered with.";
  } else {
    if($account_response->server_error()) {
      $status  = "error";
      $message = "<span style='font-weight:bold;'>Urtak Server Error</span><br />
                  Ouch. Embarassing. Something is going wrong at Urtak.com and we're taking a look at it now.";
    } else {
      $status  = "warning";
      $message = "<span style='font-weight:bold;'>You are already registered with Urtak!</span><br />
                  Hello old friend, looks like you already have an account with us.<br />
                  <a href='".get_option('urtak_home')."/api_keys' target='_blank'>Click here to sign in and get your keys.</a>";
    }
  }
}
?>

<div class="wrap" style="width:650px;">
  <div class="icon32" id="icon-options-general">
    <br/>
  </div>

  <h2>Urtak Configuration</h2>

  <div class="urtak-messages" id="urtak-<?php echo $status; ?>">
    <?php if (isset($_POST['submit'])) { ?>
      <p>
        <strong>Your settings were saved!</strong><br />
        Let's make sure everything works
      </p>
    <?php } else if(!isset($test_response)) { ?>
      <p>
        <strong>You&apos;ll need a free Urtak account to get started.</strong><br />
        Just make sure the email address below is correct and we&apos;ll sign you up.
      </p>
      <p>
        <strong>If you already have an account</strong><br />
        <a href='<?php echo get_option('urtak_home') ?>/api_keys' target='_blank'>Click here to sign in</a> to retrieve your keys.
      </p>
    <?php 
    }
    if (isset($message)) {
      echo("<p>".$message."</p>");
    } 
    ?>
  </div>

  <form action="" method="post" id="urtak-configuration">
    <table class="form-table">
      <tbody>
        <tr valign="top">
          <th scope="row">
            <label for="urtak_email"><?php _e('Email Address'); ?></label>
          </th>
          <td>
            <fieldset>
              <input id="urtak_email" name="urtak_email" type="text" size="40" value="<?php echo get_option('urtak_email') ?>" />
            </fieldset>
          </td>
        </tr>
        <tr valign="top">
          <th scope="row">
            <label for="urtak_api_key">API &amp; Publication Keys</label>
          </th>
          <td>
            <fieldset>
              <p>
                New users, we will create your keys for you, just click <em>Let's Get Started</em>.<br />
                If you are an existing user, please <a href='<?php echo get_option('urtak_home') ?>/api_keys' target='_blank'>click here to retrieve your keys</a>.
              </p>
              <p>
                <strong>API Key</strong> <em>this is like a password, do not share it</em><br />
                <input id="urtak_api_key" name="urtak_api_key" type="text" size="40" maxlength="40" value="<?php echo get_option('urtak_api_key'); ?>" placeholder='optional' />
              </p>
              <p>
                <strong>Publication Key</strong><br />
                <input id="urtak_publication_key" name="urtak_publication_key" type="text" size="40" maxlength="40" value="<?php echo get_option('urtak_publication_key'); ?>" placeholder='optional' /> 
              <p>
            </fieldset>
          </td>
        </tr>
        <tr valign="top">
          <th scope="row"></th>
          <td>
            <fieldset>
              <input type="submit" class="button-primary" name="submit" value="<?php _e('Let&apos;s Get Started &raquo;'); ?>" />
            </fieldset>
          </td>
        </tr>
        <tr valign="top">
          <th scope="row"><a href="#" onclick="jQuery('#urtak_extra_options').toggle(); return false;">Toggle Advanced Options</a></th>
          <td>
          </td>
        </tr>
      </tbody>
    </table>
    <table class="form-table" id="urtak_extra_options" style="display:none;">
      <tbody>
        <tr valign="top">
          <th scope="row"><label for="urtak_embed"><?php _e('Widget Placement'); ?></label></th>
          <td>
            <fieldset>
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
            </fieldset>
          </td>
        </tr>
        <tr valign="top">
          <th scope="row"><label for="urtak_embed_on_homepage"><?php _e('Include on Homepage?'); ?></label></th>
          <td>
            <fieldset>
              <p>
                By default Urtak is included with your articles listed on the homepage.
                If you only want it to appear once they click through, uncheck this box.
              </p>
              <input id="urtak_embed_on_homepage" name="urtak_embed_on_homepage" type="checkbox" value="true" <?php echo (get_option('urtak_embed_on_homepage') == 'true') ? 'checked' : '' ?>/> 
              Place Urtak with my homepage's article listing
            </fieldset>
          </td>
        </tr>
        <tr valign="top">
          <th scope="row"><label for="urtak_language"><?php _e('Language'); ?></label></th>
          <td>
            <fieldset>
              <p>
                Choose the language used in the widget your users see.
              </p>
              <input style="vertical-align:baseline;" id="urtak_language" name="urtak_language" type="radio" value="en" <?php echo($check_english); ?>/> 
              <span style="font-size:14px;padding:5px;">English</span><br />
              <input style="vertical-align:baseline;" id="urtak_language" name="urtak_language" type="radio" value="es" <?php echo($check_spanish); ?>/>
              <span style="font-size:14px;padding:5px;">Español</span><br />
            </fieldset>
          </td>
        </tr>
        <tr valign="top">
          <th scope="row"><label for="urtak_automatic_create"><?php _e('Include Urtak by Default?'); ?></label></th>
          <td>
            <fieldset>
              <p>
                Just like comments, Urtak is useful on every post and we'd love to see it there.
                We however do not think it is fair to have this enabled by default at this point and leave it up to you to determine.
                Urtak will only appear on posts where you've already asked a question first.
              </p>
              <input id="urtak_automatic_create" name="urtak_automatic_create" type="checkbox" value="true" <?php echo (get_option('urtak_automatic_create') == 'true') ? 'checked' : '' ?>/> 
              Place an Urtak on each of my posts
            </fieldset>
          </td>
        </tr>
        <tr valign="top">
          <th scope="row"><label for="urtak_moderation"><?php _e('Community Moderation?'); ?></label></th>
          <td>
            <fieldset>
              <p>
                We don't ever want Urtak to nag you. So, instead of requiring you to approve each and every question, we let the community 
                determine whether or not a question gets removed through the "Don&apos;t Care" option. The more the button gets hit, the less 
                frequently the question gets asked until it disappears entirely.
              </p>
              <p>
                Click <a href="https://urtak.com/faq#moderation" target="_blank">here to learn more</a> about community moderation.
                Of course, opting out of community moderation means you&apos;ll either receive emails for each question and/or 
                can moderate through the post interface.
              </p>
              <input style="vertical-align:baseline;" id="urtak_moderation" name="urtak_moderation" type="radio" value="community" <?php echo($check_community); ?>/> 
              <span style="font-size:14px;padding:5px;">Community (Automatic)</span><br />
              <input style="vertical-align:baseline;" id="urtak_moderation" name="urtak_moderation" type="radio" value="publisher" <?php echo($check_publisher); ?>/>
              <span style="font-size:14px;padding:5px;">Publisher (Manual)</span><br />
            </fieldset>
          </td>
        </tr>
        <tr valign="top">
          <th scope="row"></th>
          <td>
            <fieldset>
              <input type="submit" class="button-primary" name="submit" value="<?php _e('Save Options &raquo;'); ?>" />
            </fieldset>
          </td>
        </tr>
      </tbody>
    </table>
  </form>
</div>

<?php
/**
 * @package Urtak
 */
// Interface for asking Questions on a Post

// UI for Question Asking (post pages)
function urtak_questions_box( $post ) {
  // If we should be able to connec, than query the API for this post's Questions and Urtak
  if(urtak_ready()) {
    $response = urtak_api()->get_urtak_questions( 'post_id' , get_the_id() , array() );

    // 404'ing is okay, but any other error in 400-500 is bad, so display it
    if((!($response->not_found())) && ($response->failure())) { 
      echo "<span style='color:red;'>Sorry, but there was a problem connecting with Urtak ".$response->error()."</span><br />";
    }
?>

<input type='text' id='urtak_question_text' name='urtak_question_text' placeholder='Write a yes or no question, then click Ask Question' size='60' />
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
      echo("<br /><em>View and analyze the full results via <a href=".$link['href']." target='_blank'>your dashboard &raquo;</a></em>");
    }

  // Urtak is not ready, display a warning
  } else {
    echo "<p><strong>".__('Urtak is almost ready.')."</strong> ".sprintf(__('You must <a href="%1$s">visit the configuration page</a> to complete your setup.'), "plugins.php?page=urtak-config")."</p>";
  }
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

    // handles an edge case : if you don't want urtak by default but enabled it 
    // by asking a question which you later un-approve, then make sure we check 
    // the approved questions count and reset the _show_urtak meta accordingly
    if (get_option('urtak_automatic_create') != 'true') {
      if ($action != 'approve') {
        $lookup = urtak_api()->get_urtak( 'post_id' , $post_id , array() );
        if ($lookup->body['urtak']['questions']['approved']['count'] == '0') {
          update_post_meta( $post_id, '_show_urtak' , '' );
        }
      } else {
        update_post_meta( $post_id, '_show_urtak' , 'show' );
      }
    }

    echo "success";
  } else {
    echo $response->error();
  }
  
  die(); // this is (silly, but) required to return a proper result
}

function update_urtak( $post_id ) {

  if(get_post_status($post_id) != 'inherit') {
    
    // Don't trust the WP post meta, always do an API call to see if an Urtak already exists
    $lookup = urtak_api()->get_urtak( 'post_id' , $post_id , array() );
  
    // if an Urtak exists check to see if some fields are different and update accordingly
    if($lookup->success()) {

      $db_permalink = get_permalink($post_id);
      $db_title     = get_the_title($post_id);

      if(($lookup->body['urtak']['permalink'] != $db_permalink) || ($lookup->body['urtak']['title'] != $db_title)) {

        $urtak = array(
          'post_id'     => $post_id,
          'permalink'   => $db_permalink,
          'title'       => $db_title,
        );

        $response = urtak_api()->update_urtak( 'post_id' , $urtak );
      }
    }
  }
}

?>
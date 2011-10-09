function ask_urtak_question() {
  var question_input = jQuery('#urtak_question_text');

  var data = {
    action: 'create_urtak_question',
    question_text: question_input.attr('value'),
    post_id: post_id
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
    var entries   = response['questions']['entries'];
    var offset    = response['questions']['offset'];
    var remaining = entries - offset - 20;
    
    jQuery.each(questions, function(index, question) {
      add_urtak_question(question);
    });
    
    if(response['questions']['link']) {
      // var previous = jQuery.grep(response['questions']['link'], function(link , i){if(link.rel == 'previous') {return true;}});
      var next     = jQuery.grep(response['questions']['link'], function(link , i){if(link.rel == 'next') {return true;}});
      var results  = jQuery.grep(response['questions']['urtak']['link'], function(link , i){if(link.rel == 'results') {return true;}});
      
      // if(previous[0]) {
      //   
      // }
      
      if(next[0]) {
        jQuery("#urtak_pagination_links")
          .append(
            jQuery("<a>")
            .attr("href", results[0].href)
            .attr("target", "_blank")
            .html(remaining+" more questions available at your dashboard...")
          )
      }
    }
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
        .html(question['text'])
    )
    .append(
      jQuery("<div>")
        .addClass("urtak_question_info")
        .append(
          jQuery("<div>")
            .addClass("urtak_pie")
            .attr("data-pie-count-care", question['responses']['counts']['care'])
            .attr("data-pie-percent-no", question['responses']['percents']['no'])
            .attr("data-pie-percent-yes", question['responses']['percents']['yes'])
            .pie('60')
        )
        .append(
          jQuery("<table>")
            .addClass("urtak_question_responses")
            .append(
              jQuery("<tr>")
              .append(jQuery("<th>").html("Yes").addClass("urtak_question_yes"))
              .append(jQuery("<td>").html(question['responses']['counts']['yes']).addClass("urtak_question_yes"))
              .append(jQuery("<th>").html("Total"))
              .append(jQuery("<td>").html(question['responses']['counts']['total']))
            )
            .append(
              jQuery("<tr>")
              .append(jQuery("<th>").html("No").addClass("urtak_question_no"))
              .append(jQuery("<td>").html(question['responses']['counts']['no']).addClass("urtak_question_no"))
              .append(jQuery("<th>").html("Care"))
              .append(jQuery("<td>").html((question['responses']['percents']['care'] == null) ? 'n/a' : (question['responses']['percents']['care']+"%")))
            )
        )
        .append(
          jQuery("<div>")
            .addClass("urtak_question_links")
            .append(
              jQuery("<a>")
              .attr("href", jQuery.grep(question.link, function(link , i){if(link.rel == 'results') {return true;}})[0].href)
              .attr("target", "_blank")
              .html("see more analysis at Urtak")
            )
        )
    )
  );
}

jQuery(document).ready(function() {
  jQuery('#toggle_urtak_help').click(function() {
    jQuery('#urtak_post_help').fadeToggle('fast');
    return false;
  });
  
  jQuery('#urtak_question_text').ajaxStart(function(){
    jQuery(this).css("background", "url("+spinner_url+") no-repeat 99% 50%");
  });

  jQuery('#urtak_question_text').ajaxStop(function(){
    jQuery(this).css("background", "");
  });
  
  jQuery('#urtak_question_text').keypress(function(e) {
    if (e.keyCode == 13) {
      ask_urtak_question();
      return false;
    }
  });
  
  jQuery("#urtak_recent_questions").delegate(".urtak_question", "click", function(e){
    if(jQuery(e.target).is('.urtak_question')) {
      jQuery(this).toggleClass('urtak_question_hover');
      jQuery(this).children('.urtak_question_info').fadeToggle('fast');
      return false;
    }
  });
  
  jQuery('#urtak_recent_questions').delegate('.urtak_off', 'click', function(){
    var question = jQuery(this);
    
    var data = {
      action: 'update_urtak_question',
      question_id: jQuery(this).parents('.urtak_question').attr('id'),
      post_id: post_id,
      status: jQuery(this).attr('data-action')
    };
   
    jQuery.post(ajaxurl, data, function(response) {
      if(response == 'success') {
        // if success, just update the icons
        question.removeClass('urtak_off');
        question.addClass('urtak_on');
        question.siblings('[data-action]').each(function() {
          jQuery(this).removeClass('urtak_on');
          jQuery(this).addClass('urtak_off');
        });
      } else {
        // return the error
        alert(response);
      }
    });
    
    return false;
  });
});

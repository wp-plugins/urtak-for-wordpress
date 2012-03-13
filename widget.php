<?php
/**
 * @package Urtak
 */
// Outputting the widget

function make_urtak_widget() {
  if(should_show_widget()) {
    echo(urtak_widget());
  }
}

// This is the hook function that gives us the posts content and allows us to modify it
function add_urtak_widget( $content ) {
  if((get_option('urtak_embed') == 'after_post') && should_show_widget()) {
    return $content.urtak_widget();
  } else {
    return $content;
  }
}

// The widget is display under the follow conditions:
//    This is a single post or show on homepage is set (appropriate)
//    The widget's visibility was explicitly set to show
//    The widget's visibility isn't set, but automatic add is true
function should_show_widget() {
  $appropriate_page = ((is_home() || is_front_page()) && (get_option('urtak_embed_on_homepage') == 'true')) || (is_single());
  $meta_value = get_post_meta(get_the_id(),'_show_urtak',true);

  if (!$appropriate_page) {
    return false;
  } else if ($meta_value == 'show') {
    return true;
  } else if (($meta_value == '') && (get_option('urtak_automatic_create') == 'true')) {
    return true;
  } else {
    return false;
  }
}

function urtak_widget() {
  $widget = "";
  $widget.= "<script src=".get_option('urtak_widget_js_url')." type='text/javascript'></script>\n";
  $widget.= "<div\n";
  $widget.= "data-publication-key      = '".get_option('urtak_publication_key')."'\n";
  $widget.= "data-post-title           = '".get_the_title()."'\n";
  $widget.= "data-post-id              = '".get_the_ID()."'\n";
  $widget.= "data-post-permalink       = '".get_permalink()."'\n";
  $widget.= "data-post-created         = '".get_the_date()."'\n";
  $widget.= "data-auto-urtak-protocol  = '".get_option('urtak_widget_js_protocol')."'\n";
  $widget.= ">";
  $widget.= "</div>";
  return $widget;
}

?>

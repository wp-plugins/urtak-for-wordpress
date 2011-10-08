jQuery(document).ready(function() {
  jQuery('#get_urtak_keys_popup').click(function(ev) {
    window.open(urtak_home+"/api_keys?from=wordpress",
    "Get Your Urtak Account and API Keys","width=800,height=400");
    ev.preventDefault();
    return false;
  });
});

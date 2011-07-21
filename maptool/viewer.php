<?php
// This file can be included for showing a map viewer.

if (!defined("PHORUM") && !defined("PHORUM_ADMIN")) return;
?>

<!-- The Google map is loaded within an iframe. The iframe is used to -->
<!-- be able to run the map code from an HTML5 page, without having -->
<!-- to make the page on which the map is using HTML5 strict as well. -->

<div id="maptool" style="width:100%; height:100%">
  <div id="maptool_map" style="width:100%; height:100%">
  <iframe
    id="maptool_iframe"
    src="<?php print htmlspecialchars($PHORUM['maptool']['url']) ?>"
    width="100%"
    height="100%"
    marginwidth="0"
    marginheight="0"
    scrolling="no"
    frameborder="0" ></iframe>
  </div>
</div>

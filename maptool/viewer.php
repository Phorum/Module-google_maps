<?php
// This file can be included for showing a read only map on a 
// Phorum page. It's not strictly neccessary to use this script
// if an editor is needed, but it's probably the easiest way to
// arrange for one.
//
// Initialization of the map editor can be done by filling the array
// $PHORUM["maptool"] with the required setup. Arguments in this array
// are the following:
//
// center      The start map center, zoom level and map type. These are
// zoom        used to specify the map state to start with.
// type
//
// marker      If this argument is set, a single marker will be placed at the 
//             specified location.
//
// width       The width and height to use for to the map. These are
// height      given in standard CSS format (e.g. 80% or 300px). Do not
//             use percentages for defining the height.

if (!defined("PHORUM") && !defined("PHORUM_ADMIN")) return;

// Determine width and height for the iframe.
$width = isset($PHORUM["maptool"]["width"])&&$PHORUM["maptool"]["width"]!=''
       ? $PHORUM["maptool"]["width"] : "100%";
$height = isset($PHORUM["maptool"]["height"])&&$PHORUM["maptool"]["height"]!=''
       ? $PHORUM["maptool"]["height"] : "400px";

// Generate the URL to use for the map that is loaded in the iframe.
$mapurl = "{$PHORUM["http_path"]}/mods/google_maps/maptool/mapframe.php?" .
          "api_key=" . urlencode($PHORUM["mod_google_maps"]["api_key"]) .
          "&phorum_url=" .urlencode($PHORUM["http_path"]) .
          "&mode=view" .
          "&width=" . urlencode($width) .
          "&height=" . urlencode($height) .
          "&language=" . urlencode($PHORUM["language"]);
if (isset($PHORUM["DATA"]["CHARSET"]))
    $mapurl .= "&charset=" . urlencode($PHORUM["DATA"]["CHARSET"]);
foreach (array("viewtype", "center", "zoom", "type", "marker") as $k) {
     if (isset($PHORUM["maptool"][$k])) {
         $mapurl .= "&{$k}=" . urlencode($PHORUM["maptool"][$k]);
     }
}
?>

<!-- The Google map is loaded within an iframe. The iframe is used to --> 
<!-- be able to run the map code from a XHTML strict page, without having -->
<!-- to make the page on which the map is used XHTML strict as well. -->

<div id="maptool">
  <div id="maptool_map">
  <iframe
    id="maptool_iframe"
    src="<?php print htmlspecialchars($mapurl) ?>"
    width="<?php print htmlspecialchars($width) ?>"
    height="<?php print htmlspecialchars($height) ?>"
    marginwidth="0"
    marginheight="0"
    scrolling="no"
    frameborder="0" ></iframe>
  </div>
</div>

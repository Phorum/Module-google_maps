<?php
// This file can be included for showing a editable map on a 
// Phorum page. It's not strictly neccessary to use this script
// if an editor is needed, but it's probably the easiest way to
// arrange for one.
//
// The script expects that it is included within an HTML <form>.
// The following form fields will be automatically filled when
// the user edits the map:
//
// maptool_center     The longitude/latitude of the map's center position.
// maptool_marker     The longitude/latitude of a placed marker.
// maptool_zoom       The active zoom level.
// maptool_type       The active map type.
//
// Initialization of the map editor can be done by filling the array
// $PHORUM["maptool"] with the required setup. Arguments in this array
// are the following:
//
// edittype    The editing type. There are two options:
//             nomarker  The user can only change the map state
//             marker    The user can place a single marker on the map
//
// dcenter     The default start map center, zoom level and map type.
// dzoom       These are used to specify the map state to go to when
// dtype       the "Unset position" is clicked.
//
// center      The start map center, zoom level and map type. These are
// zoom        used to specify the map state to start with.
// type
//
// marker      If this argument is set, a marker will be placed at the 
//             specified location. Only for editing the map in case
//             edittype is "marker".
//
// width       The width and height to use for to the map. These are
// height      given in standard CSS format (e.g. 80% or 300px). Do not
//             use percentages for defining the height.

if (!defined("PHORUM") && !defined("PHORUM_ADMIN")) return;

// Easy access to the language strings. We might have to load the
// language file ourselves, in case this code is included from the
// admin interface.
if (! isset($PHORUM["DATA"]["LANG"]["mod_google_maps"]))
    include(dirname(__FILE__) . "/../lang/english.php");
$lang = $PHORUM["DATA"]["LANG"]["mod_google_maps"];

// Determine width and height for the iframe.
$width = isset($PHORUM["maptool"]["width"])&&$PHORUM["maptool"]["width"]!=''
       ? $PHORUM["maptool"]["width"] : "100%";
$height = isset($PHORUM["maptool"]["height"])&&$PHORUM["maptool"]["height"]!=''
       ? $PHORUM["maptool"]["height"] : "400px";

// Generate the URL to use for the map that is loaded in the iframe.
$mapurl = "{$PHORUM["http_path"]}/mods/google_maps/maptool/mapframe.php?" .
          "api_key=" . urlencode($PHORUM["mod_google_maps"]["api_key"]) .
          "&phorum_url=" .urlencode($PHORUM["http_path"]) .
          "&mode=edit" .
          "&width=" . urlencode($width) .
          "&height=" . urlencode($height) .
          "&language=" . urlencode($PHORUM["language"]);
if (isset($PHORUM["DATA"]["CHARSET"]))
    $mapurl .= "&charset=" . urlencode($PHORUM["DATA"]["CHARSET"]);
foreach (array(
  "center", "zoom", "type",
  "dcenter", "dzoom", "dtype",
  "marker", "edittype") as $k) {
     if (isset($PHORUM["maptool"][$k])) {
         $mapurl .= "&{$k}=" . urlencode($PHORUM["maptool"][$k]);
     }
}

if (!isset($PHORUM["maptool"]["edittype"]))
    $PHORUM["maptool"]["edittype"] = "marker";

?>

<!-- A surrounding div for the maptool -->
<div id="maptool">

<!-- Hidden form fields, which represent the state of the map tool -->

<input type="hidden" name="maptool_center" id="maptool_center" value="" />
<input type="hidden" name="maptool_marker" id="maptool_marker" value="" />
<input type="hidden" name="maptool_zoom"   id="maptool_zoom"   value="" />
<input type="hidden" name="maptool_type"   id="maptool_type"   value="" />

<!-- A search form, where the user can search for a locations based -->
<!-- on a textual description of the location -->

<div id="maptool_topbar" style="margin-bottom: 5px">
  <table width="100%" cellpadding="0" cellspacing="0" border="0">
    <tr>
      <td width="100%">
        <input type="text" name="search" value="" style="width:95%">
      </td>
      <td style="white-space: nowrap">
        <input type="submit" value="<?php print $lang["Search"] ?>"
         onclick="
         var mapframe = document.getElementById('maptool_iframe');
         var mapdoc = mapframe.contentWindow || mapframe.document;
         mapdoc.searchLocation(this.form.search.value);
         return false" />
        <input type="submit" value="<?php print $lang["Help"] ?>"
         onclick="alert(&quot;<?php print $lang["HelpText"] ?>&quot;); return false" />
      </td>
    </tr>
  </table>
</div>

<!-- The Google map is loaded within an iframe. The iframe is used to --> 
<!-- be able to run the map code from a XHTML strict page, without having -->
<!-- to make the page on which the map is used XHTML strict as well. -->

<div id="maptool_map" style="margin-bottom: 5px">
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

<!-- Displaying of the map state and a button to clear the active marker -->

<div id="maptool_bottombar">

  <input style="float:right" type="button" name="maptool_clear" onClick="var mapframe = document.getElementById('maptool_iframe'); var mapdoc = mapframe.contentWindow || mapframe.document; mapdoc.resetMap(); return false" value="<?php print $lang["Clear"] ?>"/>

  <div style="font-size: 9px; padding-top: 5px">
    <?php print $lang["Location"] ?>
    <span id="maptool_location_display" style="font-size: 9px">
      <?php print $lang["NoLocationSet"] ?>
    </span>
  </div>

</div>

<!-- The JavaScript code for the map editor -->
<script type="text/javascript">
//<!--

// Callback function which is called from the iframe map,
// to pass on a new map state.
function onMapToolChange(state)
{
    var display    = document.getElementById('maptool_location_display');
    var f_center   = document.getElementById('maptool_center');
    var f_marker   = document.getElementById('maptool_marker');
    var f_zoom     = document.getElementById('maptool_zoom');
    var f_type     = document.getElementById('maptool_type');

    f_center.value = state.center;
    f_marker.value = state.marker;
    f_zoom.value   = state.zoom;
    f_type.value   = state.type;

<?php $fld = $PHORUM["maptool"]["edittype"] == "marker" ? "marker" : "center"?>
    if (state.<?php print $fld ?> == '') {
        display.innerHTML = '<?php print $lang["NoLocationSet"] ?>';
    } else {
        display.innerHTML = state.<?php print $fld ?>;
    }
}
// -->
</script>

</div>

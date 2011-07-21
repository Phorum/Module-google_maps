<?php
// This file can be included for showing a editable map, where the user
// can specify a marker position or a streetview.
//
// The script expects that it is included within an HTML <form>.
// It sets up form fields, which will be automatically updated
// with the state of the map.

if (!defined("PHORUM") && !defined("PHORUM_ADMIN")) return;
?>
<!-- A surrounding div for the maptool -->
<div id="maptool" style="height: 100%; width: 100%">

  <!-- Hidden form fields, which represent the state of the map tool -->

  <input type="hidden" name="map_latitude"         id="map_latitude"         value="" />
  <input type="hidden" name="map_longitude"        id="map_longitude"        value="" />
  <input type="hidden" name="map_zoom"             id="map_zoom"             value="" />
  <input type="hidden" name="map_type"             id="map_type"             value="" />

  <?php if ($PHORUM['maptool']['type'] == 'location-editor') { ?>
  <input type="hidden" name="marker_latitude"      id="marker_latitude"      value="" />
  <input type="hidden" name="marker_longitude"     id="marker_longitude"     value="" />
  <input type="hidden" name="streetview_latitude"  id="streetview_latitude"  value="" />
  <input type="hidden" name="streetview_longitude" id="streetview_longitude" value="" />
  <input type="hidden" name="streetview_heading"   id="streetview_heading"   value="" />
  <input type="hidden" name="streetview_pitch"     id="streetview_pitch"     value="" />
  <input type="hidden" name="streetview_zoom"      id="streetview_zoom"      value="" />
  <input type="hidden" name="geoloc_country"       id="geoloc_country"       value="" />
  <input type="hidden" name="geoloc_city"          id="geoloc_city"          value="" />
  <?php } ?>

  <!-- Location search and reset -->

  <div id="maptool_topbar" style="padding-bottom: 5px">
    <table width="100%" cellpadding="0" cellspacing="0" border="0">
      <tr>
        <td width="100%"
            style="vertical-align: middle;
                   padding-right: 8px">
          <input type="text" name="search" value=""
                 style="width:100%; padding: 2px"/>
        </td>
        <td style="white-space:nowrap; vertical-align: middle">
          <input type="button" name="search_button" value="<?php print $PHORUM['maptool']['lang']["Search"] ?>"/>
        </td>
        <td style="padding-left: 5px; vertical-align: middle">
          <div id="geolocation_button" style="display:none">
            <input type="button" name="maptool_geolocation"
                   onclick="
                       var mapframe = document.getElementById('maptool_iframe');
                       var mapdoc = mapframe.contentWindow || mapframe.document;
                       mapdoc.doGeoLocation();
                       return false"
                   value="<?php print $PHORUM['maptool']['lang']['GetMyLocation'] ?>"/>
          </div>
        </td>
        <td style="padding-left: 5px; vertical-align: middle">
          <input type="button" name="maptool_clear"
                 onclick="
                     var mapframe = document.getElementById('maptool_iframe');
                     var mapdoc = mapframe.contentWindow || mapframe.document;
                     mapdoc.resetMap();
                     return false"
                 value="<?php print $PHORUM['maptool']['lang']['Clear'] ?>"/>
        </td>
      </tr>
    </table>
  </div>

  <!-- The Google map is loaded within an iframe. The iframe is used to -->
  <!-- be able to run the map code from an HTML5 page, without having -->
  <!-- to make the page on which the map is using HTML5 as well. -->

  <div id="maptool_map" style="border: 1px solid #aaa">
    <iframe
      id="maptool_iframe"
      src="<?php print htmlspecialchars($PHORUM['maptool']['url']) ?>"
      width="100%"
      height="100%"
      marginwidth="0"
      marginheight="0"
      scrolling="no"
      frameborder="0"></iframe>
  </div>

  <!-- Display the current location -->
  <?php if ($PHORUM['maptool']['type'] == 'location-editor') { ?>
  <div id="maptool_bottombar">
    <div style="padding-top: 5px">
      <?php print $PHORUM['maptool']['lang']['Location'] ?>:
      <span id="maptool_location_display">
        <?php
        if (isset($PHORUM['maptool']['geoloc_country'])) {
          print htmlspecialchars($PHORUM['maptool']['geoloc_country']);
          if (isset($PHORUM['maptool']['geoloc_city'])) {
            print ', ' . htmlspecialchars($PHORUM['maptool']['geoloc_city']);
          }
        }
        ?>
      </span>
    </div>
  </div>
  <?php } ?>

</div>
<!-- The JavaScript code for the map editor -->
<script type="text/javascript">
//<![CDATA[

// Take care of layouting the iframe to match the surrounding element.
$PJ(document).ready(function () {
  $PJ('#maptool_iframe').height(
    $PJ('#maptool').outerHeight() -
    $PJ('#maptool_topbar').outerHeight() -
    $PJ('#maptool_bottombar').outerHeight()
  );
});

function onGoogleMapReady(frame, map)
{
    if (frame.geoLocationSupported())
    {
        var geo = document.getElementById('geolocation_button');
        if (geo) geo.style.display = 'block';
    }
}

// Add events to the location search elements (text input + search button)
// Searching must be triggered by both pressing enter on the input field
// and clicking the search button.
$PJ('#maptool input[name=search_button]').click(function () {
    triggerSearch(this);
});
$PJ('#maptool input[name=search]').keypress(function (e) {
    if ((e.which && e.which == 13) || (e.keyCode && e.keyCode == 13)) {
        triggerSearch(this);
        e.preventDefault();
        return false;
    } else {
        return true;
    }
});
function triggerSearch(elt)
{
    var mapframe = document.getElementById('maptool_iframe');
    var mapdoc = mapframe.contentWindow || mapframe.document;
    mapdoc.searchLocation(elt.form.search.value);
}

// A callback function that is called from the iframe map,
// to pass on a new map state. The map state properties are copied
// into the hidden fields in this editor.
function onMapToolChange(state)
{
    var fields = [
        'map_latitude',
        'map_longitude',
        'map_zoom',
        'map_type'
    <?php if ($PHORUM['maptool']['type'] == 'location-editor') { ?>
       ,'marker_latitude',
        'marker_longitude',
        'streetview_latitude',
        'streetview_longitude',
        'streetview_heading',
        'streetview_pitch',
        'streetview_zoom',
        'geoloc_country',
        'geoloc_city'
    <?php } ?>
    ];

    for (var i = 0; i < fields.length; i++)
    {
        var name = fields[i];
        var field = document.getElementById(name); 
        field.value = state[name] !== undefined && state[name] !== null
                    ? state[name] : '';
    }

    <?php if ($PHORUM['maptool']['type'] == 'location-editor') { ?>
    // Update the location description display.
    var display = document.getElementById('maptool_location_display');
    display.innerHTML = '';
    if (state.geoloc_country) {
      var str = state.geoloc_country;
      if (state.geoloc_city) {
        str += ', ' + state.geoloc_city;
      }
      display.innerHTML = '';
      display.appendChild(document.createTextNode(str));
    }
    <?php } ?>
}
//]]>
</script>


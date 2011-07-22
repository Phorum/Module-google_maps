<?php

// This script is used to display a Google Map from within an <iframe>
// in the page. It's a generic tool that can be used for displaying 
// all required kinds of maps (editing, viewing and overviews).
//
// The script takes the following parameters for modifying the 
// behaviour of the map tool:
//
// phorum_url  The URL to the Phorum base (a.k.a. $PHORUM["http_path"]).
//
// mode        The displaying mode for the map. Either "view" or "edit".
//
// edittype    If mode is set to "edit", this parameter determines what
//             kind of editing can be done on the map. Options are:
//             nomarker  The user can only change the map state.
//             marker    The user can place a single marker on the map.
//
// viewtype    If mode is set to "view", this parameter determines what
//             kind of viewing can be done on the map. Options are:
//             marker    Display a single marker on the map.
//             plot      Plot multiple points on the map and automatically
//                       change the map to make sure that all points fit
//                       on the screen.
//
// api_key     This parameter is required and holds the API key
//             that can be used for communicating with the 
//             Google servers.
//
// dcenter     The default map center location, zoom level and map type.
// dzoom       These are used to specify the map state to go to when
// dtype       the resetMap() function is called. The type can be one
//             of: normal, satellite, hybrid
//
// center      The start map center, zoom level and map type. These are
// zoom        used to specify the map state to start with. The type can
// type        be one of: normal, satellite, hybrid 
//
// marker      If this argument is set, a marker will be placed at the 
//             specified location.
//
// charset     The charset to present data on the map in (default is "UTF-8")
//
// width       The width and height to use for to the map. These are
// height      given in standard CSS format (e.g. 80% or 400px). Do not
//             use 100% for the height.
// 
// language    The language to use. This will load the Phorum language file
//             or the english file if the given language is not found
//             (default is "english").
//
// If the map runs as a view/plot map type, it will check if the parent
// frame contains an array named "markers". If that is the case, the 
// marker descriptions in that array will be plotted on the map. Each item
// in the array is an array itself, containing the following elements:
// [0] longitude
// [1] latitude
// [2] HTML popup content (optional)
//

include(dirname(__FILE__) . "/constants.php");

// Defaults for the arguments.
$args = array(
    "phorum_url" => NULL,
    "api_key"    => NULL,
    "mode"       => "view",
    "edittype"   => "marker",
    "viewtype"   => "marker",
    "dcenter"    => "(40, -20)",
    "dzoom"      => 1,
    "dtype"      => "normal",
    "center"     => NULL,
    "zoom"       => NULL,
    "type"       => NULL,
    "charset"    => "utf-8",
    "width"      => "100%",
    "height"     => "400px",
    "language"   => "english",
    "marker"     => "",
);

// Grab args from the request.
foreach ($_REQUEST as $k => $v) {
    if (array_key_exists($k, $args)) {
        $args[$k] = $v;
    }
}

// Inherit map state from the default map state.
// If all state parameters are inherited, then remember that
// the map's start state matches the reset state.
$inherit_count = 0;
foreach (array("center", "zoom", "type") as $k) {
    if (is_null($args[$k]) || $args[$k] == '') {
        $inherit_count ++;
        $args[$k] = $args["d$k"];
    }
}
$start_is_reset = ($inherit_count == 3);

// Check if we have an api_key argument set.
if (empty($args["api_key"]))
{ ?>
    <div style="border: 1px solid red; padding: 10px">
      The module "google_maps" needs a Google API key to be able to
      communicate to the Google map servers. Please register
      a key for your server at
      <a href="http://www.google.com/apis/maps/">the Google Maps API page</a>
      and enter it at the
      <a href="<?php print htmlspecialchars($args["phorum_url"]) ?>/admin.php?module=modsettings&mod=google_maps">Googe Maps module settings page</a>.
    </div> <?php

    return;
}


// Check if all required arguments are set.
foreach ($args as $k => $v)
  if (is_null($args[$k])) die("Missing Google Map parameter \"$k\"");

// Check mode parameter.
if ($args["mode"] != "edit" && $args["mode"] != "view") {
    die ("Illegal value for parameter \"mode\"");
}

// Check edittype parameter.
if ($args["mode"] == "edit" &&
    ($args["edittype"] != "nomarker" && $args["edittype"] != "marker")) {
    die ("Illegal value for parameter \"edittype\"");
}

// Check viewtype parameter.
if ($args["mode"] == "view" &&
    ($args["viewtype"] != "plot" && $args["viewtype"] != "marker")) {
    die ("Illegal value for parameter \"viewtype\"");
}

// Check the location parameters.
if (!preg_match(REGEXP_LOCATION, $args["center"])) 
    die ("Illegal value for parameter \"center\"");
if (!preg_match(REGEXP_LOCATION, $args["dcenter"])) 
    die ("Illegal value for parameter \"dcenter\"");
if ($args["marker"] != '' && !preg_match(REGEXP_LOCATION, $args["marker"])) 
    die ("Illegal value for parameter \"marker\"");
if (!preg_match(REGEXP_ZOOM, $args["zoom"])) 
    die ("Illegal value for parameter \"zoom\"");
if (!preg_match(REGEXP_ZOOM, $args["dzoom"])) 
    die ("Illegal value for parameter \"dzoom\"");
if (!preg_match(REGEXP_TYPE, $args["type"])) 
    $args["type"] = DEFAULT_TYPE;
if (!preg_match(REGEXP_TYPE, $args["dtype"])) 
    $args["dtype"] = DEFAULT_TYPE;

// Convert map type to map type JavaScript contants.
$args["type"] = "G_".strtoupper($args["type"])."_MAP";
$args["dtype"] = "G_".strtoupper($args["dtype"])."_MAP";

// Load the language support.
$language = basename($args["language"]); 
if (! file_exists("../lang/{$language}.php")) $language = "english";
include("../lang/{$language}.php");
$lang = $PHORUM["DATA"]["LANG"]["mod_google_maps"];

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
  "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
  <head>
    <meta http-equiv="content-type" content="text/html; charset=<?php print htmlspecialchars($args["charset"]) ?>"/>
    <title>Google Map interface</title>
    <script src="http://maps.google.com/maps?file=api&amp;v=2&amp;key=<?php htmlspecialchars(print $args["api_key"]) ?>" type="text/javascript"></script>

    <script type="text/javascript">
    //<![CDATA[

    // -----------------------------------------------------------------
    // Initialize variables
    // -----------------------------------------------------------------

    // Object storage.
<?php if ($args["mode"] == 'edit') { ?>
    var geocoder;
  <?php if ($args["edittype"] == 'marker') { ?>
    var marker = null;
  <?php } ?>
<?php } ?>

<?php if ($args["marker"] != '') { ?> 
    var start_marker = new GLatLng<?php print $args["marker"] ?>;
<?php } else { ?>
    var start_marker = null;
<?php } ?>

    var map;

    var start_is_reset = <?php print $start_is_reset ? 'true' : 'false' ?>;

    var start_point = new GLatLng<?php print $args["center"] ?>;
    var start_zoom  = <?php print $args["zoom"] ?> 
    var start_type  = <?php print $args["type"]; ?>;

<?php if ($args["mode"] == 'view' && $args["viewtype"] == 'plot') { ?>
    var bounds = new GLatLngBounds();

    var mgr;
    var markers;

    var ploticon = new GIcon();
        ploticon.image            = "<?php print htmlspecialchars($args["phorum_url"])."/mods/google_maps/maptool/icons/marker.png"?>";
        ploticon.shadow            = "<?php print htmlspecialchars($args["phorum_url"])."/mods/google_maps/maptool/icons/marker_shadow.png"?>";
        ploticon.iconSize         = new GSize(32,32);
        ploticon.iconAnchor       = new GPoint(16,16);
        ploticon.infoWindowAnchor = new GPoint(32,16);
<?php } ?>

<?php if ($args["mode"] == 'edit') { ?>
    var reset_point = new GLatLng<?php print $args["dcenter"] ?>;
    var reset_zoom  = <?php print $args["dzoom"] ?> 
    var reset_type  = <?php print $args["dtype"]; ?>;
<?php } ?>

    // -----------------------------------------------------------------
    // Initialize the Google Map
    // -----------------------------------------------------------------

    function load()
    {
      if (GBrowserIsCompatible())
      {
        // Create the map object.
        map = new GMap2(document.getElementById("map"));
        map.setCenter(start_point, start_zoom);

        // Add map controls.
        map.addControl(new GSmallMapControl());
        map.addControl(new GMapTypeControl());
        map.addControl(new GScaleControl());

<?php if ($args["mode"] == 'view' && $args["viewtype"] == 'plot') { ?>
        // Create marker manager for plotting on the map.
        mgr = new GMarkerManager(map);
<?php } ?>

<?php if ($args["mode"] == 'edit') { ?>
        // Create the geocoder object.
        geocoder = new GClientGeocoder();
<?php } ?>

<?php if ($args["mode"] != 'edit') { ?>
        // Enable doubleclick for zooming to the next level.
        map.enableDoubleClickZoom();
<?php } ?>

        // Move to the start location.
        startMap();

<?php if ($args["mode"] == 'edit') { ?>
        // Setup event handlers.
        GEvent.addListener(map, "moveend", function() {
            raiseMapToolChangeEvent();
        });
        GEvent.addListener(map, "zoomend", function() {
            raiseMapToolChangeEvent();
        });
        GEvent.addListener(map, "maptypechanged", function() {
            raiseMapToolChangeEvent();
        });
    <?php if ($args["edittype"] == 'marker') { ?>
        // If the user clicks the map, then move or place the marker.
        GEvent.addListener(map, "click", function(overlay, point) {
            placeEditMarker(point);
            raiseMapToolChangeEvent();
        });
    <?php } ?>
<?php } ?>

        raiseGoogleMapReadyEvent();
      } else {
          document.write('<div style="border: 1px solid red; padding: 10px">');
          document.write("<?php print $lang["IncompatibleBrowser"] ?>");
          document.write('</div>');
      }
    }

    // -----------------------------------------------------------------
    // Functions for changing the state of the map
    // -----------------------------------------------------------------

    // Set the map to the given type. The type is the string as
    // return by map.getCurrentMapType().
    function setMapType(type)
    {
        var types = map.getMapTypes();
        for (var i = 0; types[i]; i++) {
          if (types[i].getName() == type.getName()) {
            map.setMapType(types[i]);
            break;
          }
        }
    }

    // Get the map type as a normalized value which we can use
    // to store the map state. We have to do this, because the 
    // map name can differ because of Google's localization.
    function getMapType()
    {
        var type  = map.getCurrentMapType();

        if (type.getName() == G_SATELLITE_MAP.getName()) {
            return "satellite";
        } else if (type.getName() == G_HYBRID_MAP.getName()) {
            return "hybrid";
        } else {
            return "normal";
        }
    }

    // Put a marker on the map, based on coordinates.
    function placeMarkerAtCoordinates(lat,lng,info)
    {
        var p = new GLatLng(lat, lng);
        return placeMarker(p, info);
    }

    // Put a marker on the map, based on a GLatLng point object.
    function placeMarker(point, info) {

<?php if ($args["mode"] == 'view' && $args["viewtype"] == 'plot') { ?>
        bounds.extend(point);
        var m = new GMarker(point, ploticon);
<?php } else { ?>
        var m = new GMarker(point);
<?php } ?>

        if (info) {
            GEvent.addListener(m, "click", function() {
               m.openInfoWindowHtml(info);
            });
        }

        map.addOverlay(m);
        return m;
    }

    // Go to the map start position.
    function startMap()
    {
        map.setCenter(start_point, start_zoom);
        setMapType(start_type);
        <?php if ($args["mode"] == "edit") { ?>
        if (start_marker) placeEditMarker(start_marker);
        <?php } else { ?>
        if (start_marker) placeMarker(start_marker);
        <?php } ?>

        // Make sure the marker start position is visible.
        if (start_marker && !map.getBounds().contains(start_marker)) {
            map.panTo(start_marker);
        }

<?php if ($args["mode"] == 'edit') { ?>
        raiseMapToolChangeEvent(start_is_reset);
<?php } ?>
    }

    // Go to the map reset position.
    function resetMap()
    {
<?php if ($args["mode"] == 'edit' && $args["edittype"] == 'marker') { ?>
        if (marker) {
            map.removeOverlay(marker);
            marker = null;
        }
<?php } ?>
        map.setCenter(reset_point, reset_zoom);
        setMapType(reset_type);

        raiseMapToolChangeEvent(true);
    }

<?php if ($args["mode"] == 'edit') { ?>
    // Search for a textual location on the map, using Google's
    // geocoder to translate the text into map coordinates.
    function searchLocation(description)
    {
        geocoder.getLatLng(description, function(point) {
          if (! point) {
            alert('<?php print ($lang["NoSearchResults"].$lang["HelpText"]) ?>');
          } else {
            map.panTo(point);
  <?php if ($args["edittype"] == 'marker') { ?>
            placeEditMarker(point);
  <?php } ?>
            raiseMapToolChangeEvent();
          }
        });
    }

  <?php if ($args["edittype"] == 'marker') { ?>
    // Put an editable marker on the map.
    function placeEditMarker(point)
    {
        // Create a marker in case there is no marker yet.
        if (! marker)
        {
            marker = new GMarker(point, {draggable: true});

            // If the user drags the marker, then fire a change event.
            GEvent.addListener(marker, "dragend", function() {
                raiseMapToolChangeEvent();
            });

            map.addOverlay(marker);
        }
        // If we already have a marker, then move it to the new location.
        else
        {
            marker.setPoint(point);
        }
    }
  <?php } ?>
<?php } ?>

<?php if ($args["mode"] == 'view' && $args["viewtype"] == 'plot') { ?>
    function placePlotMarker(lat, lng, info)
    {
        var p = new GLatLng(lat, lng);
        var newm = new GMarker(p, ploticon);

        if (info) {
            GEvent.addListener(newm, "click", function() {
                newm.openInfoWindowHtml(info);
            });
        }

        return newm;
    }

    function fitPlotPoints()
    {
        map.setCenter(bounds.getCenter());
        var zoomlevel = map.getBoundsZoomLevel(bounds);
        if (zoomlevel > 2) zoomlevel --;
        map.setZoom(zoomlevel);
    }
<?php } ?>

    // -----------------------------------------------------------------
    // Functions for communication with the parent window
    // -----------------------------------------------------------------

    function raiseGoogleMapReadyEvent()
    {
<?php if ($args["mode"] == 'view' && $args["viewtype"] == 'plot') { ?>
        // First see if our parent provides markers to plot. If yes,
        // then place them on the map.
        if (parent.markers)
        {
            var e = null;
            var w = null;
            var n = null;
            var s = null;

            var i = 0;
            markers = new Array();
            for (var i = 0; parent.markers[i]; i++)
            {
                var m = parent.markers[i];
                // Keep track of the bounds.
                if (n == null || n < m[1]) n = m[1];
                if (e == null || e < m[0]) e = m[0];
                if (s == null || s > m[1]) s = m[1];
                if (w == null || w > m[0]) w = m[0];

                // Create the marker object.
                markers[i] = placePlotMarker(m[0], m[1], m[2]);
            }

            // Compute and set map center and zoom level.
            var bounds = new GLatLngBounds(new GLatLng(w,s), new GLatLng(e,n));
            var zoomlevel = map.getBoundsZoomLevel(bounds);
            map.setCenter(bounds.getCenter(), zoomlevel);

            // Display the markers.
            mgr.addMarkers(markers, 1);
            mgr.refresh();
        }
<?php } ?>

        // Callback to notify the parent that the map is ready.
        if (parent.onGoogleMapReady) {
            parent.onGoogleMapReady(map);
        }
    }

<?php if ($args["mode"] == 'edit') { ?>

    // A simple object to store state information.
    function GoogleMapState()
    {
        this.center   = null;
        this.zoom     = null;
        this.type     = null;
        this.marker   = null;
    }

    // Callback to the parent window.
    function raiseMapToolChangeEvent(reset)
    {
        if (parent.onMapToolChange) {
            var state = new GoogleMapState();
            if (reset) {
                state.center   = '';
                state.marker   = '';
                state.zoom     = '';
                state.type     = '';
            } else {
                state.center   = map.getCenter();
                <?php if ($args["edittype"] == 'marker') { ?>
                    state.marker   = marker ? marker.getPoint() : '';
                <?php } else { ?>
                    state.marker   = '';
                <?php } ?>
                state.zoom     = map.getZoom();
                state.type     = getMapType();
            }
            parent.onMapToolChange(state);
            state = null;
        }
    }
<?php } ?>
    //]]>
    </script>
  </head>

  <body onload="load()" onunload="GUnload()">

    <noscript>
      <div style="border: 1px solid red; padding: 10px">
      <?php print $lang["IncompatibleBrowser"] ?>
      </div>
    </noscript>

    <div id="map" style="
        width: <?php print htmlspecialchars($args["width"]) ?>;
        height: <?php print htmlspecialchars($args["height"]) ?>"></div>

  </body>

</html>

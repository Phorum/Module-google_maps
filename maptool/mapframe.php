<?php

// This script is used to display a Google Map from within an <iframe>
// in the page. It's a generic tool that can be used for displaying
// all required kinds of maps (editing, viewing and overviews).
//
// The script takes the following parameters for modifying the
// behaviour of the map tool:
//
// type        The type of map. This is one of:
//             - location-editor
//             - map-editor
//             - viewer
//             - plotter
//
// <other>     Other parameters are used for setting the map state
//             (longitude, latitude, zoom, marker, streetview, etc.)
//

// Defaults for the arguments.
$args = array(
    "type"                 => "viewer",
    "reset_latitude"       => 40,
    "reset_longitude"      => -20,
    "reset_zoom"           => 1,
    "reset_type"           => 'roadmap',
    "map_latitude"         => '',
    "map_longitude"        => '',
    "map_zoom"             => '',
    "map_type"             => '',
    "marker_latitude"      => '',
    "marker_longitude"     => '',
    "streetview_latitude"  => '',
    "streetview_longitude" => '',
    "streetview_zoom"      => '',
    "streetview_heading"   => '',
    "streetview_pitch"     => '',
    "geoloc_country"       => '',
    "geoloc_city"          => ''
);

// Grab and merge args from the request.
foreach ($PHORUM['args'] as $k => $v) {
    if (array_key_exists($k, $args) && $v !== '') {
        $args[$k] = $v;
    }
}

// Check if all required arguments are set.
foreach ($args as $k => $v) {
    if (is_null($args[$k])) die("Missing Google Map parameter \"$k\"");
}

// Easy access to the language data.
$lang = $PHORUM["DATA"]["LANG"]["mod_google_maps"];

?>
<!DOCTYPE html>
<html>
  <head>
    <meta http-equiv="content-type" content="text/html; charset=utf-8" />
    <meta name="viewport" content="initial-scale=1.0, user-scalable=no" />
    <style type="text/css">
      html { height: 100% }
      body { height: 100%; margin: 0px; padding: 0px }
      #map { height: 100%; width: 100%; }
    </style>
    <title>Google Map interface</title>
    <script type="text/javascript" src="http://maps.google.com/maps/api/js?sensor=false&region=<?php print $lang['geocoding_lang'] ?>"></script>
    <script type="text/javascript" src="<?php print $PHORUM['http_path'] ?>/mods/google_maps/maptool/fluster/lib/Fluster2.packed.js"></script>

    <script type="text/javascript">
    //<![CDATA[

    // -----------------------------------------------------------------
    // Initialize variables
    // -----------------------------------------------------------------

    var api_language = '<?php print addslashes($lang['geocoding_lang']) ?>';

    var marker = null;

    var geoloc_city    = '<?php print addslashes($args['geoloc_city']) ?>';
    var geoloc_country = '<?php print addslashes($args['geoloc_country']) ?>';

    <?php if ($args['type'] == 'location-editor' ||
              $args['type'] == 'map-editor') { ?>
    var reset_state =
    {
        map_latitude         : <?php print addslashes($args['reset_latitude']) ?>,
        map_longitude        : <?php print addslashes($args['reset_longitude']) ?>,
        map_zoom             : <?php print addslashes($args['reset_zoom']) ?>,
        map_type             : '<?php print addslashes($args['reset_type']) ?>'
    };
<?php } ?>
    var start_state =
    {
        <?php if (isset($args['map_latitude']) &&
                        $args['map_latitude'] !== '') { ?>
        map_latitude         : <?php print addslashes($args['map_latitude']) ?>,
        map_longitude        : <?php print addslashes($args['map_longitude']) ?>,
        map_zoom             : <?php print addslashes($args['map_zoom']) ?>,
        map_type             : '<?php print addslashes($args['map_type']) ?>',
        <?php } ?>

        <?php if (isset($args['marker_latitude']) &&
                        $args['marker_latitude'] !== '') { ?>
        marker_latitude      : <?php print addslashes($args['marker_latitude']) ?>,
        marker_longitude     : <?php print addslashes($args['marker_longitude']) ?>,
        <?php } ?>

        <?php if (isset($args['streetview_latitude']) &&
                        $args['streetview_latitude'] !== '') { ?>
        streetview_latitude  : <?php print addslashes($args['streetview_latitude']) ?>,
        streetview_longitude : <?php print addslashes($args['streetview_longitude']) ?>,
        streetview_zoom      : <?php print addslashes($args['streetview_zoom']) ?>,
        streetview_heading   : <?php print addslashes($args['streetview_heading']) ?>,
        streetview_pitch     : <?php print addslashes($args['streetview_pitch']) ?>
        <?php } ?>
    };

<?php if ($args["type"] == 'plotter') { ?>
    var bounds = new google.maps.LatLngBounds();

    var ploticon = new google.maps.MarkerImage(
        "<?php print htmlspecialchars($PHORUM["http_path"]) .
                     "/mods/google_maps/maptool/marker.png" ?>",
        new google.maps.Size(32, 32),  // size
        new google.maps.Point(0, 0),   // origin
        new google.maps.Point(16, 16), // anchor
        new google.maps.Size(32, 32)   // scaled size
    );

<?php } ?>

    // -----------------------------------------------------------------
    // Initialize the Google Map
    // -----------------------------------------------------------------

    var map;
    var streetview;
<?php if ($args["type"] == 'plotter') { ?>
    var fluster
<?php } ?>

    function initialize()
    {
      // Create the map object.
      map = new google.maps.Map(document.getElementById("map"), {
          <?php if ($args["type"] == 'map-editor') { ?>
          streetViewControl : false,
          <?php } ?>
          zoom      : 1,
          center    : new google.maps.LatLng(40, -20),
          mapTypeId : google.maps.MapTypeId.ROADMAP,
      });

      <?php if ($args["type"] == 'plotter') { ?>
      fluster = new Fluster2(map);
      fluster.gridSize = 20;
      <?php } ?>

      // Fetch the streetview object for this map.
      streetview = map.getStreetView();

      // Initialize the start position of the map.
      startMap();

<?php if ($args["type"] == 'location-editor' ||
          $args['type'] == 'map-editor') { ?>
      // Setup events for keeping track of edit operations.
      google.maps.event.addListener(map, 'zoom_changed', function () {
          raiseMapToolChangeEvent();
      });
      google.maps.event.addListener(map, 'dragend', function () {
          raiseMapToolChangeEvent();
      });
      google.maps.event.addListener(map, 'maptypeid_changed', function () {
          raiseMapToolChangeEvent();
      });
      google.maps.event.addListener(streetview, 'visible_changed', function () {
          raiseMapToolChangeEvent();
      });
      google.maps.event.addListener(streetview, 'pano_changed', function () {
          raiseMapToolChangeEvent();
      });
      google.maps.event.addListener(streetview, 'position_changed', function () {
          raiseMapToolChangeEvent();
      });
      google.maps.event.addListener(streetview, 'pov_changed', function () {
          raiseMapToolChangeEvent();
      });
  <?php if ($args['type'] == 'location-editor') { ?>
      google.maps.event.addListener(map, 'click', function (ev) {
          placeEditMarker(ev.latLng);
          lookupGeolocationInfo(ev.latLng);
          raiseMapToolChangeEvent();
      });
  <?php } ?>
<?php } ?>
      raiseGoogleMapReadyEvent();
    }

    // -----------------------------------------------------------------
    // Functions for changing the state of the map
    // -----------------------------------------------------------------

    // Go to the map start position.
    function startMap()
    {
        <?php if ($args['type'] == 'location-editor' ||
                  $args['type'] == 'map-editor') { ?>
        setMapState(reset_state);
        <?php } ?>
        setMapState(start_state);
    }

    <?php if ($args['type'] == 'location-editor' ||
              $args['type'] == 'map-editor') { ?>
    // Go to the map reset position.
    function resetMap()
    {
        setMapState(reset_state);
        geoloc_city    = null;
        geoloc_country = null;
        raiseMapToolChangeEvent(true);
    }
    <?php } ?>

<?php if ($args['type'] == 'location-editor' ||
          $args['type'] == 'map-editor') { ?>
    // Search for a textual location on the map, using Google's
    // geocoder to translate the text into map coordinates.
    function searchLocation(description)
    {
        setLoading(true);
        var geocoder = new google.maps.Geocoder();
        geocoder.geocode({
            address  : description,
            language : api_language
        }, function(result, status) {
            setLoading(false);
            if (status != google.maps.GeocoderStatus.OK) {
                alert(document.getElementById('maptool_msg_notfound').innerHTML);
            } else {
                var point = result[0].geometry.location;
                map.panTo(point);
                streetview.setVisible(false);
                <?php if ($args["type"] == 'location-editor') { ?>
                placeEditMarker(point);
                lookupGeolocationInfo(point);
                <?php } ?>
                raiseMapToolChangeEvent();
            }
        });
        return false;
    }

  <?php if ($args["type"] == 'location-editor') { ?>
    // Put an editable marker on the map.
    function placeEditMarker(point)
    {
        // Create a marker in case there is no marker yet.
        if (! marker)
        {
            marker = new google.maps.Marker({
                position  : point,
                map       : map,
                draggable : true
            });

            // If the user drags the marker, then fire a change event.
            google.maps.event.addListener(marker, 'dragend', function () {
                raiseMapToolChangeEvent();
                lookupGeolocationInfo(marker.getPosition());
            });
        }
        // If we already have a marker, then move it to the new location.
        else {
            marker.setPosition(point);
        }

        raiseMapToolChangeEvent();
    }

    function lookupGeolocationInfo(point)
    {
        geoloc_city = null;
        geoloc_country = null;

        var geocoder = new google.maps.Geocoder();
        geocoder.geocode({
            location : point,
            language : api_language
        }, function(result, status) {
            if (status === google.maps.GeocoderStatus.OK)
            {
                for (var i = 0; i < result.length; i++)
                {
                    var set = result[i];
                    for (var j = 0; j < set.address_components.length; j++)
                    {
                        var comp  = set.address_components[j];
                        for (var k = 0; k < comp.types.length; k++)
                        {
                            if (comp.types[k] === 'country') {
                              geoloc_country = comp.long_name;
                              break;
                            } else if (comp.types[k] === 'locality') {
                              geoloc_city    = comp.long_name;
                              break;
                            }
                        }

                        if (geoloc_country && geoloc_city) break;
                    }
                    if (geoloc_country && geoloc_city) break;
                }

                raiseMapToolChangeEvent();
            }
        });
    }
  <?php } ?>
<?php } ?>

<?php if ($args['type'] === 'plotter' || $args['type'] === 'viewer') { ?>
    // The active infowindow.
    var info_window = null;

    // Put a view marker on the map.
    function placeViewMarker(point, info)
    {
        // Create the marker.
        var marker = new google.maps.Marker({
            position  : point,
            <?php if ($args['type'] !== 'plotter') { ?>
            map       : map,
            <?php } ?>
            <?php if ($args['type'] === 'plotter') { ?>
            icon      : ploticon,
            flat      : true,
            <?php } ?>
            draggable : false
        });

        <?php if ($args['type'] === 'plotter') { ?>
        fluster.addMarker(marker);
        <?php } ?>

        // If info is provided, then setup an info window for the marker.
        if (info) {
            google.maps.event.addListener(marker, 'click', function () {
                if (info_window) {
                    info_window.close();
                }
                info_window = new google.maps.InfoWindow({
                    content     : info,
                    pixelOffset : new google.maps.Size(0, 16)
                });
                info_window.open(map, marker); 
            });
        }
    }
<?php } ?>

    // -----------------------------------------------------------------
    // Functions for communication with the parent window
    // -----------------------------------------------------------------

    function raiseGoogleMapReadyEvent()
    {
        // Callback to notify the parent that the map is ready.
        if (parent.onGoogleMapReady) {
            parent.onGoogleMapReady(window, map);
        }
    }

    /**
     * Retrieve the current map state.
     *
     * @return object
     *   An object, describing the current state of the map.
     *   This object can be passed to setMapState() to restore
     *   the map state.
     */
    function getMapState()
    {
        var state = { };

        // Compile map state.
        var center = map.getCenter();
        state.map_latitude   = center.lat();
        state.map_longitude  = center.lng();
        state.map_zoom       = map.getZoom();
        state.map_type       = map.getMapTypeId();

        // Compile streetview state.
        var sv_pos = streetview.getPosition();
        var sv_pov = streetview.getPov();
        if (streetview.getVisible()) {
            state.streetview_latitude   = sv_pos ? sv_pos.lat()   : null,
            state.streetview_longitude  = sv_pos ? sv_pos.lng()   : null,
            state.streetview_heading    = sv_pos ? sv_pov.heading : null,
            state.streetview_pitch      = sv_pos ? sv_pov.pitch   : null,
            state.streetview_zoom       = sv_pos ? sv_pov.zoom    : null
        } else {
            state.streetview_latitude   = null,
            state.streetview_longitude  = null,
            state.streetview_heading    = null,
            state.streetview_pitch      = null,
            state.streetview_zoom       = null
        }

        // Compile marker state.
        var marker_pos = marker ? marker.getPosition() : null;
        if (marker && marker_pos) {
            state.marker_latitude   = marker_pos.lat(),
            state.marker_longitude  = marker_pos.lng()
        } else {
            state.marker_latitude   = null,
            state.marker_longitude  = null
        }

        // Compile geolocation info state.
        state.geoloc_country = geoloc_country;
        state.geoloc_city    = geoloc_city;

        return state;
    }

    /**
     * Set the map state.
     *
     * @param object
     *   An object, describing the current state of the map.
     *   This is an object, as created by getMapState().
     */
    function setMapState(state)
    {
        // Keep track of the point that we want to be visible in the map.
        var focus_point = null;

        // Set the map type.
        var type = state.map_type;
        if (state.map_type !== null && state.map_type !== undefined) {
            // backward compatibility
            if (state.map_type === 'normal') state.map_type = 'roadmap';

            map.setMapTypeId(state.map_type);
        }

        // Set the map center.
        if (state.map_latitude  !== null      &&
            state.map_latitude  !== undefined &&
            state.map_longitude !== null      &&
            state.map_longitude !== undefined) {
            var center = new google.maps.LatLng(
                state.map_latitude, state.map_longitude);
            map.setCenter(center);
        }
        focus_point = map.getCenter();

        // Set the map zoom.
        if (state.map_zoom !== null && state.map_zoom !== undefined) {
            map.setZoom(state.map_zoom);
        }

        // Set the marker state.
        if (state.marker_latitude  !== null      &&
            state.marker_latitude  !== undefined &&
            state.marker_longitude !== null      &&
            state.marker_longitude !== undefined)
        {
            <?php if ($args["type"] == 'location-editor') { ?>
            var point = new google.maps.LatLng(
                state.marker_latitude, state.marker_longitude);
            placeEditMarker(point);
            <?php } ?>

            <?php if ($args["type"] == 'viewer') { ?>
            var point = new google.maps.LatLng(
                state.marker_latitude, state.marker_longitude);
            placeViewMarker(point);
            <?php } ?>

            focus_point = point;
        }
        else if (marker) {
            marker.setMap(null);
            marker = null;
        }

        // Set the streetview state.
        if (state.streetview_latitude  !== null      && 
            state.streetview_latitude  !== undefined && 
            state.streetview_longitude !== null      && 
            state.streetview_longitude !== undefined && 
            state.streetview_pitch     !== null      && 
            state.streetview_pitch     !== undefined && 
            state.streetview_heading   !== null      && 
            state.streetview_heading   !== undefined && 
            state.streetview_zoom      !== null      && 
            state.streetview_zoom      !== undefined)
        {
            streetview.setPosition(new google.maps.LatLng(
                state.streetview_latitude,
                state.streetview_longitude
            ));
            streetview.setPov({
                heading    : state.streetview_heading,
                pitch      : state.streetview_pitch,
                zoom       : state.streetview_zoom
            });
            streetview.setVisible(true);
        }
        else {
            streetview.setVisible(false);
        }

        // Make sure the focus point's position is visible.
        var bounds = map.getBounds();
        if (focus_point && bounds && bounds.contains(focus_point)) {
            map.panTo(focus_point);
        }
    }

<?php if ($args['type'] == 'location-editor' ||
          $args['type'] == 'map-editor') { ?>
    // Callback to the parent window.
    function raiseMapToolChangeEvent(reset)
    {
        if (parent.onMapToolChange) {
            parent.onMapToolChange(getMapState());
        };
    }
<?php } ?>

<?php if ($args['type'] == 'location-editor' ||
          $args['type'] == 'map-editor') { ?>
    function geoLocationSupported()
    {
        if (navigator.geolocation) return true;
        if (google.gears) return true;
        return false;
    }

    function doGeoLocationCallback(point)
    {
        setLoading(false);
        if (!point) {
            alert(document.getElementById('maptool_msg_notfound').innerHTML);
        } else {
            map.panTo(point);
            streetview.setVisible(false);
            <?php if ($args["type"] == 'location-editor') { ?>
            placeEditMarker(point);
            lookupGeolocationInfo(point);
            <?php } ?>
            raiseMapToolChangeEvent();
        }
    }

    function doGeoLocation()
    {
        // Try W3C Geolocation (Preferred)
        if (navigator.geolocation)
        {
            setLoading(true);
            navigator.geolocation.getCurrentPosition(function(position) {
                setLoading(false);
                var point = new google.maps.LatLng(
                    position.coords.latitude,
                    position.coords.longitude
                );
                doGeoLocationCallback(point);
            }, function() {
                setLoading(false);
                doGeoLocationCallback(null);
            });
        }
        // Try Google Gears Geolocation
        else if (google.gears)
        {
            setLoading(true);
            var geo = google.gears.factory.create('beta.geolocation');
            geo.getCurrentPosition(function(position) {
                setLoading(false);
                var point = new google.maps.LatLng(
                    position.latitude,
                    position.longitude
                );
                doGeoLocationCallback(point);
            }, function () {
                setLoading(false);
                doGeoLocationCallback(null);
            });
        }
        else {
            doGeoLocationCallback(null);
        }
    }

    // A loading mask with some timers to prevent it from becoming
    // too flashy (as in "flashing".)
    var loading_timer = null;
    function setLoading(state)
    {
        if (loading_timer) {
          clearTimeout(loading_timer);
          loading_timer = null;
        }

        var overlay = document.getElementById('loading_overlay');
        if (state) {
            loading_timer = setTimeout(function () {
              overlay.style.display = 'block';
            }, 500);
        } else {
            loading_timer = setTimeout(function () {
              overlay.style.display = 'none';
            }, 500);
        }
    }
<?php } ?>

    //]]>
    </script>
  </head>

  <body onload="initialize()">

    <noscript>
      <div style="border: 1px solid red; padding: 10px">
      <?php print $lang["IncompatibleBrowser"] ?>
      </div>
    </noscript>

    <span id="maptool_msg_notfound" style="display:none">
      <?php print $lang["NoSearchResults"] ?>
    </span>

    <div id="map"></div>

    <div id="loading_overlay"
         style="position: absolute;
                display: none;
                top: 0;
                left: 0;
                bottom: 0;
                right: 0;
                opacity: 0.20;
                filter: progid:DXImageTransform.Microsoft.Alpha(opacity=20);
                background: #000 url(<?php print $PHORUM["http_path"] ?>/mods/google_maps/maptool/loader.gif) center center no-repeat"></div>
  </body>

</html>

<?php

/**
 * A utility function, which can be used to build the HTML code that is
 * needed for displaying a map.
 *
 * @param string $maptool_type
 *     This parameter sets the type of map to show. This can be one of:
 *     - location-editor : an editor that can be used to setup a marker
 *                         or a streetview.
 *     - map-editor      : an editor that can be used to setup a map view.
 *                         This does not provide marker and streetview
 *                         functionality.
 *     - viewer          : a viewer, that can be used to display a map state,
 *                         possibly containing a single marker or a streetview.
 *     - plotter         : a viewer that can be used to plot a lot of markers.
 *
 * @param array $state
 *     State data to include in the maptool data. For types other than
 *     "plotter", the data is an array that can contain the following fields:
 *     - map_latitude
 *     - map_longitude
 *     - map_zoom
 *     - map_type
 *     - marker_latitude
 *     - marker_longitude
 *     - streetview_latitude
 *     - streetview_longitude
 *     - streetview_heading
 *     - streetview_pitch
 *     - streetview_zoom
 *     - geoloc_country
 *     - geoloc_city
 * 
 *     In case of type "plotter", the data is an array containing plot
 *     points for the map. Each plot point is an array, containing the fields:
 *     - latitude
 *     - longitude
 *     - info: optional HTML code to add in a info window for the related marker
 */
function mod_google_maps_build_maptool($maptool_type, $state = NULL)
{
    global $PHORUM;

    // We might have to load the language file ourselves,
    // in case this code is included from the admin interface.
    // We only need to include the english language file in that case.
    if (! isset($PHORUM['DATA']['LANG']['mod_google_maps'])) {
        include_once dirname(__FILE__) . "/lang/english.php";
    }

    // Check the maptool type parameter.
    if ($maptool_type !== 'location-editor' &&
        $maptool_type !== 'map-editor'      &&
        $maptool_type !== 'viewer'          &&
        $maptool_type !== 'plotter') {
        trigger_error('Illegal maptool type: ' . $maptool_type, E_USER_ERROR);
    }

    // Defaults.
    $data = array(
      'reset_latitude'  => 40,
      'reset_longitude' => -20,
      'reset_zoom'      => 1,
      'reset_type'      => 'roadmap'
    );

    // Override defaults with settings from the module configuration
    // (i.e. the reset_* options that define the reset state of/ the map.)
    foreach ($PHORUM['mod_google_maps'] as $key => $val) {
        if (isset($data["reset_$key"])) {
            $data["reset_$key"] = $val;
        }
    }

    // Add map state data.
    if ($maptool_type === 'plotter') {
        $data['plot'] = $state;
    } else {
        if (!empty($state)) {
            foreach ($state as $key => $val) {
                $data[$key] = $val;
            }
        }
    }

    $PHORUM['maptool'] = $data;

    // Add the maptool type to the maptool data.
    $PHORUM['maptool']['type'] = $maptool_type;

    // Add the Google API language to use.
    $PHORUM['maptool']['api_language'] =
        $PHORUM["DATA"]["LANG"]["mod_google_maps"]["geocoding_lang"];

    // Generate the URL to use for the map that is loaded in the iframe.
    // All fields from $PHORUM['maptool'] are added as parameters to this URL.
    $parameters = array(
        PHORUM_ADDON_URL, "module=google_maps", "addon=mapframe"
    );
    foreach ($PHORUM['maptool'] as $key => $val) {
        if ($key === 'plot') continue;
        $parameters[] = urlencode($key) . "=" . urlencode($val);
    }
    $PHORUM['maptool']['url'] = call_user_func_array(
        'phorum_get_url', $parameters);

    // Add language variables for easy access from within the maptool scripts.
    $PHORUM['maptool']['lang'] = $PHORUM["DATA"]["LANG"]["mod_google_maps"];

    // Grab the map code.
    ob_start();
    include dirname(__FILE__) . "/maptool/{$maptool_type}.php";
    $maptool = ob_get_contents();
    ob_end_clean();

    return $maptool;
}

/**
 * A utility function, which can be used to filter incoming map state data.
 *
 * @param array $data
 * @return array $filtered
 */
function mod_google_maps_filter_state_data($data)
{
    $filtered = array();

    foreach (array(
      'map_longitude'           => 'float',
      'map_latitude'            => 'float',
      'map_zoom'                => 'zoom',
      'map_type'                => 'type',
      'marker_longitude'        => 'float',
      'marker_latitude'         => 'float',
      'streetview_longitude'    => 'float',
      'streetview_latitude'     => 'float',
      'streetview_heading'      => 'float',
      'streetview_pitch'        => 'float',
      'streetview_zoom'         => 'zoom',
      'geoloc_country'          => 'string',
      'geoloc_city'             => 'string') as $field => $check)
    {
        $value = isset($_POST[$field]) && $_POST[$field] !== ''
               ? $_POST[$field] : NULL;

        if ($value !== NULL) {
            switch ($check) {
            case 'float':
              settype($value, 'float');
              break;
            case 'zoom':
              settype($value, 'float');
              if ($value <= 0) $value = 0;
              break;
            case 'type':
              if ($value !== 'roadmap'   &&
                  $value !== 'satellite' &&
                  $value !== 'hybrid'    &&
                  $value !== 'terrain')
              {
                  $value = 'roadmap';
              }
              break;
            case 'string':
              $value = trim($value);
              break;
            }
        }

        $filtered[$field] = $value;
    }

    return $filtered;
}

/**
 * Conversion of module v1 user settings data to v2 user settings data.
 *
 * @param array $data
 * @return array
 */
function mod_google_maps_upgrade_userdata($d)
{
    if (!empty($d['center'])) {
        if (preg_match(
            '/^\((-?\d+(?:\.\d+)?)\s*,\s*(-?\d+(?:\.\d+)?)\)$/',
            $d['center'], $m
        )) {
            $d['map_latitude']  = $m[1];
            $d['map_longitude'] = $m[1];
            unset($d['center']);
        }
    }

    if (!empty($d['marker'])) {
        if (preg_match(
            '/^\((-?\d+(?:\.\d+)?)\s*,\s*(-?\d+(?:\.\d+)?)\)$/',
            $d['marker'], $m
        )) {
            $d['marker_latitude']  = $m[1];
            $d['marker_longitude'] = $m[1];
            unset($d['marker']);
        }
    }

    if (!empty($d['type'])) {
        if ($d['type'] === 'normal') {
            $d['map_type'] = 'roadmap';
        } else {
            $d['map_type'] = $d['type'];
        }
        unset($d['type']);
    }

    if (!empty($d['zoom'])) {
        $d['map_zoom'] = $d['zoom'];
        unset($d['zoom']);
    }

    return $d;
}
?>

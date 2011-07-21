<?php

// Set the page description, title and breadcrumbs.
$PHORUM['DATA']['HEADING'] =
    $PHORUM['DATA']['LANG']['mod_google_maps']['UserMapTitle'];
$PHORUM['DATA']['HTML_DESCRIPTION'] = '';
$PHORUM['DATA']['DESCRIPTION'] = $PHORUM['DATA']['HEADING'];
$PHORUM['DATA']['BREADCRUMBS'][] = array(
    'URL'  => NULL,
    'TEXT' => $PHORUM['DATA']['HEADING']
);

// Build standard URLs.
phorum_build_common_urls();

// Inlude for Phorum 5.2. This API layer is replaced with the custom field
// API which handles custom fields for forums and messages too. The
// backward compatibility code in 5.3 will catch the old style API calls.
if (file_exists('./include/api/custom_profile_fields.php')) {
    require_once('./include/api/custom_profile_fields.php');
}

// Collect all users which have a "mod_google_maps" custom user profile field.
$field = phorum_api_custom_profile_field_byname('mod_google_maps');
if (empty($field)) trigger_error(
    'No custom profile field named "mod_google_maps" available',
    E_USER_ERROR
);
$user_ids = phorum_api_user_search_custom_profile_field(
    $field['id'], '%', '*', TRUE
);

// Retrieve the data for the users that were found.
$users = phorum_api_user_get($user_ids);

// Setup a list of markers to plot on the map.
$show = array();
foreach ($users as $user)
{
    $loc = $user['mod_google_maps'];

    $plot = NULL;

    if (isset($loc['marker_latitude'])) {
      $plot = array(
        'latitude'  => $loc['marker_latitude'],
        'longitude' => $loc['marker_longitude']
      );
    } elseif (isset($loc['streetview_latitude'])) {
      $plot = array(
        'latitude'  => $loc['streetview_latitude'],
        'longitude' => $loc['streetview_longitude']
      );
    }

    if (!$plot) continue;

    // Build the contents for the info window.
    $url  = phorum_get_url(PHORUM_PROFILE_URL, $user['user_id']);
    $name = (empty($PHORUM["custom_display_name"])
          ? htmlspecialchars($user['display_name'], ENT_COMPAT, $PHORUM["DATA"]["HCHARSET"])
          : $user['display_name']);
    $plot['info'] =
        "<a href=\"#\" onclick=\"parent.document.location.href='".addslashes($url)."';" .
                  "return false\">$name</a>";

    // Provide a hook for modules to influence the info window.
    if (isset($PHORUM['hooks']['google_maps_user_info']))
    {
        // Add some extra data for the hook.
        $plot['user']    = $user;
        $plot['name']    = $name;
        $plot['url']     = $url;

        $plot = phorum_hook('google_maps_user_info', $plot);
    }

    if ($plot) {
        $show[] = $plot;
    }
}
if (count($show) == 0) {
    phorum_output('google_maps::usermap_nousers');
    exit();
}

// Build HTML code for the map.
$PHORUM['DATA']['MOD_GOOGLE_MAPS'] =
    mod_google_maps_build_maptool('plotter', $show);

// Display the user map.
phorum_output('google_maps::usermap');

?>

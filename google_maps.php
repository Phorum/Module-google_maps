<?php

if (!defined("PHORUM")) return;

require_once './mods/google_maps/api.php';
require_once './mods/google_maps/defaults.php';

// Hook: common
function phorum_mod_google_maps_common()
{
    global $PHORUM;

    // Generate addon URL.
    $PHORUM["DATA"]["URL"]["MOD_GOOGLE_MAPS_USERMAP"] =
        phorum_get_url(PHORUM_ADDON_URL, "module=google_maps", "addon=usermap");

    // Handle module installation:
    // Load the module installation code if this was not yet done.
    // The installation code will take care of automatically adding
    // the custom profile field that is needed for this module.
    if (! isset($PHORUM["mod_google_maps_installed"]) ||
        ! $PHORUM["mod_google_maps_installed"]) {
        include("./mods/google_maps/install.php");
    }
}

// Hook: cc_menu_options_hook 
// This hook will add a "Location" link to the control center menu.
// This link will lead to a page where the user can configure his location.
function phorum_mod_google_maps_cc_menu_options_hook()
{
    global $PHORUM;

    // Generate the required template data for the control panel menu button.
    if ($PHORUM["DATA"]["PROFILE"]["PANEL"] == 'location')
        $PHORUM["DATA"]["LOCATION_PANEL_ACTIVE"] = TRUE;
    $PHORUM["DATA"]["URL"]["CC_LOCATION"] =
        phorum_get_url(PHORUM_CONTROLCENTER_URL, "panel=location");

    // Show the menu button.
    include phorum_get_template('google_maps::cc_menu_item');
}

// Hook: cc_panel
// This hook will setup the {MOD_GOOGLE_MAPS} template variable
// that can be used to display the map editor in the control center.
function phorum_mod_google_maps_cc_panel($data)
{
    global $PHORUM;

    // Check if we are on our custom "location" panel.
    if ($data['panel'] != 'location') return $data;

    // Check if map data was posted.
    // If yes, then store the maptool's state in the user data.
    if (isset($_POST['map_latitude']))
    {
        $PHORUM['user']['mod_google_maps'] =
            mod_google_maps_filter_state_data($_POST);

        phorum_api_user_save(array(
            'user_id'         => $PHORUM['user']['user_id'],
            'mod_google_maps' => $PHORUM['user']['mod_google_maps']
        ));

        $data['okmsg'] = $PHORUM["DATA"]["LANG"]["ProfileUpdatedOk"];
    }

    // Retrieve the data for the active Phorum user.
    $mapstate = empty($PHORUM["user"]["mod_google_maps"])
              ? array() : $PHORUM["user"]["mod_google_maps"];

    // Upgrade the user data if it looks like version 1 data.
    if (isset($mapstate['marker'])) {
        $mapstate = mod_google_maps_upgrade_userdata($mapstate);
    }

    // Build the HTML code for the map editor.
    $PHORUM['DATA']['MOD_GOOGLE_MAPS'] =
        mod_google_maps_build_maptool('location-editor', $mapstate);

    $PHORUM["DATA"]["URL"]["LOCATION_CONFIGURE"] = phorum_get_url(
        PHORUM_CONTROLCENTER_URL, "panel=location"
    );

    $data['handled'] = TRUE;
    $data['template'] = 'google_maps::cc_panel';

    return $data;
}

// Hook: profile
// Setup the google map code for the user profile.
function phorum_mod_google_maps_profile($profile)
{
    global $PHORUM;

    $PHORUM['DATA']['MOD_GOOGLE_MAPS'] = '';

    // Retrieve the data for the active Phorum user.
    $mapstate = empty($profile['mod_google_maps'])
              ? array() : $profile['mod_google_maps'];

    // Upgrade the user data if it looks like version 1 data.
    if (isset($mapstate['marker'])) {
        $mapstate = mod_google_maps_upgrade_userdata($mapstate);
    }

    // Do not show a map if neither a marker, nor a streetview are available.
    if (!isset($mapstate['marker_latitude']) &&
        !isset($mapstate['streetview_latitude'])) return $profile;

    // If a position is set in streetview, then copy that position to
    // the marker position, so the marker and streetview will match
    // when viewing the map.
    if (isset($mapstate['streetview_latitude']) &&
        isset($mapstate['streetview_longitude'])) {
        $mapstate['marker_latitude'] = $mapstate['streetview_latitude'];
        $mapstate['marker_longitude'] = $mapstate['streetview_longitude'];
    }

    // Build the HTML code for the map viewer.
    $PHORUM['DATA']['MOD_GOOGLE_MAPS'] =
        mod_google_maps_build_maptool('viewer', $mapstate);

    // Format country and city for the profile page.
    if (!empty($profile['mod_google_maps']))
    {
        $m = $profile['mod_google_maps'];
        if (!empty($m['geoloc_country'])) {
            $profile['mod_google_maps']['country'] = htmlspecialchars(
                $m['geoloc_country'], ENT_COMPAT, $PHORUM["DATA"]["HCHARSET"]);
        }
        if (!empty($m['geoloc_city'])) {
            $profile['mod_google_maps']['city'] = htmlspecialchars(
                $m['geoloc_city'], ENT_COMPAT, $PHORUM["DATA"]["HCHARSET"]);
        }
    }

    return $profile;
}

// Hook: before_footer_profile
// This hook is used to display the map in the user profile,
// unless the admin configured the module setting to not display
// the map automatically in the profile.
function phorum_mod_google_maps_before_footer_profile()
{
    global $PHORUM;

    if (isset($PHORUM["DATA"]["MOD_GOOGLE_MAPS"]) &&
        $PHORUM["mod_google_maps"]["profile_auto_show"]) {
        include phorum_get_template("google_maps::profile");
    }
}

// Hook: read
// Setup the author's city and country for the message data.
function phorum_mod_google_maps_read($messages)
{
    global $PHORUM;

    foreach ($messages as $id => $message)
    {
        if (!empty($messages[$id]['user']) &&
            !empty($messages[$id]['user']['mod_google_maps'])) {
            $m = $messages[$id]['user']['mod_google_maps'];
            if (!empty($m['geoloc_country'])) {
                $messages[$id]['user']['country'] = htmlspecialchars(
                    $m['geoloc_country'], ENT_COMPAT, $PHORUM["DATA"]["HCHARSET"]);
            }
            if (!empty($m['geoloc_city'])) {
                $messages[$id]['user']['city'] = htmlspecialchars(
                    $m['geoloc_city'], ENT_COMPAT, $PHORUM["DATA"]["HCHARSET"]);
            }
        }
    }

    return $messages;
}

// Hook: addon
function phorum_mod_google_maps_addon()
{
    global $PHORUM;

    if (! isset($PHORUM["args"]["addon"]))
        die("missing \"addon\" parameter for the google_maps module");

    // Load addon script.
    $addon = basename($PHORUM["args"]["addon"]);
    if (file_exists("./mods/google_maps/addon/{$addon}.php")) {
        include("./mods/google_maps/addon/{$addon}.php");
    } else {
        // Unknown addon.
        die("Unknown google_maps module addon script: " .
            htmlspecialchars($addon));
    }
}

?>

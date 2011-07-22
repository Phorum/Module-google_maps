<?php

if (!defined("PHORUM")) return;

require_once("./mods/google_maps/defaults.php");
require_once("./mods/google_maps/maptool/constants.php");

// Hook: common
function phorum_mod_google_maps_common()
{
    global $PHORUM;

    // Addons might need these for creating navigation.
    phorum_build_common_urls();

    // Load custom template settings.
    include(phorum_get_template("google_maps::settings"));

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

// Hook: after header
// This hook is used to setup the template variable {MOD_GOOGLE_MAPS}
// for displaying a Google Map on the page. It will prepare that variable
// for the user control center and profile pages.
function phorum_mod_google_maps_after_header()
{
    // We only need to create the map data for the profile and control center.
    if (phorum_page != "profile" && phorum_page != "control")
        return;

    global $PHORUM;

    $data = isset($PHORUM["DATA"]["mod_google_maps"])
          ? $PHORUM["DATA"]["mod_google_maps"] : array();

    // Setup the map configuration for the control center.
    if (phorum_page == "control") {
        $mapconf = isset($PHORUM["user"]["mod_google_maps"]) 
                 ? $PHORUM["user"]["mod_google_maps"] : array();
        $maptool = "editor.php";
        $PHORUM["maptool"] = array(
            "width"    => isset($data["cc_width"]) ? $data["cc_width"] : '',
            "height"   => isset($data["cc_height"]) ? $data["cc_height"] : '',
            "edittype" => "marker",
        );
    }

    // Setup the map configuration for the user profile.
    elseif (phorum_page == "profile") {
        $mapconf = isset($PHORUM["DATA"]["PROFILE"]["mod_google_maps"]) 
                 ? $PHORUM["DATA"]["PROFILE"]["mod_google_maps"] : array();
        if (!isset($mapconf["marker"]) || 
            !preg_match(REGEXP_LOCATION, $mapconf["marker"])) return;
            
        $maptool = "viewer.php";
        $PHORUM["maptool"] = array(
            "width"    => isset($data["profile_width"]) ? $data["profile_width"] : '',
            "height"   => isset($data["profile_height"]) ? $data["profile_height"] : '',
        );
    }

    // Merge the map state initialization parameters.
    foreach (array("center", "marker", "type", "zoom") as $k) {
        $PHORUM["maptool"][$k] = isset($mapconf[$k]) ? $mapconf[$k] : "";
    }

    // Grab the map code.
    ob_start();
    include("./mods/google_maps/maptool/{$maptool}");
    $PHORUM["DATA"]["MOD_GOOGLE_MAPS"] = ob_get_contents();
    ob_end_clean();
}

// Hook: tpl_cc_usersettings
// This hook is used to dispay the Google map tool in the 
// "Edit My Profile" section of the user control center.
function phorum_mod_google_maps_tpl_cc_usersettings($page)
{
    global $PHORUM;

    // We only need to show the map editor on the "Edit my profile"
    // control center panel and in case the admin didn't disable
    // automatic displaying.
    if (isset($page["PANEL"]) && $page["PANEL"] == "user" &&
        $PHORUM["mod_google_maps"]["cc_auto_show"])
        include(phorum_get_template("google_maps::controlcenter"));

    return $page;
}

// Hook: cc_save_user
function phorum_mod_google_maps_cc_save_user($userdata)
{
    // Only save data on the user panel.
    if (!isset($_POST["panel"]) || $_POST["panel"] != "user") {
        return $userdata;
    }

    // Put the location setting in the user data.
    if (isset($_POST["maptool_center"]) && preg_match(REGEXP_LOCATION, $_POST["maptool_center"])) { $userdata["mod_google_maps"]["center"] = $_POST["maptool_center"]; } else { $userdata["mod_google_maps"]["center"] = NULL; }
    if (isset($_POST["maptool_marker"]) && preg_match(REGEXP_LOCATION, $_POST["maptool_marker"])) { $userdata["mod_google_maps"]["marker"] = $_POST["maptool_marker"]; } else { $userdata["mod_google_maps"]["marker"] = NULL; }

    // Put the zoom setting in the user data.
    if (isset($_POST["maptool_zoom"]) && preg_match(REGEXP_ZOOM, $_POST["maptool_zoom"])) { $userdata["mod_google_maps"]["zoom"] = $_POST["maptool_zoom"]; } else { $userdata["mod_google_maps"]["zoom"] = NULL; }

    // Put the type setting in the user data.
    if (isset($_POST["maptool_type"]) && preg_match(REGEXP_TYPE, $_POST["maptool_type"])) { $userdata["mod_google_maps"]["type"] = $_POST["maptool_type"]; } else { $userdata["mod_google_maps"]["type"] = NULL; }

    return $userdata;
}

// Hook: before_footer
// In this hook, we check if there is a JavaScript function 
// mod_google_maps_cc_width_hack() in the page. If there is one,
// then it is called. The function is in the JavaScript library
// template "templates/<tplname>/javascript.tpl" and it is used
// for doing a fix on the normal control center template, without
// having to hack the template file for it.
//
// This hack was initially written to be able to set the width
// of the table that contains the profile HTML fields to 95%.
// Without it, the table would not expand and the map would be
// as wide as the real name input field.
// 
// This hook is also used to display the map in the user profile,
// unless the admin configured the module setting to not display
// the map automatically in the profile.
function phorum_mod_google_maps_before_footer()
{ 
    global $PHORUM;

    if (phorum_page == "control") { ?>
        <script type="text/javascript">
          if (window.mod_google_maps_cc_width_hack) {
            mod_google_maps_cc_width_hack();
          }
        </script> <?php
    }

    // Show the map viewer on the user profile page in case the admin
    // didn't disable automatic displaying.
    if (isset($PHORUM["DATA"]["MOD_GOOGLE_MAPS"]) &&
        phorum_page == "profile" && 
        $PHORUM["mod_google_maps"]["profile_auto_show"])
        include(phorum_get_template("google_maps::profile"));
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

// Hook: real_name_add_rules:
// Supply extra username rewriting rules for the Real Name module.
function phorum_mod_google_maps_real_name_add_rules($files)
{
    $files[] = "./mods/google_maps/rewrite_rules.src";
    return $files;
}


?>

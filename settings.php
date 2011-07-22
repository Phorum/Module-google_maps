<?php
if (!defined("PHORUM_ADMIN")) return;

require_once("./mods/google_maps/defaults.php");

// save settings
if(count($_POST))
{
  $settings = array(
    "api_key"  => $_POST["api_key"],
    "center"   => $_POST["maptool_center"],
    "zoom"     => $_POST["maptool_zoom"],
    "type"     => $_POST["maptool_type"],
    "profile_auto_show" => isset($_POST["profile_auto_show"]) ? 1 : 0,
    "cc_auto_show" => isset($_POST["cc_auto_show"]) ? 1 : 0,
  );
  
  if (!phorum_db_update_settings(array("mod_google_maps" => $settings))) {
    phorum_admin_error("Sorry, the setting were not updated (database error)");
  } else {
    phorum_admin_okmsg("The settings were successfully updated");
  }

  $PHORUM["mod_google_maps"] = $settings;
}

include_once "./include/admin/PhorumInputForm.php";
$frm =& new PhorumInputForm ("", "post", "Save");
$frm->hidden("module", "modsettings");
$frm->hidden("mod", "google_maps"); 

$frm->addbreak("Edit settings for the Google Maps module");

$row = $frm->addrow("Google Map API key (signup for one <a href=\"http://www.google.com/apis/maps/\" target=\"_new\">here</a>)", $frm->text_box("api_key", $PHORUM["mod_google_maps"]["api_key"], 25));

$row = $frm->addrow("Display automatically in the user's profile", $frm->checkbox("profile_auto_show", "1", "", $PHORUM["mod_google_maps"]["profile_auto_show"]));
$frm->addhelp($row, "Display automatically in the user's profile", "This option can be used to configure automatic displaying of the map for the user in the user's profile. If you disable this option, you can place the map anywhere in the profile.tpl template by hand. Place the code {MOD_GOOGLE_MAPS} in your template at the place where you want the map to appear. See also the README for this module for an example.");

$row = $frm->addrow("Display automatically in the control center", $frm->checkbox("cc_auto_show", "1", "", $PHORUM["mod_google_maps"]["cc_auto_show"]));
$frm->addhelp($row, "Display automatically in the control center", "This option can be used to configure automatic displaying of the map for the user in the user's control center. By default, the map will be placed on the \"Edit My Profile\" page. If you disable this option, you can place the map jumpmenu anywhere in the cc_usersettings.tpl template by hand. Place the code {MOD_GOOGLE_MAPS} in your template at the place where you want the map to appear. See also the README for this module for an example.");

$frm->addbreak("Configure the starting position for the map");

// Construct the code for the map editor.
$PHORUM["maptool"] = array(
    "height"   => "350px",
    "edittype" => "nomarker",
    "center"   => $PHORUM["mod_google_maps"]["center"],
    "type"     => $PHORUM["mod_google_maps"]["type"],
    "zoom"     => $PHORUM["mod_google_maps"]["zoom"],
);
ob_start();
include("./mods/google_maps/maptool/editor.php");
$map = ob_get_contents();
ob_end_clean();

// Show the map editor.
$frm->addmessage("Using the map below, you can configure the default state for the map. This state is shown in case a user did not yet set a position or if he/she unsets the position. The map's center, zoom level and map type are all stored for defining the default state.<br/><br/>Note: the easiest way to assign a certain location as the map's center is by double clicking on it.");
$frm->addmessage($map);

$frm->show();
?>

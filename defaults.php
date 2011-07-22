<?php
// A simple helper script that will setup initial module
// settings in case one of these settings is missing.

// ----------------------------------------------------------------------
// THIS FILE IS NOT MEANT FOR CHANGING MODULE SETTINGS.
// USE THE MODULE SETTINGS IN THE PHORUM ADMIN FOR THAT,
// UNLESS YOU KNOW WHAT YOU ARE DOING.
// ----------------------------------------------------------------------

if(!defined("PHORUM") && !defined("PHORUM_ADMIN")) return;

if (! isset($GLOBALS["PHORUM"]["mod_google_maps"]))
    $GLOBALS["PHORUM"]["mod_google_maps"] = array();

if (! isset($GLOBALS["PHORUM"]["mod_google_maps"]["api_key"]))
    $GLOBALS["PHORUM"]["mod_google_maps"]["api_key"] = NULL;

if (! isset($GLOBALS["PHORUM"]["mod_google_maps"]["location"]) ||
    $GLOBALS["PHORUM"]["mod_google_maps"]["location"] == '')
    $GLOBALS["PHORUM"]["mod_google_maps"]["location"] = "(40, -20)";

if (! isset($GLOBALS["PHORUM"]["mod_google_maps"]["zoom"]) ||
    $GLOBALS["PHORUM"]["mod_google_maps"]["zoom"] == '')
    $GLOBALS["PHORUM"]["mod_google_maps"]["zoom"] = 1;

if (! isset($GLOBALS["PHORUM"]["mod_google_maps"]["type"]) ||
    $GLOBALS["PHORUM"]["mod_google_maps"]["type"] == '')
    $GLOBALS["PHORUM"]["mod_google_maps"]["type"] = "normal";

if (! isset($GLOBALS["PHORUM"]["mod_google_maps"]["profile_auto_show"]))
    $GLOBALS["PHORUM"]["mod_google_maps"]["profile_auto_show"] = 1;

if (! isset($GLOBALS["PHORUM"]["mod_google_maps"]["cc_auto_show"]))
    $GLOBALS["PHORUM"]["mod_google_maps"]["cc_auto_show"] = 1;

?>

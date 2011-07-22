<?php

// ----------------------------------------------------------------------
// This install file will be included by the module automatically 
// at the first time that it is run. This file will take care of 
// adding the custom user field "mod_google_maps" to Phorum.
// This way, the administrator won't have to create the custom field
// manually.
// ----------------------------------------------------------------------

if(!defined("PHORUM")) return;

// Initialize the settings array that we will be saving.
$settings = array( "mod_google_maps_installed" => 1 );

// Get the current custom profile fields.
$FIELDS = $GLOBALS["PHORUM"]["PROFILE_FIELDS"];

// If this is not an array, we do not trust it.
if (! is_array($FIELDS)) {
    print "<b>Unexpected situation on installing " .
          "the Google Maps module:</b> \$PHORUM[\"PROFILE_FIELDS\"] " .
          "is not an array";
    return;
}

// Check if the field isn't already available.
$field_exists = false;
foreach ($FIELDS as $id => $fieldinfo) {
    if ($fieldinfo["name"] == "mod_google_maps") {
        $field_exists = true;
        break;
    }
}

// The field does not exist. Add it.
if (! $field_exists)
{
    $FIELDS["num_fields"]++;
    $FIELDS[$FIELDS["num_fields"]] =  array(
        'name' => 'mod_google_maps',
        'length' => 65000, // since field is 65000+ in the database anyway
        'html_disabled' => 0, // we need raw storage
    );

    $settings["PROFILE_FIELDS"] = $FIELDS;
}

// Save our settings.
if (!phorum_db_update_settings($settings)) {
    print "<b>Unexpected situation on installing " .
          "the Google Maps module</b>: Adding the custom profile field " .
          "failed due to a database error";
} else {
    print "<b>Install notification:</b><br/>" .
          "The Google Maps module was installed successfully!<br/><br/>";
}

?>

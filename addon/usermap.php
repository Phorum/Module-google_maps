<?php 

// Use the active Phorum charset for displaying data on the map.
$charset = isset($PHORUM["DATA"]["CHARSET"])
         ? $PHORUM["DATA"]["CHARSET"] : "utf-8";
header("Content-Type: text/html; charset=".htmlspecialchars($charset));

// Collect all users which have a "mod_google_maps" custom user profile field.
$user_ids = phorum_user_check_custom_field("mod_google_maps", "%", 1);

// Retrieve the data for the users that were found.
$users = phorum_user_get($user_ids);

// Find bounds and create javascript code for placing the markers.
$x1 = null; $y1 = null;
$x2 = null; $y2 = null;
$show = array();
foreach ($users as $user) {
    $loc = $user["mod_google_maps"];
    if (preg_match(REGEXP_LOCATION, $loc["marker"], $m)) {
        $user["mod_google_maps"]["lat"] = $m[1];
        $user["mod_google_maps"]["lng"] = $m[3];
        $url  = phorum_get_url(PHORUM_PROFILE_URL, $user["user_id"]);
        $name = htmlspecialchars($user["username"]);

        $show[] = array(
            'lat'      => $m[1],
            'lng'      => $m[3],
            'user_id'  => $user["user_id"],
            'link'     => $url,
            'username' => $name,
        );
    }
}
if (count($show) == 0) {
    print "<h1>Sorry, no user locations were found in the database</h1>";
    phorum_hook("after_footer");
    include phorum_get_template("footer");
    exit();
}
$PHORUM["DATA"]["MOD_GOOGLE_MAPS_USERS"] = $show;

// Create the map to show.
$data   = isset($PHORUM["DATA"]["mod_google_maps"])
        ? $PHORUM["DATA"]["mod_google_maps"] : array();
$width  = isset($data["usermap_width"]) ? $data["usermap_width"] : '';
$height = isset($data["usermap_height"]) ? $data["usermap_height"] : '';
$PHORUM["maptool"] = array(
    "width"    => $width,
    "height"   => $height,
    "viewtype" => "plot",
);
ob_start();
include("./mods/google_maps/maptool/viewer.php");
$PHORUM["DATA"]["MOD_GOOGLE_MAPS"] = ob_get_contents();
ob_end_clean();

// Display the header.
include phorum_get_template("header");
phorum_hook("after_header");

// Add usermap marker list.
?>
<!-- taa -->
<script type="text/javascript">
//<!--
var markers = new Array(
<?php foreach ($PHORUM["DATA"]["MOD_GOOGLE_MAPS_USERS"] as $user) { ?>
  new Array(<?php print $user["lat"]?>, <?php print $user["lng"]?>, '<a href="<?php print addslashes($user["link"]) ?>" target="_parent"><?php print addslashes($user["username"]) ?></a>'),
<?php } ?>
  null
);
// -->
</script>
<?php

// Display the user map.
include phorum_get_template("google_maps::usermap");

// Display the footer.
phorum_hook("before_footer");
include phorum_get_template("footer");
?>

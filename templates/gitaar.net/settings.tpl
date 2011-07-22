<?php
# This is implemented using pure PHP code, so the file can work for
# both Phorum 5.1 and 5.2.

# The width and height of the map in the user control center.
$PHORUM["DATA"]["mod_google_maps"]["cc_width"]  = '100%';
$PHORUM["DATA"]["mod_google_maps"]["cc_height"] = '400px';

# The width and height of the map in the profiles screen.
$PHORUM["DATA"]["mod_google_maps"]["profile_width"]  = '100%';
$PHORUM["DATA"]["mod_google_maps"]["profile_height"] = '400px';

# The width and height of the map in the usermap addon screen.
$PHORUM["DATA"]["mod_google_maps"]["usermap_width"]  = '100%';
$PHORUM["DATA"]["mod_google_maps"]["usermap_height"] = '500px';

# A Phorum 5.1 fix through javascript.
if (! isset($PHORUM["TEMPLATE"]) || $PHORUM["TEMPLATE"] != "default2") {
    ob_start(); ?>
    <script type="text/javascript">
    // A small hack for fixing the control center form field table width
    // in the default Phorum 5.1 template, without having to change the
    // control center template files.
    function mod_google_maps_cc_width_hack()
    {
        var elt = document.getElementById('cc_width_hack');
        while (elt) {
            elt = elt.parentNode;
            if (elt.tagName.toLowerCase() == 'table') {
                elt.style.width = "95%";
                break;
            }
        }
    }
    </script> <?php

    $PHORUM["DATA"]["HEAD_TAGS"] .= ob_get_contents();
    ob_end_clean();

    $PHORUM["DATA"]["HEAD_TAGS"] .= 
        '<style type="text/css">' .
        '#maptool_map { border: 1px solid black; }' .
        '</style>';
}
?>

<?php
// This file can be included for showing a editable map, which can be
// used to plot multiple markers.
//
// The viewer.php script implements all that we need.
// That script will exclude the parts that are not needed
// for this plotter script.

include dirname(__FILE__) . '/viewer.php';
?>

<script type="text/javascript">
//<![CDATA[

var markers = [
<?php
  $first = TRUE;
  foreach ($PHORUM['maptool']['plot'] as $marker) {
      if (!$first) {
          print ",\n";
      }
      $first = FALSE;
      print "[{$marker['latitude']}, {$marker['longitude']}, " .
            "'" . addslashes($marker['info']) . "']";
  }
?>
];

function onGoogleMapReady(frame, map)
{
    var e = null;
    var w = null;
    var n = null;
    var s = null;

    var i = 0;
    for (var i = 0; i < markers.length; i++)
    {
        var m = markers[i];

        // Keep track of the bounds.
        if (n == null || n < m[1]) n = m[1];
        if (e == null || e < m[0]) e = m[0];
        if (s == null || s > m[1]) s = m[1];
        if (w == null || w > m[0]) w = m[0];

        // Create the marker object.
        var point = new frame.google.maps.LatLng(m[0], m[1]);
        frame.placeViewMarker(point, m[2]); 
    }

    frame.fluster.initialize();

    // Update the map to contain the plot bounds.
    map.fitBounds(new frame.google.maps.LatLngBounds(
        new frame.google.maps.LatLng(w, s),
        new frame.google.maps.LatLng(e, n)
    ));
}
// ]]>
</script>

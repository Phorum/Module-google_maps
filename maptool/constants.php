<?php

// TODO zoom and location too
// The default map type.
define('DEFAULT_TYPE', 'normal');

// Some constants for easy and consistent regexp writing.
define('REGEXP_LOCATION', '/^\((-?\d+(\.\d+)?)\s*,\s*(-?\d+(\.\d+)?)\)$/');
define('REGEXP_TYPE', '/^(normal|satellite|hybrid)+$/');
define('REGEXP_ZOOM', '/^\d+$/');

?>

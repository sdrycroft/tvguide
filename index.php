<?php
global $conf;
require_once 'config.inc.php';
require_once 'lib/functions.inc.php';
if(php_sapi_name() != 'cli'){
  // If we are not on the command line, we output the HTML
  require_once 'lib/view.inc.php';
} else {
  // If we are being called from the command line, we do the load.
  require_once 'lib/load.inc.php';
}

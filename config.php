<?php

define('MYSQL_PORT', 3306);
define('MYSQL_HOST', "127.0.0.1");
define('MYSQL_USER', "root");
define('MYSQL_PW', "");
define('MYSQL_DB', "");

// Cookies
// The script does not support a login emulation for Google Accounts.
// You have to login with your browser and read out the cookie
// Notice: You have to refresh these values every 30 days, otherwise you get an exception
const BLUE_CSRFToken = "";
const BLUE_GOOAPPUID = 0;
const BLUE_ACSID = "";
const GREEN_CSRFToken = "";
const GREEN_GOOAPPUID = 0;
const GREEN_ACSID = "";

// Scan Region
// Warning: Choose wisely and don't try to scan the whole world.
//          The datamasses may explode. 
//          1 year data dump on 1000 km² takes about 1,8 GB
//          while a 1 year data dump on 4000 km² takes about 6,5 GB
//          (rough sample values with no mathematic base)
// The values must be integers (simply take koordinates and remove the comma
// but pay attention, that there have to be 6 digits behind the comma before you remove it)
const REGION_MINLATE6 = 51887700;   // Minimum latitude
const REGION_MAXLATE6 = 52009400;   // Maximum latitude
const REGION_MINLNGE6 = 7531000;    // Minimum longitude
const REGION_MAXLNGE6 = 7739000;    // Maximum longitude

// Scanning Tweaks
// DESIRED_ITEMS_PER_REQUEST
// Sets the requested chatlines per request.
// A higher value can speed up the process and reduces overhead
// but may be suspicious to the rip-target 
// Default: 50 (optimal 250)
const DESIRED_ITEMS_PER_REQUEST = 50;

// Default values and constants
// RELEASE_TIMESTAMP
// The release of ingress to the publicity. There should be no actions
// before this timestamp.
const INGRESS_RELEASE_TIMESTAMP = 1353024000000;
?>

<?php
set_time_limit(30);
require_once 'config.php';
require 'classes/IntelSource.php';

echo "------ Start:    " . date('d.m.Y. H:i:s') . " ------\n";
if (!IntelSource::isLocked()) {
    IntelSource::setLock();
    $userSession = new UserSession(BLUE_CSRFToken, BLUE_GOOAPPUID, BLUE_ACSID);
    $munge = new GetPaginatedPlextsMunge($userSession);
    $munge->parseMungeFromJS();
    $source = new IntelSource($munge);
    // deprecated / unused 
    //$source->setAuth(BLUE_COOKIE, BLUE_CSRFToken);
    $source->setAscendingTimestampOrder(true);
    $source->setDesiredItems(DESIRED_ITEMS_PER_REQUEST);
    $source->setFactionOnly(false);
    $source->setMinLatE6(REGION_MINLATE6);
    $source->setMinLngE6(REGION_MINLNGE6);
    $source->setMaxLatE6(REGION_MAXLATE6);
    $source->setMaxLngE6(REGION_MAXLNGE6);
    $source->setOpenTab("all");
    $offset = IntelSource::getLastMessagesTimestamp();
    if(is_null($offset)){
        $offset = INGRESS_RELEASE_TIMESTAMP;
    }
    try {
        echo "### Ripping Resistance Chat and Broadcasts ###\n";
        $source->RipMessages($offset);
    } catch (Exception $ex) {
        echo "Unrecoverable error: " . $ex->getMessage() . "\n";
    }
    echo "------ Finished: " . date('d.m.Y. H:i:s') . " ------\n";
    IntelSource::unsetLock();
} else {
    echo "------ Lockfile detected; Script already running ------\n";
    exit(1);
}

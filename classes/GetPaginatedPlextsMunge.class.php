<?php

require_once 'Munge.class.php';

class GetPaginatedPlextsMunge extends Munge {

    private $sortAscending = false;
    private $desiredItems = 50;
    private $minLatE6;
    private $minLngE6;
    private $maxLatE6;
    private $maxLngE6;
    private $openTab;
    private $minTimestampMs;
    private $maxTimestampMs;
    private $method;

    public function __construct($userSession) {
        parent::__construct($userSession);
    }
    
    public function createRequest() {
        $request = array(
            $this->plext_desiredItems => $this->desiredItems,
            $this->plext_minLatE6 => $this->minLatE6,
            $this->plext_minLngE6 => $this->minLngE6,
            $this->plext_maxLatE6 => $this->maxLatE6,
            $this->plext_maxLngE6 => $this->maxLngE6,
            $this->plext_minTimestampMs => $this->minTimestampMs,
            $this->plext_maxTimestampMs => $this->maxTimestampMs,
            $this->plext_openTab => $this->openTab,
            $this->plext_method => $this->plext_methodName,
            $this->plext_seed => $this->plext_seed_val,
        );
        if($this->sortAscending){
            $request[$this->sortAscending] = $this->sortAscending;
        }
        $this->method = $this->plext_methodName;
        return $request;
    }
    
    public function getUrl(){
        return self::TARGET_URLBASE . self::API_PATH . $this->method; 
    }

    public function getSortAscending() {
        return $this->sortAscending;
    }

    public function setSortAscending($sortAscending) {
        $this->sortAscending = $sortAscending;
    }

    public function getDesiredItems() {
        return $this->desiredItems;
    }

    public function setDesiredItems($desiredItems) {
        $this->desiredItems = $desiredItems;
    }

    public function getMinLatE6() {
        return $this->minLatE6;
    }

    public function setMinLatE6($minLatE6) {
        $this->minLatE6 = $minLatE6;
    }

    public function getMinLngE6() {
        return $this->minLngE6;
    }

    public function setMinLngE6($minLngE6) {
        $this->minLngE6 = $minLngE6;
    }

    public function getMaxLatE6() {
        return $this->maxLatE6;
    }

    public function setMaxLatE6($maxLatE6) {
        $this->maxLatE6 = $maxLatE6;
    }

    public function getMaxLngE6() {
        return $this->maxLngE6;
    }

    public function setMaxLngE6($maxLngE6) {
        $this->maxLngE6 = $maxLngE6;
    }

    public function getOpenTab() {
        return $this->openTab;
    }

    public function setOpenTab($openTab) {
        $this->openTab = $openTab;
    }

    public function getMinTimestampMs() {
        return $this->minTimestampMs;
    }

    public function setMinTimestampMs($minTimestampMs) {
        $this->minTimestampMs = $minTimestampMs;
    }

    public function getMaxTimestampMs() {
        return $this->maxTimestampMs;
    }

    public function setMaxTimestampMs($maxTimestampMs) {
        $this->maxTimestampMs = $maxTimestampMs;
    }

}

?>

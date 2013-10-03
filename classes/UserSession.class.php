<?php
class UserSession {

    const COOKIE_TPL = "csrftoken=%s; GOOGAPPUID=%d; ACSID=%s;";
    
    private $CSRFToken;
    private $GOOGAPPUID;
    private $ACSID;

    public function __construct($csrf, $gooapp, $acsid) {
        $this->CSRFToken = $csrf;
        $this->GOOGAPPUID = $gooapp;
        $this->ACSID = $acsid;
    }
    
    public function getCookie(){
        return sprintf(self::COOKIE_TPL, $this->CSRFToken, $this->GOOGAPPUID, $this->ACSID);
    }

    public function getCSRFToken() {
        return $this->CSRFToken;
    }
    public function setCSRFToken($CSRFToken) {
        $this->CSRFToken = $CSRFToken;
    }
    public function getGOOGAPPUID() {
        return $this->GOOGAPPUID;
    }
    public function setGOOGAPPUID($GOOGAPPUID) {
        $this->GOOGAPPUID = $GOOGAPPUID;
    }
    public function getACSID() {
        return $this->ACSID;
    }
    public function setACSID($ACSID) {
        $this->ACSID = $ACSID;
    }
}

?>

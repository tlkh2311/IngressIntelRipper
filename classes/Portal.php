<?php

require_once 'Entity.php';

class Portal extends Entity {

    static $portals = array();
    protected $latE6 = 0;
    protected $lngE6 = 0;
    protected $address;

    public function __construct($name, $guid, $latE6, $lenE6, $address, $team, $saveNew = true) {
        $this->name = $name;
        $this->guid = $guid;
        $this->latE6 = $latE6;
        $this->lngE6 = $lenE6;
        $this->team = $team;
        $this->address = $address;
        if($saveNew && !self::PortalExists($guid)){
            Portal::SaveNewPortal($this);
        }
    }
    
    protected static function SaveNewPortal($portal){
        if(!is_a($portal, "Portal")){
            throw new Exception("Invalid Object Type");
        }
        $sql = "INSERT INTO `portals` (`guid`, `name`, `team`, `address`, `latE6`, `lngE6`) VALUES (?, ?, ?, ?, ?, ?)";
        $insert = IntelSource::$mysqli->prepare($sql);
        $guid = $portal->getGuid();
        $name = $portal->getName();
        $team = $portal->getTeam();
        $address = $portal->getAddress();
        $latE6 = $portal->getLatitude();
        $lngE6 = $portal->getLongitude();
        $insert->bind_param('ssssii', $guid, $name, $team, $address, $latE6, $lngE6);
        $insert->execute();
        if($insert->affected_rows != 1){
            throw new Exception("Could not create Portal `" . $portal->getName() . "`: " . IntelSource::$mysqli->error);
        }
        self::$portals[$guid] = $portal;
    }
    
    public static function LoadCache(){
        $query = "SELECT * FROM `portals`";
        $sql = IntelSource::$mysqli->query($query);
        self::$portals = array();
        if($sql->num_rows > 0){
            while($row = $sql->fetch_object()){
                self::$portals[$row->guid] = new Portal(
                        $row->name, 
                        $row->guid, 
                        $row->latE6, 
                        $row->lngE6, 
                        $row->address, 
                        $row->team,
                        false
                        );
                        
            }
        }
    }

    public function getPlain(){
        return $this->name . " (" . $this->address . ")";
    }
    
    public function getLatitude() {
        return $this->latE6;
    }

    public function getLongitude() {
        return $this->lngE6;
    }

    public function getAddress() {
        return $this->address;
    }

    static function PortalExists($guid) {
        return isset(self::$portals[$guid]);
    }

    static function GetPortal($guid) {
        return self::$portals[$guid];
    }

}

?>

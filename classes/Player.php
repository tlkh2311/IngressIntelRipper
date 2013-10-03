<?php
require_once 'Entity.php';

class Player extends Entity{
    
    static $players = array();
 
    
    public function __construct($name, $guid, $team, $saveNew = true) {
        $this->name = $name;
        $this->guid = $guid;
        $this->team = $team;
        if($saveNew && !self::PlayerExists($guid)){
            Player::SaveNewPlayer($this);
        }
    }
    
    protected static function SaveNewPlayer($player){
        if(!is_a($player, "Player")){
            throw new Exception("Invalid Object Type");
        }
        $sql = "INSERT INTO `players` (`guid`, `name`, `team`) VALUES (?, ?, ?)";
        $insert = IntelSource::$mysqli->prepare($sql);
        $guid = $player->getGuid();
        $name = $player->getName();
        $team = $player->getTeam();
        $insert->bind_param('sss', $guid, $name, $team);
        $insert->execute();
        if($insert->affected_rows != 1){
            throw new Exception("Could not create Player `" . $player->getName() . "`: " . IntelSource::$mysqli->error);
        }
        self::$players[$guid] = $player;
    }
    
    static function LoadCache(){
        $query = "SELECT * FROM `players`";
        $sql = IntelSource::$mysqli->query($query);
        self::$players = array();
        if($sql->num_rows > 0){
            while($row = $sql->fetch_object()){
                self::$players[$row->guid] = new Player($row->name, $row->guid, $row->team, false);
            }
        }
    }


    static function PlayerExists($guid) {
        return isset(self::$players[$guid]);
    }
    
    static function GetPlayer($guid){
        return self::$players[$guid];
    }
}

?>

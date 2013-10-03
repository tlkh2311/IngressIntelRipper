<?php

class Entity {

    protected $guid;
    protected $name;
    protected $team;

    public function getName(){ return $this->name; }
    public function getGuid(){ return $this->guid; }
    public function getTeam(){ return $this->team; }
}

?>

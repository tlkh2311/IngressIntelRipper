<?php

require_once 'Portal.php';

class NarrowCast extends IntelMessage {

    protected $player;
    protected $portal;

    public function __construct($data) {
        parent::__construct($data);
        //$this->team = $this->player->getTeam();
    }

    protected function ParseSpecialMarkup(&$markup) {
        foreach ($markup as $key => &$value) {
            switch ($value[0]) {
                case IntelMessage::MARKUP_PLAYER:
                    if (Player::PlayerExists($value[1]['guid'])) {
                        $this->player = Player::GetPlayer($value[1]['guid']);
                    } else {
                        $name = substr($value[1]['plain'], 0);
                        $this->player = new Player($name, $value[1]['guid'], $value[1]['team']);
                    }
                    $this->markup[$key] = &$this->player;
                    $value = null;
                    break;
                case IntelMessage::MARKUP_PORTAL:
                    if (Portal::PortalExists($value[1]['guid'])) {
                        $this->portal = Portal::GetPortal($value[1]['guid']);
                    } else {
                        $this->portal = new Portal($value[1]['name'], $value[1]['guid'], $value[1]['latE6'], $value[1]['lngE6'], $value[1]['address'], $value[1]['team']);
                    }
                    $this->markup[$key] = &$this->portal;
                    $value = null;
                    break;
            }
        }
    }

}

?>

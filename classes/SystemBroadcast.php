<?php

class SystemBroadcast extends IntelMessage {

    protected $attacker;

    public function __construct($data) {
        parent::__construct($data);
        if (is_object($this->attacker)) {
            //$this->team = $this->attacker->getTeam();
        }
    }

    protected function ParseSpecialMarkup(&$markup) {
        $targetFound = false;
        foreach ($markup as $key => &$value) {
            switch ($value[0]) {
                case IntelMessage::MARKUP_PLAYER:
                    if (Player::PlayerExists($value[1]['guid'])) {
                        $this->attacker = Player::GetPlayer($value[1]['guid']);
                    } else {
                        $name = substr($value[1]['plain'], 0);
                        $this->attacker = new Player($name, $value[1]['guid'], $value[1]['team']);
                    }
                    $this->markup[$key] = &$this->attacker;
                    $this->senderGuid = $this->attacker->getGuid();
                    $value = null;
                    break;
                case IntelMessage::MARKUP_PORTAL:
                    if (Portal::PortalExists($value[1]['guid'])) {
                        $this->markup[$key] = Portal::GetPortal($value[1]['guid']);
                    } else {
                        $this->markup[$key] = new Portal($value[1]['name'], $value[1]['guid'], $value[1]['latE6'], $value[1]['lngE6'], $value[1]['address'], $value[1]['team']);
                    }
                    if (!$targetFound) {
                        $this->target = &$this->markup[$key];
                        $targetFound = true;
                    }
                    $value = null;
                    break;
            }
        }
    }

}

?>

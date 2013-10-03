<?php

require_once 'Player.php';

class PlayerGenerated extends IntelMessage {

    protected $sender = null;
    protected $atPlayers = array();

    function __construct($data) {
        parent::__construct($data);
        //$this->team = $this->sender->getTeam();
    }

    protected function ParseSpecialMarkup(&$markup) {
        foreach ($markup as $key => &$value) {
            switch ($value[0]) {
                case IntelMessage::MARKUP_SECURE:
                    $this->secure = 1;
                    $this->markup[$key] = &$this->secure;
                    $value = null;
                    break;
                case IntelMessage::MARKUP_SENDER:
                    $name = substr($value[1]['plain'], 0, strlen($value[1]['plain']) - 2);
                    if (Player::PlayerExists($value[1]['guid'])) {
                        $this->sender = Player::GetPlayer($value[1]['guid']);
                    } else {
                        $this->sender = new Player($name, $value[1]['guid'], $value[1]['team']);
                    }
                    $this->markup[$key] = &$this->sender;
                    $value = null;
                    break;
                case IntelMessage::MARKUP_AT_PLAYER:
                    if (Player::PlayerExists($value[1]['guid'])) {
                        $this->atPlayers[] = Player::GetPlayer($value[1]['guid']);
                    } else {
                        $name = substr($value[1]['plain'], 1);
                        $this->atPlayers[] = new Player($name, $value[1]['guid'], $value[1]['team']);
                    }
                    $this->markup[$key] = &$this->atPlayers[count($this->atPlayers) - 1];
                    $value = null;
                    break;
            }
        }
    }

}

?>

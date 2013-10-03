<?php

require_once 'PlayerGenerated.php';
require_once 'NarrowCast.php';
require_once 'SystemBroadcast.php';

abstract class IntelMessage {

    const TYPE_SYSTEM_NARROWCAST = "SYSTEM_NARROWCAST";
    const TYPE_PLAYER_GENERATED = "PLAYER_GENERATED";
    const TYPE_SYSTEM_BROADCAST = "SYSTEM_BROADCAST";
    const MARKUP_AT_PLAYER = "AT_PLAYER";
    const MARKUP_PLAYER = "PLAYER";
    const MARKUP_PORTAL = "PORTAL";
    const MARKUP_SECURE = "SECURE";
    const MARKUP_SENDER = "SENDER";
    const MARKUP_TEXT = "TEXT";
    const COLOR_ALIEN = "#28f428";
    const COLOR_DEPLOY = "#00bab5";
    const COLOR_ME = "#ffd652";
    const COLOR_NARROW = "#d8ad4c";
    const COLOR_PORTAL_ADDRESS = "#a5823c";
    const COLOR_PLAYER = "#cfe5e5";
    const COLOR_RESISTANCE = "#00c2ff";
    const COLOR_SECURE = "#ff5555";
    const COLOR_TIME = "#D6FEFA";
    const CLASS_ALIEN = "mAlien";
    const CLASS_DEPLOY = "mDeploy";
    const CLASS_ME = "mMe";
    const CLASS_NARROW = "mNarrow";
    const CLASS_PORTAL_NAME = "mPortalName";
    const CLASS_PORTAL_ADDRESS = "mAddress";
    const CLASS_PLAYER = "mPlayer";
    const CLASS_RESISTANCE = "mRestistance";
    const CLASS_SECURE = "mSecure";
    const CLASS_TIME = "mTime";
    const TEAM_RESISTANCE = "RESISTANCE";
    const TEAM_ALIEN = "ENLIGHTENED";

    protected $messageId;
    protected $senderGuid;
    protected $timestamp;
    protected $timestampInt;
    protected $team;
    protected $secure = 0;
    protected $target = null;
    protected $texts = array();
    protected $markup = array();
    protected $text;
    // Ripping Cache to avoid SQL-Querys

    protected static $ripcachesize = 250;
    protected static $rippedIds = array();
    protected static $ripPointer = 0;
    // if true, messages will be checked if they are saved in
    // the database. Should be disabled to get a performance
    // boost, especially when messages are load from database
    protected static $saveProcOn = true;

    public function __construct($data) {
        $this->ParseMarkup($data[2]['plext']['markup']);
        $this->ParseSpecialMarkup($data[2]['plext']['markup']);
        $this->team = $data[2]['plext']['team'];
        $this->messageId = $data[0];
        $this->text = $data[2]['plext']['text'];
        if (is_int($data[1])) {
            $this->setTimestamp($data[1]);
        } elseif (is_float($data[1])) {
            //$this->timestamp = (int) ($data[1] / 1000);
            $this->setTimestamp($data[1]);
        } else {
            $this->timestamp = 0;
        }
        if (self::$saveProcOn && !$this->IsMessageSaved()) {
            $this->SaveNewMessage();
            $this->SaveNewChatline();
        }
    }

    public function IsMessageSaved() {
        if (self::isRipped($this->messageId)) {
            echo "Message {$this->messageId} at " . date('d.m.Y H:i:s', $this->timestampInt) . " was already saved and is in Cache.\n";
            return true;
        }
        $sql = "SELECT `messageId` FROM `messages` WHERE `messageId` = ?";
        $select = IntelSource::$mysqli->prepare($sql);
        $type = self::GetTypeStringFromClass(get_class($this));
        //$target = is_a($this->target, "Portal") ? $this->target->getGuid() : NULL;
        //$select->bind_param('dsss', $this->timestamp, $type, $this->senderGuid, $target);
        $select->bind_param('s', $this->messageId);
        $select->execute();
        $select->bind_result($id);
        if ($select->fetch()) {
            echo "Message {$id} at " . date('d.m.Y H:i:s', $this->timestampInt) . " was already saved.\n";
            self::addToRipCache($this->messageId);
            return $id;
        } else {
            if (!empty($this->senderGuid)) {
                $player = Player::GetPlayer($this->senderGuid);
                if (is_a($player, "Player")) {
                    $playername = $player->getName();
                } else {
                    $playername = $this->senderGuid;
                }
            } else {
                $playername = "INGRESS_SYSTEM";
            }
            echo "Found new {$type} at " . date('d.m.Y H:i:s', $this->timestampInt) . " from '{$playername}'";
            if (is_a($this->target, "Portal")) {
                echo " @ " . $this->target->getPlain();
            }
            echo "\n";
            self::addToRipCache($this->messageId);
            return false;
        }
    }

    public function SaveNewMessage() {
        $sql = "INSERT INTO `messages` (`timestamp`, `messageId`, `sender`, `type`, `team`, `secure`, `target`)
            VALUES (?, ?, ?, ?, ?, ?, ?)";
        $insert = IntelSource::$mysqli->prepare($sql);
        $type = self::GetTypeStringFromClass(get_class($this));

        $targetGuid = $this->target != null ? $this->target->getGuid() : null;
        $insert->bind_param('dssssis', $this->timestamp, $this->messageId, $this->senderGuid, $type, $this->team, $this->secure, $targetGuid
        );
        $insert->execute();
        if ($insert->affected_rows == 1) {
            //$this->SaveMarkup($insert->insert_id, $type);
            $this->SaveMarkup($this->messageId, $type);
        } else {
            throw new Exception("Could not create Message at `" . $this->timestamp . "`: " . IntelSource::$mysqli->error);
        }
    }

    public static function GetTypeStringFromClass($classname) {
        switch ($classname) {
            case "NarrowCast":
                return IntelMessage::TYPE_SYSTEM_NARROWCAST;
                break;
            case "PlayerGenerated":
                return IntelMessage::TYPE_PLAYER_GENERATED;
                break;
            case "SystemBroadcast":
                return IntelMessage::TYPE_SYSTEM_BROADCAST;
                break;
        }
    }

    protected function SaveMarkup($messageId, $mType) {
        $sql = "INSERT INTO `markup` (`messageId`, `markupId`, `type`, `value`) VALUES
            (?, ?, ?, ?)";
        $insert = IntelSource::$mysqli->prepare($sql);
        $meFound = false;
        foreach ($this->markup as $key => $value) {
            if (is_int($value)) {
                $type = IntelMessage::MARKUP_SECURE;
            } elseif (is_a($value, "Player")) {
                if ($meFound) {
                    $type = IntelMessage::MARKUP_AT_PLAYER;
                } else {
                    if ($mType == IntelMessage::TYPE_PLAYER_GENERATED) {
                        $type = IntelMessage::MARKUP_SENDER;
                    } else {
                        $type = IntelMessage::MARKUP_PLAYER;
                    }
                    $meFound = true;
                }
                $value = $value->getGuid();
            } elseif (is_a($value, "Portal")) {
                $type = IntelMessage::MARKUP_PORTAL;
                $value = $value->getGuid();
            } elseif (is_string($value)) {
                $type = IntelMessage::MARKUP_TEXT;
            }
            $trimmedval = trim($value);
            $insert->bind_param('siss', $messageId, $key, $type, $trimmedval);
            $insert->execute();
            if ($insert->affected_rows != 1) {
                throw new Exception("Could not create Markup `" . $key . "` (" . $type . ") for Message " . $messageId . ": " . IntelSource::$mysqli->error);
            }
        }
    }

    public function SaveNewChatline(){
        $type = self::GetTypeStringFromClass(get_class($this));
        $sql = "INSERT INTO `chat` (`messageId`, `messageType`, `timestamp`, `secure`, `messageText`) VALUES (?, ?, ?, ?, ?)";
        $insert = IntelSource::$mysqli->prepare($sql);
        $insert->bind_param('ssdis', $this->messageId, $type, $this->timestamp, $this->secure, $this->text);
        $insert->execute();
        if ($insert->affected_rows != 1) {
            throw new Exception("Could not save Chatline `" . $text ."` for Message " . $messageId . ": " . IntelSource::$mysqli->error);
        }
    }
    
    public static function SaveChatline($messageId, $timestamp, $plext){
        $secure = (int)($plext['markup'][0][0] == "SECURE");
        $text = $plext['text'];
        $messageType = $plext['plextType'];
        $sql = "INSERT INTO `chat` (`messageId`, `messageType`, `timestamp`, `secure`, `messageText`) VALUES (?, ?, ?, ?, ?)";
        $insert = IntelSource::$mysqli->prepare($sql);
        $insert->bind_param('ssdis', $messageId, $messageType, $timestamp, $secure, $text);
        $insert->execute();
        if ($insert->affected_rows != 1) {
            throw new Exception("Could not save Chatline `" . $text ."` for Message " . $messageId . ": " . IntelSource::$mysqli->error);
        }
    }
    
    protected function ParseMarkup(&$markup) {
        foreach ($markup as $key => &$value) {
            switch ($value[0]) {
                case IntelMessage::MARKUP_TEXT:
                    $this->texts[] = $value[1]['plain'];
                    $this->markup[$key] = &$this->texts[count($this->texts) - 1];
                    $value = null;
                    break;
                case IntelMessage::MARKUP_SENDER:
                    $this->senderGuid = $value[1]['guid'];
                    break;
                case IntelMessage::MARKUP_PLAYER:
                    if ($this->senderGuid == null) {
                        $this->senderGuid = $value[1]['guid'];
                    }
                    break;
            }
        }
    }

    protected abstract function ParseSpecialMarkup(&$markup);

    public static function ParseData($data) {
        //self::SaveChatline($data[0], $data[1], $data[2]['plext']);
        switch ($data[2]['plext']['plextType']) {
            case IntelMessage::TYPE_PLAYER_GENERATED:
                return new PlayerGenerated($data);
                break;
            case IntelMessage::TYPE_SYSTEM_NARROWCAST:
                return new NarrowCast($data);
                break;
            case IntelMessage::TYPE_SYSTEM_BROADCAST:
                return new SystemBroadcast($data);
                break;
            default:
                break;
        }
    }

    public function ToString($formatted = false) {
        $date = date('d.m.Y H:m:s', $this->getTimestampInt());
        $string = "";
        $meFound = false;
        if ($formatted) {
            $string .= sprintf('<span class="%s">%s</span>', self::CLASS_TIME, $date);
            $count = count($this->markup);
            for ($i = 0; $i < $count; $i++) {
                // int because there's only one Var with knt and that's the Secure-Flag
                if (is_integer($this->markup[$i]) && $this->markup[$i]) {
                    $string .= sprintf(' <span class="%s">%s</span>', self::CLASS_SECURE, "[Secure]");
                } elseif (is_a($this->markup[$i], "Player")) {
                    $string .= sprintf(' <span class="%s">%s</span>', $this->markup[$i]->getTeam() == self::TEAM_RESISTANCE ? self::CLASS_RESISTANCE : self::CLASS_ALIEN, ($meFound ? "@" : "") . $this->markup[$i]->getName() . ($meFound ? ":" : ""));
                    $meFound = true;
                } elseif (is_string($this->markup[$i])) {
                    $string .= sprintf(' <span class="%s">%s</span>', self::CLASS_PLAYER, $this->markup[$i]);
                } elseif (is_a($this->markup[$i], "Portal")) {
                    $string .= sprintf(' <span class="%s">%s</span>', self::CLASS_PORTAL_NAME, $this->markup[$i]->getName());
                    $string .= sprintf(' <span class="%s">(%s)</span>', self::CLASS_PORTAL_ADDRESS, $this->markup[$i]->getAddress());
                }
            }
        } else {
            $string = $date;
            $count = count($this->markup);
            for ($i = 0; $i < $count; $i++) {
                if (is_int($this->markup[$i]) && $this->markup[$i]) {
                    $string .= " [Secure]";
                } elseif (is_a($this->markup[$i], "Player")) {
                    $string .= ($meFound ? "@" : " ");
                    $string .= ($this->markup[$i]->getTeam() == IntelMessage::TEAM_RESISTANCE ? "<Resistance: " : "<Enlightened: ");
                    $string .= $this->markup[$i]->getName();
                    $string .= ">" . (!$meFound ? ":" : "");
                    $meFound = true;
                }else if(is_a($this->markup[$i], "Portal")){
                    $string .= " " . $this->markup[$i]->getPlain();
                } elseif (is_string($this->markup[$i])) {
                    $string .= " " . $this->markup[$i];
                }
            }
        }
        return $string;
    }

    public static function isRipped($messageId) {
        return in_array($messageId, self::$rippedIds);
    }

    public static function loadRipcacheFromDb($last = 500000) {
        $sql = IntelSource::$mysqli->prepare("SELECT messageId FROM messages LIMIT " . $last);
        $sql->bind_result($id);
        $sql->execute();
        $sql->store_result();
        echo "Load " . $sql->num_rows . " message Ids\n";
        self::setRipcachesize($sql->num_rows);
        while ($sql->fetch()) {
            self::addToRipCache($id);
        }
    }

    public static function addToRipCache($messageId) {
        self::$rippedIds[self::$ripPointer++] = $messageId;
        if (self::$ripPointer >= self::$ripcachesize) {
            self::$ripPointer = 0;
        }
    }

    public static function setRipcachesize($value) {
        self::$ripcachesize = $value;
    }

    public function getSenderGuid() {
        return $this->senderGuid;
    }

    public function getTimestamp() {
        return $this->timestamp;
    }

    public function setTimestamp($value) {
        $this->timestamp = $value;
        $this->timestampInt = (int) ($value / 1000);
    }

    public function getTimestampInt() {
        return (int) $this->timestampInt;
    }

    public function getTeam() {
        return $this->team;
    }

    public function isSecure() {
        return (bool) $this->secure;
    }

    public function getTarget() {
        return $this->target;
    }

    public function getTexts() {
        return $this->texts;
        ;
    }

    public function getMarkup() {
        return $this->markup;
    }

    public static function isMessageSavingOn() {
        return self::$saveProcOn;
    }

    public static function setMessageSaving($value) {
        self::$saveProcOn = (bool) $value;
    }

}

?>

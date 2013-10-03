<?php

require 'classes/IntelMessage.php';
require_once 'classes/GetPaginatedPlextsMunge.class.php';
require_once 'classes/UserSession.class.php';

#define('USELOCALRESULT', true);
#define('DUMPREQUEST', true);
#define('DUMPRESPONSE', true);
#define('DUMPTRAFFIC', true);

if (defined("DUMPTRAFFIC")) {
    global $_dfile;
    $_dfile = fopen("tdump.txt", "a");
}

class IntelSource {

    const MYSQL_PORT = 3306;
    const MYSQL_HOST = "localhost";
    const MYSQL_USER = "root";
    const MYSQL_PW = "Root";
    const MYSQL_DB = "ingress";
    //const URL_GETPLEXTS = "http://www.ingress.com/r/dashboard.getPaginatedPlextsV2";
    const URL_GETPLEXTS = "http://www.ingress.com/r/wzuitnswoda7w028";

    static $mysqli;
    protected $messages = array();
    protected $timespan = 600000;
    protected $desiredItems = 250;
    protected $minLatE6;
    protected $minLngE6;
    protected $maxLatE6;
    protected $maxLngE6;
    protected $minTimestampMs = -1;
    protected $maxTimestampMs = -1;  // int timestamp * 1000
    protected $factionOnly = false;
    protected $openTab;
    protected $ascendingTimestampOrder = true;
    //protected $method = "dashboard.getPaginatedPlextsV2";
    protected $method = "wzuitnswoda7w028";
    protected $munge;
    protected $debug = false;
    private $header = array(
        "Accept" => "*/*",
        "Accept-Encoding" => "gzip,deflate,sdch",
        "Accept-Language" => "de-DE,de;q=0.8,en-US;q=0.6,en;q=0.4",
        "Content-Type" => "application/json; charset=UTF-8",
        "Cookie" => "",
        "User-Agent" => "Mozilla/5.0 (Windows NT 6.2; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/29.0.1547.76 Safari/537.36",
        "X-CSRFToken" => "",
        "X-Requested-With" => "XMLHttpRequest",
        "Host" => "www.ingress.com",
        "Origin" => "http://www.ingress.com",
        "Referer" => "http://www.ingress.com/intel",
    );

    public function __construct($munge) {
        if (!is_a($munge, "GetPaginatedPlextsMunge")) {
            throw new Exception("Parameter `munge` must be a `GetPaginatedPlextsMunge`-Object");
        }
        $this->munge = $munge;
        if (!isset(self::$mysqli)) {
            self::ConnectMySql();
        }
    }

    public static function ConnectMySql() {
        if (!isset(self::$mysqli)) {
            self::$mysqli = new mysqli(MYSQL_HOST, MYSQL_USER, MYSQL_PW, MYSQL_DB, MYSQL_PORT);
            if (self::$mysqli->connect_errno) {
                die("Can't connect to MySQL-Server: " . self::$mysqli->connect_error);
            }
            self::$mysqli->set_charset("utf8");
            Player::LoadCache();
            Portal::LoadCache();
        }
    }

    public function LoadMessageIdCache($amount) {
        echo "Loading $amount MessageIDs into local cache...\n";
        IntelMessage::loadRipcacheFromDb($amount);
        echo "Done. RAM Usage: " . memory_get_usage() / 1024 . "KB\n";
    }

    public function LoadMessages() {
//        Old stuff
//        $requestData = array(
//            "desiredNumItems" => $this->desiredItems,
//            "minLatE6" => $this->minLatE6,
//            "minLngE6" => $this->minLngE6,
//            "maxLatE6" => $this->maxLatE6,
//            "maxLngE6" => $this->maxLngE6,
//            "minTimestampMs" => $this->minTimestampMs,
//            "maxTimestampMs" => $this->maxTimestampMs,
//            "factionOnly" => $this->factionOnly,
//            "method" => $this->method,
//        );
//        Crypted Shit
//        $requestData = array(
//            "tmb0vgxgp5grsnhp" => $this->desiredItems,
//            "pg98bwox95ly0ouu" => $this->minLatE6,
//            "eib1bkq8znpwr0g7" => $this->minLngE6,
//            "ilfap961rwdybv63" => $this->maxLatE6,
//            "lpf7m1ifx0ieouzq" => $this->maxLngE6,
//            "hljqffkpwlx0vtjt" => $this->minTimestampMs,
//            "sw317giy6x2xj9zm" => $this->maxTimestampMs,
//            "0dvtbatgzcfccchh" => $this->factionOnly,
//            "4kr3ofeptwgary2j" => $this->method,
//        );
//        Crypted Shit v2 
//        $requestData = array(
//            "kyo6vh5n58hmrnua" => $this->desiredItems, // check []
//            "cein0n4jrifa7ui2" => $this->minLatE6, // check []
//            "lbd1juids3johtdo" => $this->minLngE6, // check []
//            "h4kyot9kmvd3g284" => $this->maxLatE6, // check []
//            "sbci6jjc2d5g9uy4" => $this->maxLngE6, // check []
//            "q5kxut5rmbtlqbf9" => "all", // check [] Tab?
//            "hu4swdftcp7mvkdi" => $this->minTimestampMs, // check [] validate not max
//            "ly6ylae5lv1z9072" => $this->maxTimestampMs, // check [] validate not min
//            "0dvtbatgzcfccchh" => $this->factionOnly,
//            "22ux2z96jwq5zn78" => $this->method,
//            "q402kn5zqisuo1ym" => "4608f4356a6f55690f127fb542f557f98de66169",
//        );
        $this->munge->setDesiredItems($this->desiredItems);
        $this->munge->setMinLatE6($this->minLatE6);
        $this->munge->setMaxLatE6($this->maxLatE6);
        $this->munge->setMinLngE6($this->minLngE6);
        $this->munge->setMaxLngE6($this->maxLngE6);
        $this->munge->setMaxTimestampMs($this->maxTimestampMs);
        $this->munge->setMinTimestampMs($this->minTimestampMs);
        $this->munge->setOpenTab($this->openTab);
        $this->munge->setSortAscending($this->ascendingTimestampOrder);
        $requestData = $this->munge->createRequest();
        $result = $this->DoRequest($this->munge->getUrl(), $requestData);
        if (defined('DEBUG')) {
            print_r($result);
        }
        $this->messages = $this->ParseNianticData($result);
    }

    protected function ParseNianticData(&$data) {
        $messages = array();
        if (is_array($data) && is_array($data['result'])) {
            foreach ($data['result'] as $key => $value) {
                $messages[$key] = IntelMessage::ParseData($value);
            }
        } else {
            echo "Got Invalid Data!\n";
            print_r($data);
        }
        return $messages;
    }

    /**
     * Loads Messages from the database if they're existing
     * @param int $begin the beginning of the timespan
     * @param int $end the end of the timespan
     */
    public function LoadMessagesFromDB($begin = -1, $end = -1) {
        $saveMessagePrestate = IntelMessage::isMessageSavingOn();
        IntelMessage::setMessageSaving(false);
        $query = "SELECT `messageId`, `timestamp`, `type`, `sender`, `team`, `secure`, `target` 
            FROM `messages`";
        $sql = null;
        if ($begin >= 0 || $end >= 0) {
            $query .= " WHERE `timestamp` ";
            if ($begin >= 0 && $end >= 0) {
                $query .= "BETWEEN ? AND ?";
                $sql = IntelSource::$mysqli->prepare($query);
                $sql->bind_param('ii', $begin, $end);
            } elseif ($begin >= 0) {
                $query .= ">= ?";
                $sql = IntelSource::$mysqli->prepare($query);
                $sql->bind_param('i', $begin);
            } else {

                $query .= "<= ?";
                $sql = IntelSource::$mysqli->prepare($query);
                $sql->bind_param('i', $end);
            }
        } else {
            $sql = IntelSource::$mysqli->prepare($query);
        }
        $metadata = array();
        $markup = array();
        $sql->execute();
        $sql->bind_result($messageId, $timestamp, $type, $sender, $team, $secure, $target);
        while ($sql->fetch()) {
            $metadata[] = array(
                "messageId" => $messageId,
                "timestamp" => $timestamp,
                "type" => $type,
                "sender" => $sender,
                "team" => $team,
                "secure" => $secure,
                "target" => $target,
            );
        }
        $sql->close();
        $localId = 0;
        foreach ($metadata as $meta) {
            $markupqry = "SELECT `markupid`, `type`, `value` FROM `markup` WHERE `messageId` = ?";
            $markupsql = IntelSource::$mysqli->prepare($markupqry);
//$markupsql->bind_param('i', $meta['id']);
            $markupsql->bind_param('s', $meta['messageId']);
            $markupsql->execute();
            $markupsql->bind_result($key, $markupType, $markupvalue);

            while ($markupsql->fetch()) {
                $markup[$localId][$key] = array(
                    "plextType" => $markupType,
                    "value" => $markupvalue,
                );
            }
            $localId++;
        }
        $nianticData = $this->ReconstructSourceData($metadata, $markup);
        $this->messages = $this->ParseNianticData($nianticData);
        IntelMessage::setMessageSaving($saveMessagePrestate);
    }

    protected function executeQuery($preparedStmt) {
        $preparedStmt->execute();
        $preparedStmt->bind_result($messageId, $timestamp, $type, $sender, $team, $secure, $target);

        $metadata = array();
        $markup = array();
        while ($preparedStmt->fetch()) {
            $metadata[] = array(
                "messageId" => $messageId,
                "timestamp" => $timestamp,
                "type" => $type,
                "sender" => $sender,
                "team" => $team,
                "secure" => $secure,
                "target" => $target,
            );
        }
        $preparedStmt->close();
        $localId = 0;
        $markupqry = "SELECT `markupid`, `type`, `value` FROM `markup` WHERE `messageId` = ?";
        $markupsql = IntelSource::$mysqli->prepare($markupqry);
        foreach ($metadata as $meta) {
//$markupsql->bind_param('i', $meta['id']);
            $markupsql->bind_param('s', $meta['messageId']);
            $markupsql->execute();
            $markupsql->bind_result($key, $markupType, $markupvalue);

            while ($markupsql->fetch()) {
                $markup[$localId][$key] = array(
                    "plextType" => $markupType,
                    "value" => $markupvalue,
                );
            }
            $localId++;
        }
// Niantic Data
        return $this->ReconstructSourceData($metadata, $markup);
    }

    /**
     * Creates a mysqli prepared command and returns it.
     * @param array $where an Array of Conditions use IntelSource->addCondition to add Conditions to the array
     * @param string $orderby A fieldname to order by
     * @param string $orderbyDir Order direction 'ASC' or 'DESC'
     * @param int $limit Resultcount Limit
     * @param int $offset Offset of a limited result
     * @return mysqli_stmt A command, ready to bind and execute
     * @throws Exception
     */
    protected function buildQuery($where, $orderby = "", $orderbyDir = "asc", $limit = 0, $offset = 0) {
        $query = "SELECT `messageId`, `timestamp`, `type`, `sender`, `team`, `secure`, `target` 
            FROM `messages`";
        $limit = (int) $limit;
        $offset = (int) $offset;
        if (is_array($where)) {
            $query .= " WHERE ";
            $vartypes = "";
            $evalparts = array();
            $parts = array();
            foreach ($where as $key => $wInfo) {
                if (is_string($wInfo['value'])) {
                    $vartypes .= 's';
                } else if (is_int($wInfo['value'])) {
                    $vartypes .= 'i';
                } else if (is_float($wInfo['value']) || is_double($wInfo['value'])) {
                    $vartypes .= 'd';
                } else {
                    throw new Exception("Invalid for where condition `" . gettype($wInfo['value']) . "`");
                }

                $parts[] = "`" . $wInfo['field'] . "` " . $wInfo['op'] . " ?";
                $evalparts[] = '$where[' . $key . '][\'value\']';
            }
            $query .= implode(" AND ", $parts);
        }
        if (!empty($orderby)) {
            $query .= " ORDER BY `{$orderby}`";
            $orderbyDir = strtoupper($orderbyDir);
            if ($orderbyDir == "ASC" || $orderbyDir == "DESC") {
                $query .= " " . $orderbyDir;
            }
        }
        if ($limit > 0) {
            $query .= " LIMIT " . $offset . ", " . $limit;
        }
        $sql = IntelSource::$mysqli->prepare($query);
        if ($sql) {
            if (is_array($where) && count($where) > 0) {
                $eval = '$sql->bind_param("' . $vartypes . '", ' . implode(',', $evalparts) . ');';
                eval($eval);
            }
            return $sql;
        } else {
            throw new Exception("Query invalid:\n" . $query);
        }
    }

    protected function queryDatabaseTplfunc($where, $orderby = "", $orderbyDir = "asc", $limit = 0, $offset = 0) {
        $sql = $this->buildQuery($where, $orderby, $orderbyDir, $limit, $offset);
        $data = $this->executeQuery($sql);
        return $this->ParseNianticData($data);
    }

    public function queryPlayerActionsByMarkupValue($playerId, $markup, $timestampBegin = -1, $timestampEnd = -1, $limit = 0, $offset = 0) {
        $limit = (int) $limit;
        $offset = (int) $offset;
        $timestampBegin = (int) $timestampBegin;
        $timestampEnd = (int) $timestampEnd;
        $query = "SELECT `messageId`, `timestamp`, `type`, `sender`, `team`, `secure`, `target` 
            FROM `messages`";
        $where = array();
        if ($timestampBegin > 0) {
            $where[] = '`timestamp` > ' . $timestampBegin;
        }
        if ($timestampEnd > 0) {
            $where[] = '`timestamp` < ' . $timestampEnd;
        }
        $where[] = "`type` = '" . IntelMessage::TYPE_SYSTEM_BROADCAST . "'";
        $where[] = '`sender` = ?';
        $where[] = '`messageId` IN (SELECT messageId FROM markup WHERE `value` = ? AND `type` = \'' . IntelMessage::MARKUP_TEXT . '\')';
        $query .= " WHERE " . implode(' AND ', $where);
        $sql = IntelSource::$mysqli->prepare($query);
        $sql->bind_param('ss', $playerId, $markup);
        $data = $this->executeQuery($sql);
        return $this->ParseNianticData($data);
    }

    public function queryPortalActions($portalId, $onlyAsTarget = false, $timestampBegin = -1, $timestampEnd = -1, $limit = 0, $offset = 0) {
        $limit = (int) $limit;
        $offset = (int) $offset;
        $timestampBegin = (int) $timestampBegin;
        $timestampEnd = (int) $timestampEnd;
        $query = "SELECT `messageId`, `timestamp`, `type`, `sender`, `team`, `secure`, `target` 
            FROM `messages`";
        $where = array();
        if ($timestampBegin > 0) {
            $where[] = '`timestamp` > ' . $timestampBegin;
        }
        if ($timestampEnd > 0) {
            $where[] = '`timestamp` < ' . $timestampEnd;
        }
        $where[] = "`type` = '" . IntelMessage::TYPE_SYSTEM_BROADCAST . "'";
        if ($onlyAsTarget) {
            $where[] = "`target` = ?";
        } else {
            $where[] = '`messageId` IN (SELECT messageId FROM markup WHERE `value` = ? AND `type` = \'' . IntelMessage::MARKUP_PORTAL . '\')';
        }
        $query .= " WHERE " . implode(' AND ', $where);
        $sql = IntelSource::$mysqli->prepare($query);
        $sql->bind_param('s', $portalId);
        $data = $this->executeQuery($sql);
        return $this->ParseNianticData($data);
    }

    public function queryMessagesByPlayer($playerId, $timestampBegin = -1, $timestampEnd = -1) {
        $where = array();
        if ($timestampBegin > 0) {
            $this->addCondition($where, "timestamp", '>', $timestampBegin);
        }
        if ($timestampEnd > 0) {
            $this->addCondition($where, "timestamp", '<', $timestampEnd);
        }
        $this->addCondition($where, "sender", '=', $playerId);

        return $this->queryDatabaseTplfunc($where);
    }

    public function queryChat($timestampBegin = -1, $timestampEnd = -1, $team = null, $secure = false, $limit = 0) {
        $where = array();
        if ($timestampBegin > 0) {
            $this->addCondition($where, "timestamp", '>', $timestampBegin);
        }
        if ($timestampEnd > 0) {
            $this->addCondition($where, "timestamp", '<', $timestampEnd);
        }
        if (is_string($team)) {
            switch ($team) {
                case IntelMessage::TEAM_ALIEN:
                case IntelMessage::TEAM_RESISTANCE:
                    $this->addCondition($where, "team", '=', $team);
                    break;
                default:
                    throw new Exception("Invalid Team");
            }
        }
        if ($secure) {
            $this->addCondition($where, "secure", '=', 1);
        }
        $this->addCondition($where, "type", '=', IntelMessage::TYPE_PLAYER_GENERATED);
        return $this->queryDatabaseTplfunc($where, "timestamp", "ASC", $limit);
    }

    public function queryChatByPlayer($playerId, $timestampBegin = -1, $timestampEnd = -1, $team = null, $secure = false, $limit = 0) {
        $where = array();
        if ($timestampBegin > 0) {
            $this->addCondition($where, "timestamp", '>', $timestampBegin);
        }
        if ($timestampEnd > 0) {
            $this->addCondition($where, "timestamp", '<', $timestampEnd);
        }
        if (is_string($team)) {
            switch ($team) {
                case IntelMessage::TEAM_ALIEN:
                case IntelMessage::TEAM_RESISTANCE:
                    $this->addCondition($where, "team", '=', $team);
                    break;
                default:
                    throw new Exception("Invalid Team");
            }
        }
        if ($secure) {
            $this->addCondition($where, "secure", '=', 1);
        }
        $this->addCondition($where, "type", '=', IntelMessage::TYPE_PLAYER_GENERATED);
        $this->addCondition($where, "sender", '=', $playerId);
        return $this->queryDatabaseTplfunc($where, "timestamp", "ASC", $limit);
    }

    public function queryPlayerDeploys($playerId, $timestampBegin = -1, $timestampEnd = -1, $limit = 0, $offset = 0) {
        return $this->queryPlayerActionsByMarkupValue($playerId, 'deployed an', $timestampBegin, $timestampEnd, $limit, $offset);
    }

    public function queryPlayerResoDestroys($playerId, $timestampBegin = -1, $timestampEnd = -1, $limit = 0, $offset = 0) {
        return $this->queryPlayerActionsByMarkupValue($playerId, 'destroyed an', $timestampBegin, $timestampEnd, $limit, $offset);
    }

    public function queryPlayerLinkDestroys($playerId, $timestampBegin = -1, $timestampEnd = -1, $limit = 0, $offset = 0) {
        return $this->queryPlayerActionsByMarkupValue($playerId, 'destroyed the Link', $timestampBegin, $timestampEnd, $limit, $offset);
    }

    public function queryPlayerFieldDestroys($playerId, $timestampBegin = -1, $timestampEnd = -1, $limit = 0, $offset = 0) {
        return $this->queryPlayerActionsByMarkupValue($playerId, 'destroyed a Control Field @', $timestampBegin, $timestampEnd, $limit, $offset);
    }

    protected function addCondition(&$array, $field, $operator, $value) {
        $array[] = array(
            'field' => $field,
            'op' => $operator,
            'value' => $value,
        );
    }

    private function ReconstructSourceData($metadata, $markup) {
        if (!is_array($markup)) {
            throw new Exception("Markup is not an array");
        }
        $result = array(
            "gameBasket" => array(
                "deletedEntityGuids" => array(),
                "gameEntities" => array(),
                "inventory" => array(),
            ),
            "result" => array()
        );
        $count = count($metadata);
        for ($i = 0; $i < $count; $i++) {
            $result['result'][$i] = array(
                $metadata[$i]["messageId"],
                $metadata[$i]["timestamp"],
                array('plext' => array(
                        "text" => "",
                        "markup" => array(),
                        "plextType" => $metadata[$i]["type"],
                        "team" => $metadata[$i]["team"],
                    )
                ),
            );
            $branch = &$result["result"][$i][2]['plext'];
            $subBranch = &$result["result"][$i][2]['plext']['markup'];
            foreach ($markup[$i] as $key => $value) {
                $subBranch[$key] = array(
                    $value['plextType'],
                    array(),
                );
                switch ($value['plextType']) {
                    case IntelMessage::MARKUP_AT_PLAYER:
                    case IntelMessage::MARKUP_PLAYER:
                    case IntelMessage::MARKUP_SENDER:
                        if (Player::PlayerExists($value['value'])) {
                            $player = Player::GetPlayer($value['value']);
                            switch ($value['plextType']) {
                                case IntelMessage::MARKUP_AT_PLAYER:
                                    $playername = "@" . $player->getName();
                                    break;
                                case IntelMessage::MARKUP_PLAYER:
                                    $playername = $player->getName();
                                    break;
                                case IntelMessage::MARKUP_SENDER:
                                    $playername = $player->getName() . ": ";
                                    break;
                            }
                            $subBranch[$key][1] = array(
                                "plain" => $playername,
                                "guid" => $player->getGuid(),
                                "team" => $player->getTeam(),
                            );
                        } else {
                            $subBranch[$key][1] = array(
                                "plain" => "<Unknown>",
                                "guid" => "0",
                                "team" => "None",
                            );
                        }
                        break;
                    case IntelMessage::MARKUP_PORTAL:
                        if (Portal::PortalExists($value['value'])) {
                            $portal = Portal::GetPortal($value['value']);
                            $subBranch[$key][1] = array(
                                "name" => $portal->getName(),
                                "plain" => $portal->getPlain(),
                                "team" => $portal->getTeam(),
                                "latE6" => $portal->getLatitude(),
                                "address" => $portal->getAddress(),
                                "lngE6" => $portal->getLongitude(),
                                "guid" => $portal->getGuid(),
                            );
                        } else {
                            $subBranch[$key][1] = array(
                                "name" => "<Unknown>",
                                "plain" => "<Unknown> (<Unknown>)",
                                "team" => "None",
                                "latE6" => 0.0,
                                "address" => "<Unknown>",
                                "lngE6" => 0.0,
                                "guid" => "0",
                            );
                        }
                        break;
                    case IntelMessage::MARKUP_SECURE:
                        $subBranch[$key][1] = array(
                            "plain" => "[secure] ",
                        );
                        break;
                    case IntelMessage::MARKUP_TEXT:
                        $subBranch[$key][1] = array(
                            "plain" => $value["value"],
                        );
                        break;
                }
                $branch["text"] .= $subBranch[$key][1]["plain"];
            }
        }
        return $result;
    }

    public function DumpMessages($formatted = false, $types = null) {

        if (is_array($types)) {
            foreach ($this->messages as $m) {
                if (in_array($m->GetTypeStringFromClass(get_class($m)), $types)) {
                    echo $m->ToString($formatted) . "<br />\n";
                }
            }
        } else {
            foreach ($this->messages as $m) {
                $m = new PlayerGenerated();
                echo $m->ToString($formatted) . "<br />\n";
            }
        }
    }

    private function DoRequest($url, $requestData) {
        $result = $this->post_request($url, $this->header, json_encode($requestData));
        if (defined('DUMPRESPONSE')) {
            print_r($result);
        }
        if ($result['status'] == "ok") {
            if (defined('DUMPREQUEST')) {
                file_put_contents("requestResult.txt", $result['content']);
                die("Dumped");
            }
            if (strpos($result['header'], "403 Forbidden") !== false) {
                echo "Exception Thrown";

                throw new Exception($result['content']);
            }
            $rData = json_decode($result['content'], true);
            if (defined('DEBUG')) {
                print_r($result);
//print_r($rData);
            }
            $this->checkResultsForErrors($rData);
            return $rData;
        } else {
            throw new Exception("Request failed: \n" . $result['error']);
        }
    }

    private function checkResultsForErrors($result) {
        if (isset($result['error'])) {
            throw new Exception($result['error']);
        }
    }

    private function gzdecode($data) {
        $len = strlen($data);
        if ($len < 18 || strcmp(substr($data, 0, 2), "\x1f\x8b")) {
            return null;  // Not GZIP format (See RFC 1952) 
        }
        $method = ord(substr($data, 2, 1));  // Compression method 
        $flags = ord(substr($data, 3, 1));  // Flags 
        if ($flags & 31 != $flags) {
// Reserved bits are set -- NOT ALLOWED by RFC 1952 
            return null;
        }
// NOTE: $mtime may be negative (PHP integer limitations) 
        $mtime = unpack("V", substr($data, 4, 4));
        $mtime = $mtime[1];
        $xfl = substr($data, 8, 1);
        $os = substr($data, 8, 1);
        $headerlen = 10;
        $extralen = 0;
        $extra = "";
        if ($flags & 4) {
// 2-byte length prefixed EXTRA data in header 
            if ($len - $headerlen - 2 < 8) {
                return false;    // Invalid format 
            }
            $extralen = unpack("v", substr($data, 8, 2));
            $extralen = $extralen[1];
            if ($len - $headerlen - 2 - $extralen < 8) {
                return false;    // Invalid format 
            }
            $extra = substr($data, 10, $extralen);
            $headerlen += 2 + $extralen;
        }

        $filenamelen = 0;
        $filename = "";
        if ($flags & 8) {
// C-style string file NAME data in header 
            if ($len - $headerlen - 1 < 8) {
                return false;    // Invalid format 
            }
            $filenamelen = strpos(substr($data, 8 + $extralen), chr(0));
            if ($filenamelen === false || $len - $headerlen - $filenamelen - 1 < 8) {
                return false;    // Invalid format 
            }
            $filename = substr($data, $headerlen, $filenamelen);
            $headerlen += $filenamelen + 1;
        }

        $commentlen = 0;
        $comment = "";
        if ($flags & 16) {
// C-style string COMMENT data in header 
            if ($len - $headerlen - 1 < 8) {
                return false;    // Invalid format 
            }
            $commentlen = strpos(substr($data, 8 + $extralen + $filenamelen), chr(0));
            if ($commentlen === false || $len - $headerlen - $commentlen - 1 < 8) {
                return false;    // Invalid header format 
            }
            $comment = substr($data, $headerlen, $commentlen);
            $headerlen += $commentlen + 1;
        }

        $headercrc = "";
        if ($flags & 1) {
// 2-bytes (lowest order) of CRC32 on header present 
            if ($len - $headerlen - 2 < 8) {
                return false;    // Invalid format 
            }
            $calccrc = crc32(substr($data, 0, $headerlen)) & 0xffff;
            $headercrc = unpack("v", substr($data, $headerlen, 2));
            $headercrc = $headercrc[1];
            if ($headercrc != $calccrc) {
                return false;    // Bad header CRC 
            }
            $headerlen += 2;
        }

// GZIP FOOTER - These be negative due to PHP's limitations 
        $datacrc = unpack("V", substr($data, -8, 4));
        $datacrc = $datacrc[1];
        $isize = unpack("V", substr($data, -4));
        $isize = $isize[1];

// Perform the decompression: 
        $bodylen = $len - $headerlen - 8;
        if ($bodylen < 1) {
// This should never happen - IMPLEMENTATION BUG! 
            return null;
        }
        $body = substr($data, $headerlen, $bodylen);
        $data = "";
        if ($bodylen > 0) {
            switch ($method) {
                case 8:
// Currently the only supported compression method: 
                    $data = gzinflate($body);
                    break;
                default:
// Unknown compression method 
                    return false;
            }
        } else {
// I'm not sure if zero-byte body content is allowed. 
// Allow it for now...  Do nothing... 
        }

// Verifiy decompressed size and CRC32: 
// NOTE: This may fail with large data sizes depending on how 
//       PHP's integer limitations affect strlen() since $isize 
//       may be negative for large sizes. 
        if ($isize != strlen($data) || crc32($data) != $datacrc) {
// Bad format!  Length or CRC doesn't match! 
            return false;
        }
        return $data;
    }

    protected function post_request($url, $header, $data, $referer = '') {

        if (defined('USELOCALRESULT')) {
            return array(
                'status' => 'ok',
                'header' => "blabla",
                'content' => file_get_contents("requestResult.txt"),
            );
        }

// Convert the data array into URL Parameters like a=b&foo=bar etc.
        if (is_array($data))
            $data = http_build_query($data);
        if (defined("DUMPRESPONSE")) {
            print_r($data);
        }
        $url = parse_url($url);
        if ($url['scheme'] != 'http') {
            die('Error: Only HTTP request are supported !');
        }

// extract host and path:
        $host = $url['host'];
        $path = $url['path'];

// open a socket connection on port 80 - timeout: 30 sec
        $fp = fsockopen($host, 80, $errno, $errstr, 30);

        if ($fp) {

// send the request headers:
            if (defined("DUMPTRAFFIC")) {
                global $_dfile;
                fputs($_dfile, "REQUEST:\r\n");
                fputs($_dfile, "POST $path HTTP/1.1\r\n");
                fputs($_dfile, "Host: $host\r\n");
            }
            fputs($fp, "POST $path HTTP/1.1\r\n");
            fputs($fp, "Host: $host\r\n");

            if ($referer != '') {
                fputs($fp, "Referer: $referer\r\n");
                if (DUMPTRAFFIC) {
                    fputs($_dfile, "Referer: $referer\r\n");
                }
            }

            fputs($fp, "Content-length: " . strlen($data) . "\r\n");
            if (defined("DUMPTRAFFIC")) {
                fputs($_dfile, "Content-length: " . strlen($data) . "\r\n");
            }
            foreach ($header as $key => $value) {
                fputs($fp, $key . ": " . $value . "\r\n");
                if (defined("DUMPTRAFFIC")) {
                    fputs($_dfile, $key . ": " . $value . "\r\n");
                }
            }
            fputs($fp, "Connection: close\r\n\r\n");
            fputs($fp, $data);

            if (defined("DUMPTRAFFIC")) {
                fputs($_dfile, "Connection: close\r\n\r\n");
                fputs($_dfile, "BODY:\r\n");
                fputs($_dfile, $data . "\r\n\r\n");
            }

            $result = '';
            while (!feof($fp)) {
// receive the results of the request
                $result .= fgets($fp, 128);
            }
        } else {
            return array(
                'status' => 'err',
                'error' => "$errstr ($errno)"
            );
        }

// close the socket connection:
        fclose($fp);


// split the result header from the content
        $result = explode("\r\n\r\n", $result, 2);

        $header = isset($result[0]) ? $result[0] : '';
        $content = isset($result[1]) ? $this->gzdecode($result[1]) : '';

        if (defined("DUMPTRAFFIC")) {
            fputs($_dfile, "RESPONSE:\r\n");
            fputs($_dfile, $header . "\r\n\r\n" . "BODY:\r\n" . $content . "\r\n");
            fputs($_dfile, "----------\r\n");
        }
// return as structured array:
        return array(
            'status' => 'ok',
            'header' => $header,
            'content' => $content
        );
    }

    public static function getLastMessagesTimestamp() {
        if (!isset(self::$mysqli)) {
            self::ConnectMySql();
        }
        $query = "SELECT `timestamp` FROM messages ORDER BY timestamp DESC LIMIT 1";
        $sql = IntelSource::$mysqli->prepare($query);
        $sql->execute();
        $sql->bind_result($timestamp);
        $sql->fetch();
        return $timestamp;
    }

    public static function RipNewMessages($csrfToken, $cookie) {
        return self::RipMessages($csrfToken, $cookie, self::getLastMessagesTimestamp());
    }

    public function RipMessages($offset) {
        $this->RipLoopFunction($offset);
    }

    public function RipMessagesOneshot($start, $end) {
        $this->setMinTimestampMs($start);
        $this->setMaxTimestampMs($end);
        try {
            $this->LoadMessages();
        } catch (Exception $ex) {
            if ($ex->getMessage() == "User not authenticated") {
                throw $ex;
            }
            sleep(1);
        }
        $messages = $this->getMessages();
        $messagecount = count($messages);
        echo "Subloop : " . $messagecount . " Messages ripped from " . date("d.m.Y H:i:s", $start / 1000) . " to " . date("d.m.Y H:i:s", $end / 1000) . "\n";
        if ($messagecount > 0) {
            $lastmessage = $messages[$messagecount - 1]->getTimestamp();
            if ($lastmessage == $start || $lastmessage == $end) {
                return $messagecount;
            } else {
                $messagecount += $this->RipMessagesOneshot($start, $lastmessage);
                return $messagecount;
            }
        }
        return $messagecount;
    }

    protected function RipLoopFunction($offset) {
        set_time_limit(0);

        $currentTimestamp = $offset;
//$currentTimestamp = (float) 1351724400000; // Launch Date
        //$lastTimestamp = $currentTimestamp;

        $loops = 0;
        do {
            $endSpan = $currentTimestamp + $this->timespan;
            $count = $this->RipMessagesOneshot($currentTimestamp, $endSpan);
            echo "Mainloop: " . $count . " Messages ripped from " . date("d.m.Y H:i:s", $currentTimestamp / 1000) . " to " . date("d.m.Y H:i:s", $endSpan / 1000) . " | Next Timestamp: " . $endSpan . "\n";
            $currentTimestamp = $endSpan;
//            $this->setMinTimestampMs($currentTimestamp);
//            $this->setMaxTimestampMs($currentTimestamp + 1080000);
//            try {
//                $this->LoadMessages();
//            } catch (Exception $ex) {
//                if ($ex->getMessage() == "User not authenticated") {
//                    throw $ex;
//                }else{
//                    echo "Exception: " . $ex->getMessage() . "\n";
//                }
//
//                sleep(1);
//                continue;
//            }
//            $messages = $this->getMessages();
//            if(count($messages) == 0){
//                $msgTimestamp = $this->getMaxTimestampMs();
//            }else{
//                
//            }
//            
//            ///
//            if (count($messages) > 0) {
//                $msgTimestamp = $messages[0]->getTimestamp();
//            } else {
//                $msgTimestamp = $this->getMaxTimestampMs();
//            }
//            echo "Loop " . ++$loops . ": " . count($this->getMessages()) . " Messages ripped from " . date("d.m.Y H:i:s", $currentTimestamp / 1000) . " to " . date("d.m.Y H:i:s", $msgTimestamp / 1000) . " | Next Timestamp: " . $msgTimestamp . "\n";
//            $lastTimestamp = $currentTimestamp;
//            if ($msgTimestamp == $lastTimestamp) {
//                $currentTimestamp += 60000;
//            } else {
//                $currentTimestamp = $msgTimestamp;
//            }
        } while ($currentTimestamp < ((float) time()) * 1000);
    }

    public static function setLock() {
        $file = fopen("run.lock", 'w');
        if ($file) {
            fwrite($file, date("d.m.Y. H:i:s"));
            fclose($file);
        }
    }

    public static function unsetLock() {
        unlink("run.lock");
    }

    public static function isLocked() {
        return is_file("run.lock");
    }

// Getter/Setter
    /**
     * Gets all loaded Messages
     * @return array
     */
    public function getMessages() {
        return $this->messages;
    }

    /**
     * Sets all loaded messages
     * @param array $messages
     */
    public function setMessages($messages) {
        $this->messages = $messages;
    }

    /**
     * Gets the amount of messages that will be requested from the server
     * @return int
     */
    public function getDesiredItems() {
        return $this->desiredItems;
    }

    /**
     * Sets the amount of messages that will be requested from the server
     * @param int $desiredItems
     */
    public function setDesiredItems($desiredItems) {
        $this->desiredItems = $desiredItems;
    }

    /**
     * Gets the minimum Latitude in E6 Format
     * @return int
     */
    public function getMinLatE6() {
        return $this->minLatE6;
    }

    /**
     * Sets the minimum Latitude in E6 Format
     * @param int $minLatE6
     */
    public function setMinLatE6($minLatE6) {
        $this->minLatE6 = $minLatE6;
    }

    /**
     * Gets the minimum Longitude in E6 Format
     * @return int
     */
    public function getMinLngE6() {
        return $this->minLngE6;
    }

    /**
     * Sets the minimum Longitude in E6 Format
     * @param int $minLngE6
     */
    public function setMinLngE6($minLngE6) {
        $this->minLngE6 = $minLngE6;
    }

    /**
     * Gets the maximum Latitude in E6 Format
     * @return int
     */
    public function getMaxLatE6() {
        return $this->maxLatE6;
    }

    /**
     * Gets the maximum Latitude in E6 Format
     * @return int
     */
    public function setMaxLatE6($maxLatE6) {
        $this->maxLatE6 = $maxLatE6;
    }

    /**
     * Gets the maximum Longitude in E6 Format
     * @return int
     */
    public function getMaxLngE6() {
        return $this->maxLngE6;
    }

    /**
     * Sets the maximum Longitude in E6 Format
     * @param int $minLngE6
     */
    public function setMaxLngE6($maxLngE6) {
        $this->maxLngE6 = $maxLngE6;
    }

    /**
     * Gets the minimum time in ms from that on the messages are loaded
     * @return float
     */
    public function getMinTimestampMs() {
        return $this->minTimestampMs;
    }

    /**
     * Sets the minimum time in ms from that on the messages are loaded
     * @param float $minTimestampMs
     */
    public function setMinTimestampMs($minTimestampMs) {
        $this->minTimestampMs = $minTimestampMs;
    }

    /**
     * Gets the maximum time in ms from that on the messages are loaded
     * It is -1 when no limit is set
     * @return float
     */
    public function getMaxTimestampMs() {
        return $this->maxTimestampMs;
    }

    /**
     * Sets the maximum time in ms from that on the messages are loaded
     * use -1 to set no limit
     * @param type $maxTimestampMs
     */
    public function setMaxTimestampMs($maxTimestampMs) {
        $this->maxTimestampMs = $maxTimestampMs;
    }

    /**
     * Gets the flag, if only faction messages should be loaded
     * @return bool
     */
    public function getFactionOnly() {
        return $this->factionOnly;
    }

    /**
     * Sets the flag, if only faction messages should be loaded
     * @param type $factionOnly
     */
    public function setFactionOnly($factionOnly) {
        $this->factionOnly = $factionOnly;
    }

    /**
     * Gets the flag, if the messages should be loaded in ascending Order
     * @return bool
     */
    public function getAscendingTimestampOrder() {
        return $this->ascendingTimestampOrder;
    }

    /**
     * Sets the flag, if the messages should be loaded in ascending Order
     * @param bool $ascendingTimestampOrder
     */
    public function setAscendingTimestampOrder($ascendingTimestampOrder) {
        $this->ascendingTimestampOrder = $ascendingTimestampOrder;
    }

    public function setOpenTab($tab){
        $this->openTab = $tab;
    }
    public function getOpenTab(){
        return $this->openTab;
    }
    
    public function getDebug() {
        return $this->debug;
    }

    public function setDebug($debug) {
        $this->debug = (bool) $debug;
    }

    public function getMethod() {
        return $this->method;
    }

    public function getTimespan() {
        return $this->timespan;
    }

    public function setMethod($method) {
        $this->method = $method;
    }

    public function setAuth($cookie, $csrftoken) {
        $this->header["Cookie"] = $cookie;
        $this->header["X-CSRFToken"] = $csrftoken;
    }

    public function setUserAgent($useragent) {
        $this->header["User-Agent"] = $useragent;
    }

    public function setTimespan($timespan) {
        $this->timespan = $timespan;
    }

}

?>

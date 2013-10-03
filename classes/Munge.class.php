<?php
require_once 'classes/UserSession.class.php';

abstract class Munge {

    const TARGET_URLBASE = "http://www.ingress.com/";
    const JS_URL = "jsc/gen_dashboard.js";
    const API_PATH = "r/";
    const PARAM_LENGTH = 16;

    protected $userSession;
    
    protected $plext_sortAscending;
    protected $plext_desiredItems;
    protected $plext_minLatE6;
    protected $plext_minLngE6;
    protected $plext_maxLatE6;
    protected $plext_maxLngE6;
    protected $plext_openTab;
    protected $plext_minTimestampMs;
    protected $plext_maxTimestampMs;
    protected $plext_method;
    protected $plext_seed;
    protected $plext_methodName;
    protected $plext_seed_val;
    // deprecated
    //protected $plext_factionOnly;
    
    
    // { Line => { varname => position in line } }
    protected $_plext_positions = array(
        6449 => array(
            "plext_desiredItems" => 167,
            "plext_minLatE6" => 187,
            "plext_minLngE6" => 241,
            "plext_maxLatE6" => 295,
            "plext_maxLngE6" => 349,
            "plext_minTimestampMs" => 403,
            "plext_maxTimestampMs" => 423,
        ),
        6450 => array(
            "plext_openTab" => 4,
        ),
        6451 => array(
            "plext_sortAscending" => 15,
        ),
        6403 => array(
            "plext_method" => 5,
        ),
        6404 => array(
            "plext_seed" => 4,
        ),
        6381 => array(
            "plext_methodName" => 97,
        ),
    );
    
    public function __construct($userSession) {
        $this->userSession = $userSession;
    }
    

    public abstract function createRequest();
    public abstract function getUrl();
    
    public function downloadMunge(){
        $filename = "cache/gen_dashboard." . $this->userSession->getCSRFToken() . ".js";
        if(is_file($filename)){
            return $filename;
        }
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, self::TARGET_URLBASE . self::JS_URL);
        curl_setopt($ch, CURLOPT_COOKIE, $this->userSession->getCookie());
        curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows NT 6.2; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/29.0.1547.76 Safari/537.36");
        curl_setopt($ch, CURLOPT_REFERER, "http://www.ingress.com/intel");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $chData = curl_exec($ch);
        return $this->saveMunge($filename, $chData);
    }
    private function saveMunge($filename, $data){
        if(!is_dir("cache")){
            mkdir("cache", 0777);
        }
        file_put_contents($filename, $data);
        return $filename;
    }
    
    public function parseMungeFromJS($url = null) {
        if (!is_string($url)) {
            $url = $this->downloadMunge();
        }
        $js = file_get_contents($url);
        $js = explode("\n", $js);
        // a worse way to do it; thin glass
        foreach ($this->_plext_positions as $line => $occur) {
            $s = &$js[$line];
            foreach ($occur as $var => $pos) {
                $this->$var = substr($s, $pos, self::PARAM_LENGTH);
            }
        }
        $this->plext_seed_val = substr($js[6404], 24, 40);
    }
}

?>

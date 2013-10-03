<?php

class Network {

    protected static function gzdecode($data) {
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

    public static function postRequest($url, $header, $data, $referer = '') {

        if (defined('USELOCALRESULT')) {
            return array(
                'status' => 'ok',
                'header' => "blabla",
                'content' => file_get_contents("requestResult.txt"),
            );
        }

// Convert the data array into URL Parameters like a=b&foo=bar etc.
        if (is_array($data)) {
            $data = http_build_query($data);
        }
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
        $content = isset($result[1]) ? self::gzdecode($result[1]) : '';

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

}

?>

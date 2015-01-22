<?php
class Common {
    public static function getClientVersion() {
        $headers = getallheaders();
        $version = 0;
        if ($headers) {
            if (isset($headers["Content-Version"])) {
                $version = $headers["Content-Version"];
            }
        }
        
        return $version;
    }
}
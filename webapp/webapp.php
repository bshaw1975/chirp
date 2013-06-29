<?php

class webapp {

    static public function asHtCtrl($conid, $coname, $upid, $upname, $filen, $exten) {
        $chrome = 0==strcasecmp('chrome', self::uagent());
        $audio = webapp::isFilext('audio', $exten);
        $image = webapp::isFilext('image', $exten);
        $html = '<span style="vertical-align:top">';
        if ($audio) {
            $html.= $chrome ? '<audio controls preload="none"><source ':'<embed height=32 autoplay="false" autostart="false" ';
        }
        elseif ($image) {
            $html.= '<image height=128 onclick="window.parent.openUrl(this.src, true);" ';
        }
        else { // isFilext('video',...)
            $html.= '<embed height=320 autostart=false ';
        }
        $html.= "src=\"/upload/content/$upid.$exten\">";
        $html.= $audio && $chrome ? '</audio>':'';
        $preval = '';
        foreach(array($coname,$upname,$filen,$exten) as $val) {
            if ($val != $preval) $html.= "&nbsp;$val";
            $preval = $val;
        }
        $html.= '</span>';
        return $html;
    }
    
    static public function exec($cmd, $fatal = null) {
        self::log("shell_exec $cmd", intval($fatal));
        return shell_exec($cmd);
    }
    
    static public function php($cmd) {
        self::exec("php $cmd");
    }
    
    static public function uagent() {
        return self::isCLI() ? '' : (
            self::$uagent ? self::$uagent : (
                self::$uagent = get_browser(null, true)['browser']
            )
        );
    }
    
    static public function isFilext($type, $file) {
        return 0!=preg_match('/('.self::config('filext_'.$type).')$/',$file);
    }
    
    static public function isCLI() {
        return !isset($_SERVER['REMOTE_ADDR']);
    }
    
    static public function config($key = null) {
        isset(self::$config) or self::configure();
        return $key ? self::$config[$key] : self::$config;
    }
    
    static public function dirSep($dir = null) {
        $dir = strval($dir ? $dir : dirname(__FILE__));
        return DIRECTORY_SEPARATOR==$dir[strlen($dir)-1] ?
            $dir : $dir.DIRECTORY_SEPARATOR;
    }

    static public function abort($msg) {
        self::error($msg, 99);
        throw new Exception($msg);
    }
    
    static public function error($msg, $level = null) {
        $level = max(88, $level);
        self::log("ERROR $msg", $level);
        $msg = date('c').' '.$msg;
        error_log($msg);
        trigger_error($msg);
        if (self::isCLI()) echo chr(7); // ring the bell
    }
    
    static public function log($msg, $level = null) {
        $level = intval($level);
        if ($level < intval(self::$config['log_level'])) return;
        $msg = date('c').' '.$msg;
        file_put_contents(self::dirSep().self::IniBasename.'.log', $msg."\n", FILE_APPEND);
    }
    
    static public function write($msg, $skipNewline = null, $skipScroll = null) {
        if (self::isCLI()) {
            echo $msg, ($skipNewline ? "":"\n");
            flush();
        }
        else {
            self::log($msg, 77);
        }
    }
    
    static public function sessSubmission($exten = null) {
        if (self::isCLI()) self::abort("no sessions in CLI");
        $ds=DIRECTORY_SEPARATOR;
        $path= self::dirSep().'upload'.$ds.'session'.$ds.session_id().'.';
        $path.= $exten ? $exten : '*';
        $matches = glob($path, GLOB_NOSORT|GLOB_NOCHECK|GLOB_ERR);
        $rv = array_pop($matches);
        while (count($matches) > 0) {
            self::error('deleting extra session file '.$matches[0]);
            unlink(array_pop($matches));
        }
        return $rv;
    }
    
    static public function wellFormedUTF8($str) {
        return htmlspecialchars_decode(
            htmlspecialchars($str, ENT_DISALLOWED|ENT_IGNORE, 'UTF-8')
        );
    }
    
    static public function removeTags($ml) {
        if (0==strlen($ml)) return $ml;
        $rv = preg_replace("/<(.*)>/msU", "\n", $ml);
        $rv = preg_replace("/\n+/m", "\n", $rv);
        return $rv;
    }
    
    static private function _newMysqli($rv) {
        self::assertMySqlOk($rv);
        $rv->set_charset('utf8');
        return $rv;
    }

    static public function newMysqli($dbname = null) {
        $config = self::config();
        if (''==$dbname) $dbname = $config['mysqli_dbname'];
        return self::_newMysqli(new mysqli($config['mysqli_host'], $config['mysqli_user'], $config['mysqli_password'], $dbname));
    }
  
    static public function newRoMysqli($dbname = null) {
        $config = self::config();
        if (''==$dbname) $dbname = $config['mysqli_dbname'];
        return self::_newMysqli(new mysqli($config['mysqli_host'], $config['mysqli_ro_user'], $config['mysqli_ro_password'], $dbname));
    }

    static public function query(mysqli $mysqli, $stmt, $skipWarnings = null) {
        $res = $mysqli->query($stmt);
        self::assertMySqlOk($mysqli, $skipWarnings, $stmt);
        return $res;
    }
    
    static public function assertMySqlOk(mysqli $mysqli, $skipWarnings = null, $stmt = null) {
        $error = $warn = '';
        if (isset($mysqli->error_list) && count($mysqli->error_list) > 0) {
            $error = var_export($mysqli->error_list, true);
        }
        elseif (0!=$mysqli->errno) {
            $error = $mysqli->error;
        }
        elseif (0!=$mysqli->connect_errno) {
            $error = $mysqli->connect_error;
        }
        elseif ('00000'!=$mysqli->sqlstate) {
            $error = 'SQLState='.$mysqli->sqlstate;
        }
        if ($error) {
            $error .= "\n";
            if ('' != $mysqli->info) $error .= $mysqli->info."\n";
            if ('' != $stmt) $error .= $stmt."\n";
        }
        // set php.ini mysqli.reconnect to handle CR_SERVER_GONE_ERROR || CR_SERVER_LOST
        if (($mysqli->errno == 2006 || $mysqli->errno == 2013) && $mysqli->ping()) {
            // ping/reconnected succeeded, so convert error to a warning
            $warn = $error;
            $error = null;
        }
        if ($error) throw new Exception($error); // serious error occurred
        if (!$skipWarnings && $mysqli->warning_count > 0) {
            $warn .= "The last SQL statement had warnings: ".$mysqli->info."\n";
            $warnings = $mysqli->query("show warnings");
            if ($warnings) {
                while ($row = $warnings->fetch_row()) {
                    $warn .= implode(' ', $row)."\n";
                }
                $warnings->close();
            }
        }
        if ($warn) self::error($warn . $stmt . "\n");
    }

    static public function aquireSem($key) {
        self::$semaph[$key] = fopen(self::dirSep()."$key.semap.ini", 'w');
        if (self::$semaph[$key]) {
            // write a null to show its been locked
            fwrite(self::$semaph[$key], "\0", 1);
            fflush(self::$semaph[$key]);
        }
        return self::$semaph[$key];
    }
    
    static public function releaseSem($key) {
        // truncate to show its been released
        $rv = ftruncate(self::$semaph[$key], 0);
        fclose(self::$semaph[$key]);
        self::$semaph[$key] = null;
        return $rv;
    }

    static public function serverInfo() {
        //return "";
        $keys = array(
        "HTTP_ACCEPT_LANGUAGE","REMOTE_ADDR","REMOTE_PORT","SERVER_ADDR","SERVER_PORT"
        );
        $hdr  = array(
        'language','your ip','dst port','server ip','src port'
        );
        $html = '
        <table style="width:100%"><tr><th>'.
        implode($hdr, "</th><th>").'</th></tr>
        <tr style="text-align:center"><td>';
        for ($i=0; $i < count($keys); $i++) {
            if ($i > 0) $html .= '</td><td>';
            $html .= strval($_SERVER[$keys[$i]]);
        }
        return $html . '
        </td></tr></table>
        <table>
        <tr><th>server:</th><td>'.
        php_uname('a').'
        </td></tr>
        <tr><th>page sent:</th><td>'.
        date('r e').'
        </td></tr>
        <tr><th>client:</th><td>'.
        gethostbyaddr($_SERVER['REMOTE_ADDR']).'
        </td></tr>
        <tr><th>user agent:</th><td>'.
        $_SERVER["HTTP_USER_AGENT"].'
        </td></tr>
        <tr><th>local time:</th><td id="hdrlocaldt">loading...</td></tr>
        </table>
        <script>
        window.setInterval(function() {
            document.getElementById("hdrlocaldt").innerHTML = new Date().toString();
        },990);
        </script>';
    }
    
    static private function isWindows() { // private keeps non-portable stuff here
        return 0==strncasecmp(getenv('OS'),'win',3);
    }
    
    static private function configure() {
        ini_set('mysqli.reconnect',1);
        $basePath = self::dirSep().self::IniBasename;
        if (is_null(self::$config)) {
            if (is_readable($basePath.'.ini')) {
                self::$config = parse_ini_file($basePath.'.ini');
            }
            else {
                self::$config = parse_ini_string(convert_uudecode(file_get_contents($basePath.'.enc')));
            }
        }
    }

    const IniBasename = 'webapp';
    const DateTimeMySql = 'Y-m-d h:i:s';
    
    private static $config;
    private static $semaph;
    private static $uagent;
}

function __autoload($className) {
 
    $filePath = $className . '.php';
    
    if (file_exists($filePath)) {
        require_once $filePath;
        return;
    }
    
    $includePaths = explode(PATH_SEPARATOR, get_include_path());
    
    foreach($includePaths as $includePath){
        if (file_exists($includePath . DIRECTORY_SEPARATOR . $filePath)) {
            require_once $filePath;
            return;
        }
    }
    
    $filePath = str_replace('_', DIRECTORY_SEPARATOR, $className) . '.php';
        
    foreach($includePaths as $includePath){
        if(file_exists($includePath . DIRECTORY_SEPARATOR . $filePath)){
            require_once $filePath;
            return;
        }
    }
    
    webapp::error("failed to autoload $className\n");
}

if (isset($_SERVER['argv'][0]) && basename($_SERVER['argv'][0])==basename(__FILE__)) {
    if (false===file_put_contents(webapp::IniBasename.'.enc', 
            convert_uuencode(file_get_contents(webapp::IniBasename.'.ini')))) {
        webapp::error('Nope!');
    }
}

function __ErrorHandler($errno, $errstr, $errfile, $errline) {
    //debug_print_backtrace();
    return false; // not handled, please call the default PHP handler
}

set_error_handler('__ErrorHandler');

webapp::config();

/*
return shell_exec($cmd);
    $rv = -1;
    $out = array();
    $last = exec($cmd, $out, $rv);
    $out = implode("\n", $out);
    if (0!=$rv) {
    $msg = "$cmd exited with $rv $out";
    if ($fatal) self::abort($msg);
    else self::error($msg);
    }
return $out;
}*/
?>
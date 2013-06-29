<?php
require_once '../webapp.php';
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0'); // proxies
?>
<!doctype html>
<html>
<head>
<script>
function uplaunch(filen) { 
    tall = -1==filen.search(/\.(<?php echo webapp::config('filext_audio');?>)$/);
    return window.parent.openUrl('/upload/content/'+filen, tall);
}
function updtime() {
    return window.parent.getLocalDtime();
}

function upcontact() {
    return window.parent.contact.id;
}
function upcertain() {
    return window.parent.contact.certain;
}
function uplocation() {
    return window.parent.contact.location;
}
</script>
<style>
body { 
    width:100%;
    display:inline-block;
}
#outerdiv {
    margin-right:16px;
    float:right;
}
</style>

<?php // LEAVING <HEAD> OPEN, required file will close it

require_once '../webapp.php';
const DS = DIRECTORY_SEPARATOR;

function copyOne($name, $tmp) {

    if (webapp::isFilext('system', $name) || webapp::isFilext('archive', $name)) {
        webapp::log('prevented upload of '.$name, 9);
        return;
    }

    $iDot = strrpos($name, '.');
    $filen = ($iDot < 1) ? $name : substr($name, 0, $iDot);
    $exten = ($iDot < 1) ? '' : substr($name, $iDot + 1);
    $hostdt = date(webapp::DateTimeMySql);
    $clientdt = $_POST['localdt'];
    $location = $_POST['location'];
    $contact = $_POST['contact'];
    $certain = $_POST['certain'];
    /*
    if (''==$clientdt) $clientdt = $_SERVER['HTTP_LOCALDT'];
    if (''==$location) $location = $_SERVER['HTTP_LOCATION'];
    if (''==$contact) $contact = $_SERVER['HTTP_CONTACT'];
    if (''==$certain) $certain = $_SERVER['HTTP_CERTAIN'];*/
    
    $db = webapp::newMysqli();
    $stmt = $db->prepare("insert into upload values(null,?,?,?,?,?,?)") 
        or webapp::abort('Prepare failed.');
    
    $stmt->bind_param('ssssss', $filen, $exten, $hostdt, $clientdt, $location, $name)
        or webapp::abort('Bind failed.');
    
    $stmt->execute() 
        or webapp::abort('Insert submission failed.');
    
    $uploadId=$stmt->insert_id
        or webapp::abort('Missing upload insert id.');
    $stmt->close();
    
    webapp::query($db, "insert into contact_upl values($contact, $uploadId, $certain)");
    webapp::assertMySqlOk($db);
    $db->close();
    
    $file = dirname(__FILE__).DS.'content'.DS.$uploadId.(''==$exten ? "":".$exten");
    
    move_uploaded_file($tmp, $file) 
        or webapp::abort("failed move upload $uploadId $file from $tmp");

    
    // for audio clips, try to run a trace and compare
    // for everything else we are done
    if (!webapp::isFilext('audio', $file)) return;
    
    $wav  = __DIR__.DS.'pitch'.DS.$uploadId.'.wav';
    
    if ('wav'==$exten) {
        if (!copy($file, $wav)) {
            webapp::error("failed copy $file to $wav");
            $wav='';
        }
    }
    else {
        $out  = webapp::exec("ffmpeg -i $wav -aq 0 $file");
        if (false===strpos($out, 'Output #0')) {
            webapp::error("failed conversion to WAV $file to $wav");
            $wav='';
        }
    }
    
    if (file_exists($wav)) {
        $queue= __DIR__.DS.'pitch'.DS.'fifo';
        $notify = fopen($queue, 'a');
        fwrite($notify, "$wav\n");
        fclose($notify);
    }
}

if ('POST'==$_SERVER["REQUEST_METHOD"]) {
    //webapp::log(var_export($_FILES, true));
    //webapp::log(var_export($_SERVER, true));
    foreach ($_FILES as $fd) {
        $tmp = $fd['tmp_name'];
        $name = $fd['name'];
        if (is_array($tmp)) for ($i=0; $i<count($tmp); $i++) {
            copyOne($name[$i], $tmp[$i]);
        }
        else {
            copyOne($name, $tmp);
        }
    }
    header('Location: '.$_SERVER["REQUEST_URI"]);
    exit;
}

require_once 'yui3.php'; // redirects to multif for old UA

require_once 'view.php'; // lists upload/content
?>
</div> <!-- #outerdiv -->
</body>
</html>
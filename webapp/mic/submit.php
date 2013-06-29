<?php
require_once '../webapp.php';

header('Location: /main');

const FileExtIn = 'wav';
const FileExtOut = 'mp3';
const DS = DIRECTORY_SEPARATOR;

$sessFile = webapp::sessSubmission(FileExtIn);
file_exists($sessFile)
    or webapp::abort('Missing session recording.');

$webroot = webapp::dirSep();
$sessId = session_id();
$filen = 'Mic Record';
$exten = FileExtOut;
$hostdt = date(webapp::DateTimeMySql);
$name = $_POST['name'];
$clientdt = $_POST['localdt'];
$contact = $_POST['contact'];
$certain = $_POST['certain'];
$location = $_SERVER['REMOTE_ADDR'].'|'.$_POST['location'];

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

// TODO find in $_SESSION
// create a contact for the submission

$contactId = @intval($_SESSION['contact']);
if (0==$contactId) {
    webapp::query($db, 
    "insert into contact values(null, '$name ".substr($sessId, -4)."')"
    );
    $contactId = $db->insert_id
        or webapp::abort('Missing contact insert id.');
}
webapp::query($db, 
    "insert into contact_upl values($contactId, $uploadId, $certain)"
);
webapp::assertMySqlOk($db);
$db->close();

$mpeg = $webroot.'upload'.DS.'content'.DS."$uploadId.".FileExtOut;
$wav  = $webroot.'upload'.DS.'pitch'.DS.$uploadId.'.'.FileExtIn;
$queue= $webroot.'upload'.DS.'pitch'.DS.'fifo';

if (rename($sessFile, $wav)) {
    $sessFile = null;
}
else {
    webapp::abort("Failed to rename $sessFile $wav");
}

webapp::exec("ffmpeg -i $wav -aq 0 $mpeg");

$notify = fopen($queue, 'a');
fwrite($notify, "$wav\n");
fclose($notify);
?>
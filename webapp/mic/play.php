<?php
require_once '../webapp.php';

$sample = webapp::sessSubmission();
$len = filesize($sample);
if (0==$len) {
    // log err instead of calling abort or throw
    // b/c this request is called over AJAX
    // so nobody will will see an error page
    error_log('no clip '.$sample);
    exit;
}

header('Content-Type: audio/'.(
  preg_match('/.*\.mp.$/',$sample) ? 'mpeg':'x-wav'
));
header('Content-Transfer-Encoding: binary'); 
header('Content-Length: '.$len);
ob_clean();
flush();
readfile($sample);
?>
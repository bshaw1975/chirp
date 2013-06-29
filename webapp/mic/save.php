<?php
require_once '../webapp.php';

$file = webapp::sessSubmission('wav');
$rv = file_put_contents($file, file_get_contents('php://input'));
if ($rv < 1) {
    // log err instead of calling abort or throw
    // b/c this request is called over AJAX
    // so nobody will will see an error page
    error_log('Failed to write '.$file);
}
?>
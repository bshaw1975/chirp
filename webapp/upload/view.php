<?php
require_once '../webapp.php';

function asHtml($rs) {
    echo "<table style='whitespace:nowrap;text-overflow:ellipsis;text-align:right;' id='fileTable'><tr><th>Files</th><tr><td>\n";
    while ($row = $rs->fetch_row()) {
        echo '<tr><td colspan=99>';
        echo '<a href="#'.$row[0].'" onclick="uplaunch(\''.$row[0].'.'.$row[1].'\'); return 0;">';
        echo $row[2].'.'.$row[1].'&nbsp;('.$row[3].")</a></td></tr>\n";
    }
    echo "</table>\n";
}

$db=webapp::newRoMysqli();
$rs = webapp::query($db,'
select upload.id, upload.exten, upload.filen, upload.name
from upload order by id desc
');

asHtml($rs);

$rs->close();
$db->close();
?>
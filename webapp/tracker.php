<?php
require_once 'webapp.php';

const WakeupString = '--wake--';

// The FIFO
$todoFull = array();

$T = time();

$maxTiny = -1;
$maxFull = -1;

while (false!==($wav=trim(fgets(STDIN)))) {

    if (''==$wav) continue;

    $runFull = true;
    echo "Remember to change default run back to tiny\n";
    if (WakeupString==strtok($wav,' ')) {
        // wake up only when no tiny are expected
        // if we had no request in the last N seconds
        // run a full trace
        webapp::log('wake '.var_export($todoFull, true));
        $runFull = ( time() - $T > $maxTiny )
            && ( count($todoFull) > 0 );
        if (!$runFull) continue;
        $wav = array_shift($todoFull);
    }

    $ID = basename($wav, '.wav');
    $cfg = $runFull ? 'full':'tiny';
    $T = time();
    webapp::php(webapp::dirSep()."pitch.php $wav $cfg");
    $td = time() - $T;

    if ($runFull) {
        $maxFull = max($maxFull, $td);
    }
    else {
        $maxTiny = max($maxTiny, $td);
    }
    
    if (!$runFull) $todoFull[] = $wav;
    
    webapp::log(strval($td)." sec for $cfg trace $ID");
    
    if (0==(intval($ID) % 10)) {
        $min = round(1.0 * $maxTiny / 60.0, 2);
        webapp::log("longest tiny trace was $min minutes");
        $min = round(1.0 * $maxFull / 60.0, 2);
        webapp::log("longest full trace was $min minutes");
    }
}
?>
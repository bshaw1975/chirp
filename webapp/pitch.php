<?php
require_once 'webapp.php';

const DS=DIRECTORY_SEPARATOR;

if (3!=count($argv)) webapp::abort(
    "Usage: ".$argv[0]." file.wav [full|tiny]"
);

$wav = $argv[1];
if (!file_exists($wav)) webapp::abort(
    "File not found $wav"
);

$full = 'full'==$argv[2];

$clipId0=strtok(basename($wav),'.');

if (!ctype_digit($clipId0)) webapp::abort(
    "Invalid clip ID for $wav"
);

$here = webapp::dirSep();
$trackOutDir  = $here.'upload'.DS.'pitch'.DS.($full ? 'full':'tiny');
$plgFile = $trackOutDir.DS.'0.plg';
$cfgPath = ' -c '.$trackOutDir.'.cfg';

$tracker = webapp::dirSep().'sh'.DS.'cpitch '.$cfgPath.' '.$wav;
$compare = webapp::dirSep().'sh'.DS.'ccompare '.$cfgPath.' -m pitch_dtw '.$trackOutDir;

// run trace
$trackOut = webapp::exec($tracker);
$hdrPos = strpos($trackOut, "time\t")
    or webapp::abort("Tracker header off $wav ".$trackOut);

// write trace
file_put_contents($plgFile, substr($trackOut, $hdrPos)) 
    or webapp::abort("Failed to write $plgFile");
unset($trackOut);

// done with WAV
if ($full){
    unlink($wav) or webapp::abort("Failed to unlink $wav");
    webapp::log("unlinked $wav");
    unset($wav);
}

// run compare (will fail on single, needs > 1 file)
$compareOut = webapp::exec($compare);

// rename log from 0 to proper ID
$newPlgFile = str_replace("0.plg", "$clipId0.plg", $plgFile);
!file_exists($newPlgFile)
    or webapp::error("$newPlgFile already exists");
rename($plgFile, $newPlgFile) 
    or webapp::error("Failed to rename $plgFile $newPlgFile");
unset($plgFile);

// save ID indexes
$hdrPos = strpos($compareOut, "id\tlocation")
    or webapp::abort("Compare 1st header off $wav $compareOut");
$compareOut = substr($compareOut, $hdrPos);
$idMap = array(); // key = index, value = LOG File ID
$lines = explode("\n", $compareOut);
array_shift($lines); // skip header
array_shift($lines); // skip zero index
foreach ($lines as $line) {
    $row = explode("\t", $line); // tab delimited
    if (count($row) < 2) break;
    $idMap[] = basename($row[1], '.plg');
}

// parse comparisons
$hdrPos = strpos($compareOut, "ref\ttgt")
    or webapp::abort("Compare 2nd header off $wav $compareOut");
$compareOut = substr($compareOut, $hdrPos);
$i = $skipped = 0;
$diffs = array(); // key = index, value = DIFF
$lines = explode("\n", $compareOut);
array_shift($lines); // skip header
foreach ($lines as $line) {

    $row = explode("\t", $line); // tab delimited
    
    if (5!=count($row)) {
        // allow at most one blank line
        if (++$skipped > 1) webapp::abort(
            "Compare field count off ".
            var_export($row, true).
            " $wav $compareOut"
        );
        continue;
    }

    foreach ($row as $val) is_numeric($val) // warn
        or webapp::error("Compare expected numeric $val $wav $compareOut");

    0 == $row[0] // first ID is always 0 (reference)
        or webapp::abort("Compare expected 0 ref ".$row[0]." $wav $compareOut");

    ++$i == $row[1] // second ID is the index (target)
        or webapp::abort("Compare expected $i tgt for ref ".$row[0]." $wav $compareOut");

    $dlen= floatval($row[2]);
    $dist= floatval($row[3]);
    $norm= floatval($row[4]);

    // high score means a low difference
    $highScore= 10000.0;
    $score= 0.0;

    if ($dist > 0.0 && $norm > 0.0) {
        $score= ($dlen - $dist) / $norm;
    }

    // avoid negative diff if ever score > high
    $diffs[] = max(0.0, ($highScore - $score) / $highScore);
}

// save comparisons
$db = webapp::newMysqli();
while (count($diffs) > 0) {
    $diff = array_shift($diffs);
    $clipId1 = array_shift($idMap);
    webapp::query($db, "replace into clip_diff values ($clipId0,$clipId1,$diff)");
}
$db->close();

webapp::log(implode(' ',$argv).' done.');
?>
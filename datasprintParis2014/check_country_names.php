<?php

/*
 * script used to compare OECD country names with Worldbank data (in Merged Countries - Sheet 1 .tsv)
 */

ini_set('memory_limit','2G');
$inputfile = "RioMarkers_cleaned.txt";
$file = file("data/" . $inputfile);

for ($i = 1; $i < count($file); $i++) {
    $e = explode("|", $file[$i]);
    $allnames[] = $e[1]; // donor
    $allnames[] = $e[5]; // recipient
}
$allnames = array_unique($allnames);

$edata = "Merged Countries - Sheet 1.tsv";
$efile = file("data/" . $edata);
for ($i = 1; $i < count($efile); $i++) {
    $e = explode("\t", $efile[$i]);
    $wbnames[] = $e[0];
}
$wbnames = array_unique($wbnames);

$intersect = array_intersect($wbnames,$allnames);
print "present in both :\n".implode("\n",$intersect)."\n\n";
$diff = array_diff($wbnames,$allnames);
print "only in merged data:\n".implode("\n",$diff)."\n\n";
$diff = array_diff($allnames,$wbnames);
print "only in OECD data:\n".implode("\n",$diff)."\n\n";

?>

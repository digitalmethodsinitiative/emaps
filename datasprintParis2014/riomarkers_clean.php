<?php
/*
 * script to transform OECD data to something useful
 * 
 * got data from http://stats.oecd.org/Index.aspx?DataSetCode=RIOMARKERS# > export > Related files > whole dataset (06/01/2014)
 * transformed UTF-16 file to UTF-8 with: iconv -f UTF-16 -t UTF-8 'RioMarkers\ entire\ dataset.txt' > Riomarkers_UTF-8.txt
 * remove binary symbols: tr -cd '\11\12\40-\176' < Riomarkers_UTF-8.txt > RioMarkers_UTF-8_noBinary.txt
 * clean by running riomarkers_clean.php (results in RioMarkers_cleaned.txt)
 * 
 * @author Erik Borra <erik@digitalmethods.net>
 */

ini_set('memory_limit', '2G');

$inputfile = "RioMarkers_UTF-8_noBinary.txt";
$file = file("data/".$inputfile);

$c = count($file);
$t = 0;
for ($i = 1; $i < $c - $t; $i++) {
    if (!preg_match("/^\d{4}\|/", $file[$i])) {
        $file[$i - 1] = trim($file[$i - 1]) . " " . $file[$i];
        //print $file[$i-1];
        unset($file[$i]);
        $file = array_values($file);
        $i--;
        $t++;
    }
}
file_put_contents("RioMarkers_cleaned.txt", implode("", $file));
?>

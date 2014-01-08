<?php

/*
 * script to transform OECD data to something useful
 * 
 * got data from http://stats.oecd.org> erc > export > Related files > 2010,2011,2012
 * transformed UTF-16 file to UTF-8 with: iconv -f UTF-16 -t UTF-8 'CRS 2010 data.txt' > CRS2010_UTF-8.txt
 * remove binary symbols: tr -cd '\11\12\40-\176' < CRS2010_UTF-8.txt > CRS2010_UTF-8_noBinary.txt
 * clean by running riomarkers_clean.php (results in CRS2010_cleaned.txt)
 * 
 * @author Erik Borra <erik@digitalmethods.net>
 *
 */

ini_set('memory_limit', '2G');

$inputfile = "CRS2012_UTF-8_noBinary.txt";
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
file_put_contents("data/CRS2012_cleaned.txt", implode("", $file));
?>
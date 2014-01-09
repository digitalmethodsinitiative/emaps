<?php

/*
 * script to transform OECD data to something useful
 * 
 * All development aid data were retrieved from http://stats.oecd.org > Development > Individual Aid Projects (CRS) > Creditor Report System. 
 * Then we exported the data as follows: Export > Related files > 2010,2011,2012
 * We then transformed UTF-16 file to UTF-8 with: iconv -f UTF-16 -t UTF-8 'CRS 2010 data.txt' > CRS2010_UTF-8.txt
 * We then removed binary symbols: tr -cd '\11\12\40-\176' < CRS2010_UTF-8.txt > CRS2010_UTF-8_noBinary.txt
 * We then further cleaned the data by running oecd_clean.php (results in e.g. CRS2010_cleaned.txt)
 * 
 * The cleaned data can be found in the Google Drive > ParisSprintDataSets > Group 5 warehouse folder > data files
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
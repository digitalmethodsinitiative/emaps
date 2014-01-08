<?php
/*
 * script to sum all AID data per country (you can choose recipient or donor country)
 * @author Erik Borra <erik@digitalmethods.net>
 */
ini_set('memory_limit', '2G');

$received = array();
$donated = array();
$files = array("CRS2010_cleaned.txt", "CRS2011_cleaned.txt", "CRS2012_cleaned.txt");
foreach ($files as $filename) {
    addRecipient($filename);
    addDonor($filename);
}
write($received, "oecd_received.tsv");
write($donated, "oecd_donated.tsv");

function write($received, $filename) {
    ksort($received);
    $handle = fopen("data/$filename", "w");
    fwrite($handle, "country\tamount\n");
    foreach ($received as $country => $amount)
        fwrite($handle, "$country\t$amount\n");
    fclose($handle);
}

function addDonor($filename) {
    global $donated;
    $file = file("data/$filename");
    for ($i = 1; $i < count($file); $i++) {
        $e = explode("|", $file[$i]);
        $donor = $e[2];
        $recipient = $e[9];
        $amount = $e[23]; // usd_commitment_defl

        if (!isset($donated[$donor]))
            $donated[$donor] = 0;
        $donated[$donor] += $amount;
    }
}

function addRecipient($filename) {
    global $received;

    $file = file("data/$filename");
    for ($i = 1; $i < count($file); $i++) {
        $e = explode("|", $file[$i]);
        $donor = $e[2];
        $recipient = $e[9];
        $amount = $e[23]; // usd_commitment_defl

        if (!isset($received[$recipient]))
            $received[$recipient] = 0;
        $received[$recipient] += $amount;
    }
}

?>

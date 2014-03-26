<?php

/*
 * This script parses LexisNexis output and outputs it as a tab separated file.
 * 
 * To use this script, go to Lexis Nexis (e.g. http://academic.lexisnexis.nl/uva) and do a search in the news. Select e.g. 'Major World Publications' as 'Source Type', hit enter.
 * Now click 'save' (the little floppy disk on the right hand side).
 * Choose 'HTML' as 'Format', and 'Custom' as 'Document View'. Click 'Modify' next to 'Custom...'. Select 'All Available Document Sections'. Click 'OK - Use Selected Sections'. Enter your document range.
 * Click 'Download' and save the file to a folder which you specifically created for this project.
 * 
 * Once you finished downloading all your result files, fill in the folder you created for this project below, under '$folder'.
 * Then run this script as follows: php parse.php
 * Your results will be stored in the folder you specified as results.csv
 * 
 */

$folder = "/Users/erik/Downloads/lexisnexis/";

date_default_timezone_set('EST');
error_reporting(E_ALL);

$document_index = 0;
$data = $keys = array();

// loop over files in folder
foreach (glob($folder . "/*.HTML") as $filename) {
    // read file
    $file = file_get_contents($filename);

    // split into articles
    $split = explode('NAME="DOC_ID_', $file);
    for ($i = 1; $i < count($split); $i++) {
        $spl = $split[$i];

        // split into fields
        $sp = preg_split("/<DIV CLASS=\".+?\"><P CLASS=\".+?\"><SPAN CLASS=\".+?\">/", $spl);
        for ($j = 1; $j < count($sp); $j++) {
            $s = trim($sp[$j]);

            // parse fields
            $head = trim(preg_replace("/<.*/", "", $s));
            if (preg_match("/\d, \d{4}/", $head)) {
                $data[$document_index]["date"] = strftime("%Y-%m-%d", strtotime($head));
                $data[$document_index]["unix timestamp"] = strtotime($head);
                $keys[] = 'date';
                $keys[] = 'unix timestamp';
            } elseif (!preg_match("/\d/", $head) && preg_match("/:$/",$head)) {
                $data[$document_index][$head] = trim(preg_replace("/[\n\r\t]/", " ", html_entity_decode(strip_tags(preg_replace("/^.*?</", "<", $s)))));
                $keys[] = $head;
            } elseif (!preg_match("/\d+ of \d+ documents/i", $head)) {
                print "DID NOT RECOGNIZE $i: $head\n";
            }
        }
        $document_index++;
    }
}

// write as csv
$handle = fopen($folder . "/results.csv", "w");
$keys = array_unique($keys);
foreach ($keys as $key)
    fwrite($handle, $key . "\t");
fwrite($handle, "\n");
foreach ($data as $doc => $fields) {
    foreach ($keys as $key) {
        if (isset($fields[$key])) {
            fwrite($handle, $fields[$key]);
        }
        fwrite($handle, "\t");
    }
    fwrite($handle, "\n");
}
fclose($handle);
print "Results are available in $folder/results.csv\n";
?>

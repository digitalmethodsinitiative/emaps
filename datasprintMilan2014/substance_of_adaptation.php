<?php

/*
 * This script combines various adaptation funding databases into one JSON file
 * see https://docs.google.com/document/d/1s0HVBtVG9ZVVFOhwS-n3PMdgzlwNTiMUyvrNYjGv8Mc/edit for more info
 * 
 * @author Erik Borra <erik@digitalmethods.net>
 */

ini_set('memory_limit', '2G');
date_default_timezone_set('Europe/Amsterdam');
$datadir = "data";

$jsons = array();
load_databases();
print json_encode($jsons, JSON_PRETTY_PRINT);

function load_databases() {
    load_undp();
    load_ci_grasp();
    load_psi();
    load_climate_wise();
    load_oecd_riomarkers();
    load_climatefundsupdate();
    load_napa();
}

/*
 * UNDP adaptation learning mechanism (http://www.undp-alm.org/) 
 */

function load_undp() {
    global $jsons, $datadir;

    $inputfile = "";
    $file = file($datadir . "/" . $inputfile);
}

/*
 * Ci-Grasp (http://www.pik-potsdam.de/~wrobel/ci_2/)
 */

function load_ci_grasp() {
    global $jsons, $datadir;

    $inputfile = "";
    $file = file($datadir . "/" . $inputfile);
}

/*
 * UNFCCC Private Sector Initiative (http://unfccc.int/adaptation/workstreams/nairobi_work_programme/items/6547.php)
 */

function load_psi() {
    global $jsons, $datadir;

    $inputfile = "";
    $file = file($datadir . "/" . $inputfile);
}

/*
 *  ClimateWise on insurance industry (http://www.climatewise.org.uk/)
 */

function load_climate_wise() {
    global $jsons, $datadir;

    $inputfile = "";
    $file = file($datadir . "/" . $inputfile);
}

/*
 * Load OECD riomarkers data
 * 
 * to generate the data necessary for this script, see https://github.com/digitalmethodsinitiative/emaps/blob/master/datasprintParis2014/riomarkers_clean.php 
 * or take them from the Google Drive > ParisSprintDataSets > Group 5 warehouse folder > data files
 */

function load_oecd_riomarkers() {
    global $jsons, $datadir;

    $inputfile = "RioMarkers_cleaned.txt";
    $file = file($datadir . "/" . $inputfile);

    for ($i = 1; $i < count($file); $i++) {
        $e = explode("|", $file[$i]);
        $climateAdaptation = $e[12];
        if ($climateAdaptation == 2) {
            $obj = new stdClass();
            $obj->source = 'oecd_riomarkers';
            $obj->year = $e[0];
            $obj->donor = $e[1];
            $obj->recipient = $e[5];
            $obj->purpose = $e[7]; // purposeName
            $obj->amount = $e[4]; //(String) $e[4]." - ".sprintf("%.17f",$e[4]); // usd_commitment_defl
            $obj->sector = $e[19];
            $obj->projecttitle = $e[23];
            $jsons[] = $obj;
        }
    }
}

/*
 * multi-lateral funding (according to the data collected by the website http://www.climatefundsupdate.org/)
 */

function load_climatefundsupdate() {
    
}

/*
 * NAPAs (National Adaptation Programs of Action) (http://unfccc.int/adaptation/workstreams/national_adaptation_programmes_of_action/items/4583.php)
 */

function load_napa() {
    
}

?>

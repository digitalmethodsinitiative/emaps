<?php

/*
 * This script combines various adaptation funding databases into one JSON file
 * see https://docs.google.com/document/d/1s0HVBtVG9ZVVFOhwS-n3PMdgzlwNTiMUyvrNYjGv8Mc/edit for more info
 * 
 * @todo, update sources
 * @todo, put amounts in same format (float vs int) + check whether they are adjusted for inflation etc
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
 * 
 * methodology https://drive.google.com/?usp=folder&authuser=0#folders/0B3e-HpGNh9BwcVpPUHlJNkpnVWs
 * data file https://docs.google.com/file/d/0B3e-HpGNh9BwRnYzQVJpTE5HMjQ/edit
 * 
 * @todo: old source
 * 
 */

function load_undp() {
    global $jsons, $datadir;

    $inputfile = "undp.json";
    $file = file_get_contents($datadir . "/" . $inputfile);

    $data = json_decode($file);
    foreach ($data as $d) {
        $obj = new StdClass();
        $obj->source = "undp";
        $obj->year = "n/a";
        $obj->donor = "n/a";
        if (isset($d->data->partners)) {
            if (is_array($d->data->partners))
                $obj->donor = implode(",", $d->data->partners);
            else
                $obj->donor = $d->data->partners;
        }
        $obj->recipient = $d->location;
        $obj->purpose = "n/a";
        if (isset($d->data->{'climate-hazards'})) {
            if (is_array($d->data->{'climate-hazards'}))
                $obj->purpose = implode(",", $d->data->{'climate-hazards'});
            else
                $obj->purpose = $d->data->{'climate-hazards'};
        }
        $obj->amount = "n/a";
        if (isset($d->data->normalized_costs))
            $obj->amount = $d->data->normalized_costs;
        $obj->sector = $d->theme;
        $obj->projecttitle = $d->title;
        $jsons[] = $obj;
    }
}

/*
 * Ci-Grasp (http://www.pik-potsdam.de/~wrobel/ci_2/)
 * 
 * methodology https://drive.google.com/?usp=folder&authuser=0#folders/0B3e-HpGNh9BwcVpPUHlJNkpnVWs
 * json https://docs.google.com/file/d/0B3e-HpGNh9BwZzF0V2VnWmNrazA/edit
 * 
 * @todo: old source
 */

function load_ci_grasp() {
    global $jsons, $datadir;

    $inputfile = "cigrasp.json";
    $file = file_get_contents($datadir . "/" . $inputfile);

    $data = json_decode($file);
    foreach ($data as $d) {
        $obj = new StdClass();
        $obj->source = "cigrasp";
        $obj->year = "n/a";
        $obj->donor = "n/a";
        $obj->recipient = $d->country;
        $obj->purpose = "n/a";
        if (isset($d->overview->stimuli)) {
            if (is_array($d->overview->stimuli))
                $obj->purpose = implode(",", $d->overview->stimuli);
            else
                $obj->purpose = $d->overview->stimuli;
        }
        $obj->amount = $d->project_costs->normalized_costs;
        $obj->sector = $d->overview->sector;
        $obj->projecttitle = $d->title;
        $jsons[] = $obj;
    }
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
 * ClimateWise on insurance industry (http://www.climatewise.org.uk/)
 * 
 * methodology, https://docs.google.com/document/d/1aOIi0ofmjfl-haOt-hbHYGZjDnKBPxzxN6-5SpmuH-0/edit
 * json, https://docs.google.com/file/d/0B94tyKAcHuHBWnRscVUzWUpWTDg/edit, 
 * 
 * @todo: old source, add amount of money and year
 */

function load_climate_wise() {
    global $jsons, $datadir;

    $inputfile = "adaptation_projects.json";
    $file = file_get_contents($datadir . "/" . $inputfile);

    $data = json_decode($file);
    foreach ($data as $d) {
        if ($d->source == "climatewise") {
            $obj = new StdClass();
            $obj->source = $d->source;
            $obj->year = "n/a";
            $obj->donor = "n/a";
            $obj->recipient = implode(",", $d->countries);
            $obj->purpose = implode(",", $d->{'climate-hazards'});
            $obj->amount = "n/a";
            $obj->sector = "n/a";
            if (!empty($d->themes))
                $obj->sector = implode(",", $d->themes);
            $obj->projecttitle = $d->name;
            $jsons[] = $obj;
        }
    }
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

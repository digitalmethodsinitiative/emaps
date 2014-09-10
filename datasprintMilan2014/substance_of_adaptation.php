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

ini_set('memory_limit', '3G');
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
 * json https://docs.google.com/file/d/0B3e-HpGNh9BwRnYzQVJpTE5HMjQ/edit
 * 
 * @todo: old source, there is also another source used in the combined file (adaptation_projects.json) and referenced from https://docs.google.com/document/d/1aOIi0ofmjfl-haOt-hbHYGZjDnKBPxzxN6-5SpmuH-0/edit# section IV
 * 
 */

function load_undp() {
    global $jsons, $datadir;

    $inputfile = "undp.json";
    $file = file_get_contents($datadir . "/" . $inputfile);

    $data = json_decode($file);
    foreach ($data as $d) {
        $obj = new fund();
        $obj->source = "undp";
        if (isset($d->data->normalized_costs))
            $obj->addAmount($d->data->normalized_costs);
        $obj->projecttitle = $d->title;
        if (isset($d->data->partners)) {
            if (is_array($d->data->partners)) {
                foreach ($d->data->partners as $partner)
                    $obj->addDonor($partner);
            } else
                $obj->addDonor($d->data->partners);
        }
        $obj->addRecipient($d->location);
        if (isset($d->data->{'climate-hazards'})) {
            if (is_array($d->data->{'climate-hazards'})) {
                foreach ($d->data->{'climate-hazards'} as $c)
                    $obj->addPurpose($c);
            }else
                $obj->addPurpose($d->data->{'climate-hazards'});
        }

        if (!empty($d->theme)) {
            $sectors = explode("/", $d->theme);
            foreach ($sectors as $s)
                $obj->addSector($s);
        }
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
        $obj = new Fund();

        $obj->source = "cigrasp";
        $obj->addAmount($d->project_costs->normalized_costs);
        $obj->projecttitle = $d->title;

        $obj->addRecipient($d->country);
        if (isset($d->overview->stimuli)) {
            if (is_array($d->overview->stimuli)) {
                foreach ($d->overview->stimuli as $s)
                    $obj->addPurpose($s);
            } else
                $obj->addPurpose($d->overview->stimuli);
        }
        if (isset($d->overview->sector)) {
            $sectors = explode(",", $d->overview->sector);
            foreach ($sectors as $s)
                $obj->addSector($s);
        }

        $jsons[] = $obj;
    }
}

/*
 * UNFCCC Private Sector Initiative (http://unfccc.int/adaptation/workstreams/nairobi_work_programme/items/6547.php)
 * 
 * methodology, https://docs.google.com/document/d/1aOIi0ofmjfl-haOt-hbHYGZjDnKBPxzxN6-5SpmuH-0/edit
 * json, https://docs.google.com/file/d/0B94tyKAcHuHBWnRscVUzWUpWTDg/edit, 
 */

function load_psi() {
    global $jsons, $datadir;

    $inputfile = "adaptation_projects.json";
    $file = file_get_contents($datadir . "/" . $inputfile);

    $data = json_decode($file);
    foreach ($data as $d) {
        if ($d->source == "psi") {
            $obj = new fund();
            $obj->source = $d->source;
            $obj->projecttitle = $d->name;
            foreach ($d->countries as $c)
                $obj->addRecipient($c);
            foreach ($d->{'climate-hazards'} as $c)
                $obj->addPurpose($c);
            if (!empty($d->themes)) {
                foreach ($d->themes as $theme) {
                    $sectors = explode("/", $theme);
                    foreach ($sectors as $s)
                        $obj->addSector($s);
                }
            }
            $jsons[] = $obj;
        }
    }
}

/*
 * ClimateWise on insurance industry (http://www.climatewise.org.uk/)
 * 
 * methodology, https://docs.google.com/document/d/1aOIi0ofmjfl-haOt-hbHYGZjDnKBPxzxN6-5SpmuH-0/edit
 * json, https://docs.google.com/file/d/0B94tyKAcHuHBWnRscVUzWUpWTDg/edit, 
 * 
 * does not have amount of money and year
 */

function load_climate_wise() {
    global $jsons, $datadir;

    $inputfile = "adaptation_projects.json";
    $file = file_get_contents($datadir . "/" . $inputfile);

    $data = json_decode($file);
    foreach ($data as $d) {
        if ($d->source == "climatewise") {
            $obj = new fund();
            $obj->source = $d->source;
            $obj->projecttitle = $d->name;
            foreach ($d->countries as $c)
                $obj->addRecipient($c);
            foreach ($d->{'climate-hazards'} as $c)
                $obj->addPurpose($c);
            if (!empty($d->themes)) {
                foreach ($d->themes as $theme) {
                    $sectors = explode("/", $theme);
                    foreach ($sectors as $s)
                        $obj->addSector($s);
                }
            }
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
            $obj = new fund();
            $obj->source = 'oecd_riomarkers';
            $obj->year = $e[0];
            $obj->addDonor($e[1]);
            $obj->addRecipient($e[5]);
            $obj->addPurpose($e[7]); // purposeName
            $obj->addAmount($e[4]); //(String) $e[4]." - ".sprintf("%.17f",$e[4]); // usd_commitment_defl
            $obj->addSector($e[19]);
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
 * NAPAs (National Adaptation Programs of Action)
 * 
 * From the NAPA priorities database (http://unfccc.int/adaptation/workstreams/national_adaptation_programmes_of_action/items/4583txt.php) 
 * the data by sector (http://unfccc.int/files/cooperation_support/least_developed_countries_portal/napa_priorities_database/application/pdf/napa_index_by_sector.pdf) was downloaded and 
 * parsed into a csv (data/napa-full-categories.csv) with the following 
 * fields: Title of project, country, indicative cost of project in USD or AUD, order of priority of project, sector component (basically, key words). 
 */

function load_napa() {
    global $jsons, $datadir;

    $inputfile = "napa-full-categories.csv";
    $file = file($datadir . "/" . $inputfile);
    $cf = count($file);
    for ($i = 1; $i < $cf; $i++) {
        $e = explode(";", $file[$i]);
        $obj = new fund();
        $obj->source = "napa";
        $obj->projecttitle = $e[4];
        $obj->recipient = $e[2];
        if (!empty($e[5]))
            $obj->addAmount($e[5]); // USD
        else {
            $obj->addAmount($e[6]); // AUD
            $obj->currency = "AUD";
        }
        $keywords = explode(",", $e[7]); // official key words
        foreach ($keywords as $keyword)
            $obj->addPurpose($keyword);
        $sectors = explode("/", $e[1]); // category
        foreach ($sectors as $s)
            $obj->addSector($s);
        $jsons[] = $obj;
    }
}

class fund {

    public $source = "n/a";
    public $projecttitle = "n/a";
    public $year = "n/a";
    public $amount = "n/a";
    public $currency = "USD";
    public $donor = array();
    public $recipient = array();
    public $purpose = array();
    public $sector = array();

    public function addAmount($amount) {
        $this->amount = trim(str_replace(",", "", $amount));
    }

    public function addPurpose($purpose) {
        $this->purpose[] = trim(strtolower($purpose));
    }

    public function addRecipient($recipient) {
        $this->recipient[] = trim($recipient);
    }

    public function addDonor($donor) {
        $this->donor[] = trim($donor);
    }

    public function addSector($sector) {
        $this->sector[] = trim(strtolower($sector));
    }

}

?>

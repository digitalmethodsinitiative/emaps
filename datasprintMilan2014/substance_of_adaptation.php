<?php

/*
 * This script combines various adaptation funding databases into one JSON file
 * see https://docs.google.com/document/d/1s0HVBtVG9ZVVFOhwS-n3PMdgzlwNTiMUyvrNYjGv8Mc/edit for more info
 * 
 * @todo are all amounts in million?
 * 
 * @author Erik Borra <erik@digitalmethods.net>
 */

ini_set('memory_limit', '3G');
date_default_timezone_set('Europe/Amsterdam');
$datadir = "matrix_viz/data";

$jsons = array();
load_databases();
mapSectors();
mapRecipients();
file_put_contents($datadir . "/" . "substance_of_adaptation.json", json_encode($jsons, JSON_PRETTY_PRINT));
writeAsCsv();

// source, field, field value, nr of projects per country

//generateListOfPurposesAndThemes();

function load_databases() {
    load_undp_alm();
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
 * methodology for project7,8,9 can be found on https://drive.google.com/?usp=folder&authuser=0#folders/0B3e-HpGNh9BwcVpPUHlJNkpnVWs
 * data 
 *      date of retrieval: april 22, 2014
 *      http://www.undp-alm.org/explore was queried with empty fields and the resulting URLs were retrieved to get at the project pages, which were then parsed.
 *      encoded in undp.json  found here https://docs.google.com/file/d/0B3e-HpGNh9BwRnYzQVJpTE5HMjQ/edit
 *      code available at https://github.com/digitalmethodsinitiative/emaps/tree/master/code_project_7%2C8%2C9
 * 
 *      The first 31 rows in the UNDP-ALM database were dropped and not used data calculations as they contain different records to the rest of the database and do not include details of projects.  Instead, they outline country-level NAP processes (for 24 countries) and details of P-CBA, at the  country-level (for 7 countries), with varying levels of detail.  They do not contain the same variables as the rest of the database.
 */

function load_undp_alm() {
    global $jsons, $datadir;

    $inputfile = "undp.json";
    $file = file_get_contents($datadir . "/" . $inputfile);

    $data = json_decode($file);
    
    foreach ($data as $i => $d) {
        if ($i < 31)
            continue;
        $obj = new fund();
        $obj->id = $i+1;
        $obj->source = "undp_alm";
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
            $obj->addSector($d->theme);
        }
        $jsons[] = $obj;
    }
}

/*
 * Ci-Grasp (http://www.pik-potsdam.de/~wrobel/ci_2/)
 * @todo description
 * 
 * methodology https://drive.google.com/?usp=folder&authuser=0#folders/0B3e-HpGNh9BwcVpPUHlJNkpnVWs 
 *
 * data retrieved from http://www.pik-potsdam.de/~wrobel/ci_2/adaptation_projects/
 * json https://docs.google.com/file/d/0B3e-HpGNh9BwZzF0V2VnWmNrazA/edit
 * code available at https://github.com/digitalmethodsinitiative/emaps/tree/master/code_project_7%2C8%2C9
 * 
 */

function load_ci_grasp() {
    global $jsons, $datadir;

    $inputfile = "cigrasp.json";
    $file = file_get_contents($datadir . "/" . $inputfile);

    $data = json_decode($file);
    foreach ($data as $i => $d) {
        $obj = new Fund();
        $obj->id = $i + 1;
        $obj->source = "cigrasp";
        $obj->addAmount($d->project_costs->normalized_costs);
        $obj->projecttitle = $d->title;
        $countries = preg_split("/[;,]/", $d->country);
        foreach ($countries as $c)
            $obj->addRecipient($c);
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
 * case studies submitted by private sector organizations and other stakeholders that show innovative activities of adaptation to climate change. 
 * 
 * methodology, https://docs.google.com/document/d/1aOIi0ofmjfl-haOt-hbHYGZjDnKBPxzxN6-5SpmuH-0/edit
 * json, https://docs.google.com/file/d/0B94tyKAcHuHBWnRscVUzWUpWTDg/edit, 
 * data
 *      retrieved from http://unfccc.int/adaptation/workstreams/nairobi_work_programme/items/6547.php   
 *      url was scraped for title of project and country. From the listing non-specific countries (e.g. ALL and regions) were removed.
 *      Recoded the variables listed under ‘Main adaptation area relevant to case study’ to correspond with the UNDP ALM database’ ‘thematic areas’ categories. I.e. 
 *          ' Agriculture, forestry and fisheries' => 'Agriculture/Food Security',
 *           'Food' => 'Agriculture/Food Security',
 *           'Food security' => 'Agriculture/Food Security',
 *           'Capacity building' => 'Disaster Risk Reduction',
 *           'Education and training' => 'Disaster Risk Reduction',
 *           'Capacity building, education and training' => 'Disaster Risk Reduction',
 *           'Science, assessment, monitoring and early warning' => 'Disaster Risk Reduction',
 *           'Technology and Information & Communications Technology (ICT)' => 'Infrastructure/Climate Change Risk Management',
 *           'Construction and Engineering' => 'Infrastructure/Climate Change Risk Management',
 *           'Energy and Utilities' => 'Infrastructure/Climate Change Risk Management',
 *           'Transport, infrastructure and human settlements' => 'Infrastructure/Climate Change Risk Management',
 *           'Infrastructure and human settlements' => 'Infrastructure/Climate Change Risk Management',
 *           'Finance and insurance' => 'Infrastructure/Climate Change Risk Management',
 *           'Human health' => 'Health',
 *           'Oceans and coastal areas' => 'Coastal Zone Development',
 *           'Renewable energy systems' => 'Natural Resource Management',
 *           'Terrestrial ecosystems' => 'Rural Development',
 *           'Water resources' => 'Water Resources'
 */

function load_psi() {
    global $jsons, $datadir;

    $inputfile = "adaptation_projects.json";
    $file = file_get_contents($datadir . "/" . $inputfile);

    $data = json_decode($file);
    $i = 0;
    foreach ($data as $d) {
        if ($d->source == "psi") {
            $i++;
            $obj = new fund();
            $obj->id = $i;
            $obj->source = $d->source;
            $obj->projecttitle = $d->name;
            foreach ($d->countries as $c)
                $obj->addRecipient($c);
            foreach ($d->{'climate-hazards'} as $c)
                $obj->addPurpose($c);
            if (!empty($d->themes)) {
                foreach ($d->themes as $theme) {
                    $obj->addSector($theme);
                }
            }
            $jsons[] = $obj;
        }
    }
}

/*
 * ClimateWise on insurance industry (http://www.climatewise.org.uk/)
 * Documents existing initiatives in middle income and lower income countries that involve the transfer of risk associated to the occurrence of natural hazards, which are referred to as ‘schemes’. Includes over 120 schemes. 
 * 
 * methodology, https://docs.google.com/document/d/1aOIi0ofmjfl-haOt-hbHYGZjDnKBPxzxN6-5SpmuH-0/edit
 * json, https://docs.google.com/file/d/0B94tyKAcHuHBWnRscVUzWUpWTDg/edit, 
 * 
 * data
 *      retrieved from http://www.climatewise.org.uk/storage/climatewise-docs/ClimateWise%20Compendium%20of%20disaster%20risk%20transfer%20initiatives%20in%20the%20developing%20world.xlsm
 *      See https://docs.google.com/document/d/1aOIi0ofmjfl-haOt-hbHYGZjDnKBPxzxN6-5SpmuH-0/edit# section IV for a detailed description of how the data was cleaned
 *      @todo Theme was derived by recoding the variables listed under ‘risk reduction and adaptation’ (‘stages of risk management involved in initiative’’) to correspond with the UNDP ALM database’ ‘thematic areas’ categories. I.e. 
 *          Agriculture/Food Security
 *          Coastal Zone Development
 *          Disaster Risk Reduction
 *          Health
 *          Infrastructure/Climate Change Risk Management
 *          Natural Resource Management
 *          Rural Development
 *          Water Resources
 */

function load_climate_wise() {
    global $jsons, $datadir;

    $inputfile = "adaptation_projects.json";
    $file = file_get_contents($datadir . "/" . $inputfile);

    $data = json_decode($file);
    $i = 0;
    foreach ($data as $d) {
        if ($d->source == "climatewise") {
            $obj = new fund();
            $i++;
            $obj->id = $i;
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
 * no special cleaning was done
 * only projects which have climateAdaptation as their primary goal are included in this data
 * 
 * Note: cat data/RioMarkers_cleaned.txt | grep 'Least Developed Country Fund for Adaptation to Climate Change' | cut -d"|" -f2,4,6,8,20 | wc -l
  196 => Canada has reported that it spent 0.103124M to 49 countries in 4 categories
 */

function load_oecd_riomarkers() {
    global $jsons, $datadir;

    $inputfile = "RioMarkers_cleaned.txt";
    $file = file($datadir . "/" . $inputfile);

    $j = 0;
    for ($i = 1; $i < count($file); $i++) {
        $e = explode("|", $file[$i]);
        $climateAdaptation = $e[12];
        if ($climateAdaptation == 2) {
            $obj = new fund();
            $j++;
            $obj->id = $j;
            $obj->source = 'oecd_riomarkers';
            $obj->year = $e[0];
            $obj->addDonor($e[1]);
            $obj->addRecipient($e[5]);
            $obj->addPurpose($e[7]); // purposeName
            $obj->addAmount(round((float) $e[3] * 1000000));
            $obj->addSector($e[19]);
            $obj->projecttitle = $e[23];
            $jsons[] = $obj;
        }
    }
}

/*
 * multi-lateral funding (according to the data collected by the website http://www.climatefundsupdate.org/)
 * 
 * methodology: https://docs.google.com/document/d/1j-kVvTrRGjgZZ8Dj81IdrZ1RNLF1UWsyhQV36X41Ze4/edit
 * 
 * Retrieved data from http://www.climatefundsupdate.org/data
 * filtered out all projects that were not funded through the MLFs
 * tagged with categories or keywords: To fill in this data we manually added this information by using the words in the title plus doing some desk research to find project reports and other sources of information to get a sense of what the projects were about.
 * checked keywords: qualitative analysis to assign napa style categories/sectors to the projects
 * resulting csv: climatefundsupdate-multilateral.csv
 * 
 * * @todo: are keywords same as purpose, are categories same as sector?
 */

function load_climatefundsupdate() {
    global $jsons, $datadir;

    $inputfile = "climatefundsupdate-multilateral.csv";
    $file = file($datadir . "/" . $inputfile);
    $cf = count($file);
    for ($i = 1; $i < $cf; $i++) {
        $obj = new fund();
        $obj->id = $i;
        $obj->source = "climatefundsupdate";

        $e = explode(";", $file[$i]);
        $obj->projecttitle = preg_replace("/ - \d+/", "", $e[16]);
        $obj->addRecipient($e[19]);
        $obj->addDonor($e[22]);
        $obj->year = $e[23];
        $obj->addAmount((float) trim($e[27]) * 1000000);
        $sectors = explode("/", $e[12]); // category
        foreach ($sectors as $s)
            $obj->addSector($s);
        if (!empty($e[13])) // checked keyword
            $obj->addPurpose($e[13]);
        if (!empty($e[14])) // checked keyword
            $obj->addPurpose($e[14]);
        if (!empty($e[15])) // checked keyword
            $obj->addPurpose($e[15]);

        $jsons[] = $obj;
    }
}

/*
 * NAPAs (National Adaptation Programs of Action)
 * 
 * methodology: https://docs.google.com/document/d/1j-kVvTrRGjgZZ8Dj81IdrZ1RNLF1UWsyhQV36X41Ze4/edit
 * 
 * From the NAPA priorities database (http://unfccc.int/adaptation/workstreams/national_adaptation_programmes_of_action/items/4583txt.php) 
 * the data by sector (http://unfccc.int/files/cooperation_support/least_developed_countries_portal/napa_priorities_database/application/pdf/napa_index_by_sector.pdf) was downloaded and 
 * parsed into a csv (data/napa-full-categories.csv) with the following 
 * fields: Title of project, country, indicative cost of project in USD or AUD, order of priority of project, sector component (basically, key words).
 * 
 * @todo: USD vs AUD, are offical keywords same as purpose, are categories same as sector? 
 */

function load_napa() {
    global $jsons, $datadir;

    $inputfile = "napa-full-categories.csv";
    $file = file($datadir . "/" . $inputfile);
    $cf = count($file);
    for ($i = 1; $i < $cf; $i++) {
        $e = explode(";", $file[$i]);
        $obj = new fund();
        $obj->id = $i;
        $obj->source = "napa";
        $obj->projecttitle = $e[4];
        $obj->addRecipient($e[2]);
        if (!empty($e[5]))
            $obj->addAmount(trim(str_replace(",", "", $e[5]))); // USD
        else {
            $obj->addAmount(trim(str_replace(",", "", $e[6]))); // AUD
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

function mapRecipients() {
    global $jsons, $datadir;
    $file = file($datadir . "/substance_of_adaptation_mapping_of_countries_manual.csv"); // generated by map_countries.php (via Yahoo! Geo encoding) and verifying manually
    $sc = count($file);
    for ($i = 1; $i < $sc; $i++) {
        $e = explode(";", $file[$i]);
        $source = trim($e[0]);
        $recipient = trim($e[1]);
        $recipient_mapped = trim($e[2]);
        $recipient_mappings[$source][$recipient] = $recipient_mapped;
    }
    foreach ($jsons as $k => $obj) {
        foreach ($obj->recipient as $l => $recipient) {
            if (empty($recipient))
                $obj->recipient_mapped[$l] = "Non-specific";
            else
                $obj->recipient_mapped[$l] = $recipient_mappings[$obj->source][$recipient];
            $jsons[$k] = $obj;
        }
    }
}

function mapSectors() {
    global $jsons, $datadir;
    $file = file($datadir . "/substance_of_adaptation_mapping_of_sectors.csv"); // generated by manually mapping all sectors to the undp_alm scheme, see also @todo
    $sc = count($file);
    for ($i = 1; $i < $sc; $i++) {
        $e = explode(";", $file[$i]);
        $source = trim($e[0]);
        $sector = trim($e[1]);
        $sector_mapped = trim($e[2]);
        $sector_mappings[$source][$sector] = $sector_mapped;
    }
    foreach ($jsons as $k => $obj) {
        foreach ($obj->sector as $l => $sector) {
            $obj->sector_mapped[$l] = $sector_mappings[$obj->source][$sector];
            $jsons[$k] = $obj;
        }
    }
}

function generateListOfPurposesAndThemes() {
    global $jsons, $datadir;
    $sectors = $purposes = array();
    foreach ($jsons as $obj) {
        foreach ($obj->sector as $s)
            $sectors[$obj->source][] = $s;
        foreach ($obj->purpose as $p)
            $purposes[$obj->source][] = $p;
        foreach ($obj->recipient as $r)
            $recipients[$obj->source][] = $r;
    }
    $handle = fopen($datadir . "/substance_of_adaptation_unique_sectors.csv", "w");
    fwrite($handle, "source;sector\n");
    foreach ($sectors as $source => $s) {
        $ss = array_unique($s);
        sort($ss);
        foreach ($ss as $si)
            fwrite($handle, "$source;$si\n");
    }
    fclose($handle);
    $handle = fopen($datadir . "/substance_of_adaptation_unique_purposes.csv", "w");
    fwrite($handle, "source;purpose\n");
    foreach ($purposes as $source => $p) {
        $pp = array_unique($p);
        sort($pp);
        foreach ($pp as $pi)
            fwrite($handle, "$source;$pi\n");
    }
    fclose($handle);
    $handle = fopen($datadir . "/substance_of_adaptation_unique_recipients.csv", "w");
    fwrite($handle, "source;recipient\n");
    foreach ($recipients as $source => $r) {
        $rr = array_unique($r);
        sort($rr);
        foreach ($rr as $ri)
            fwrite($handle, "$source;$ri\n");
    }
    fclose($handle);
}

function writeAsCsv() {
    global $jsons, $datadir;
    $csv = "source;id;projecttitle;year;amount;currency;donor;recipient;recipient_mapped;purpose;sector;sector_mapped\n";
    foreach ($jsons as $j) {
        $csv .= $j->source . ";" . $j->id . ";" . $j->projecttitle . ";" . $j->year . ";" . $j->amount . ";" . $j->currency . ";" . implode(",", $j->donor) . ";" . implode(",", $j->recipient) . ";" . implode(",", $j->recipient_mapped) . ";" . implode(",", $j->purpose) . ";" . implode(",", $j->sector) . ";" . implode(",", $j->sector_mapped) . "\n";
    }
    file_put_contents($datadir . "/substance_of_adaptation.csv", $csv);
}

class fund {

    public $id = 0;
    public $source = "n/a";
    public $projecttitle = "n/a";
    public $year = "n/a";
    public $amount = "n/a";
    public $currency = "USD";
    public $donor = array();
    public $recipient = array();
    public $recipient_mapped = array();
    public $purpose = array();
    public $sector = array();
    public $sector_mapped = array();

    public function addAmount($amount) {
        $this->amount = $amount;
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

<?php

/*
 * does various counts
 * look at riomarkers_clean.php to generate the data necessary for this script, or take them from the Google Drive > ParisSprintDataSets > Group 5 warehouse folder > data files
 * @author Erik Borra <erik@digitalmethods.net>
 */

ini_set('memory_limit', '2G');

$what = "sumofaid";
$what = "specializationPerSector";
$what = "specializationPerRegion";
$what = "specializationPerPurposeName";
$what = "specializationPerCustomRegion";
$what = "specializationPerIncomeGroup";
$what = "specializationPerUNFCCGroup";
$what = "amountGivenPerGdpPerCountry"; // @todo (above average and below average 
$what = "amountGivenPerGdpTotal"; // @todo (above average and below average 
$what = "adaptationVsTotalReceived";
$what = "adaptationVsTotalDonated";
$what = "donorRecipientProjectAmount";

$inputfile = "RioMarkers_cleaned.txt";
$file = file("data/" . $inputfile);
$headers = explode("|", $file[0]);
//var_dump($headers);
// load extra data
if (array_search($what, array("specializationPerCustomRegion", "specializationPerIncomeGroup", "specializationPerUNFCCGroup", "amountGivenPerGdpPerCountry", "amountGivenPerGdpTotal")) !== false) {
    $edata = "Merged Countries - Sheet 1.tsv";
    $efile = file("data/" . $edata);
    // name = 0
    // region = 11  
    // incomeGroup = 12
    // ag 13, apg 14, grulac 15, eeg 16, weog 17, ddc 18, eit 19, 
    // dge 20, ocde 21, bric 22, g8 23, g20 24, g77 25, aocgcm 26, a1 27, na1 28, ldc 29, 
    // aosis 30, eu 31, ug 32, cfrn 33, cacam 34, opec 35, las 36, eig 37, ailac 38, alba 39,
    // oif 40
    foreach ($efile as $ef) {
        $e = explode("\t", $ef);
        $eCountry = $e[0];
        $eRegions[$eCountry] = $e[11];
        $eIncomeGroups[$eCountry] = $e[12];
        for ($i = 13; $i < 41; $i++) {
            $eUNFCCGroups[$eCountry][] = $e[$i];
        }
        $eUNFCCGroups[$eCountry] = array_unique($eUNFCCGroups[$eCountry]);
        asort($eUNFCCGroups[$eCountry]);
        $eGdpAvg[$eCountry] = ($e[6] + $e[7] + $e[8]) / 3;
    }
}

if ($what == "adaptationVsTotalReceived") {
    $edata = "data/oecd_received.tsv";
    $efile = file($edata);
    for ($i = 1; $i < count($efile); $i++) {
        $e = explode("\t", $efile[$i]);
        $ereceived[$e[0]] = trim($e[1]);
    }
}
if ($what == "adaptationVsTotalDonated") {
    $edata = "data/oecd_donated.tsv";
    $efile = file($edata);
    for ($i = 1; $i < count($efile); $i++) {
        $e = explode("\t", $efile[$i]);
        $edonated[$e[0]] = trim($e[1]);
    }
}
//var_dump(explode("|",$file[0])); die;
for ($i = 1; $i < count($file); $i++) {
    $e = explode("|", $file[$i]);
    $donor = $e[1];
    $recipient = $e[5];
    $purposeName = $e[7];
    $climateMitigation = $e[11];
    $climateAdaptation = $e[12];
    $amount = $e[4]; //round($e[4] * 1000000,0); // usd_commitment_defl
    $sector = $e[19];
    $region = $e[14];
    $projecttitle = $e[23];

    if ($climateAdaptation == 2) {
        switch ($what) {
            case "sumofaid":
            case "adaptationVsTotalReceived":
                if (!isset($sums[$recipient]))
                    $sums[$recipient] = 0;
                $sums[$recipient] += $amount;
                break;
            case "adaptationVsTotalDonated":
                if (!isset($sums[$donor]))
                    $sums[$donor] = 0;
                $sums[$donor] += $amount;
                break;
            case "specializationPerSector":
                if (!isset($countries[$donor][$sector])) {
                    $countries[$donor][$sector]['count'] = 0;
                    $countries[$donor][$sector]['totalAmount'] = 0;
                }
                $countries[$donor][$sector]['count']++;
                $countries[$donor][$sector]['totalAmount']+=$amount;
                break;
            case "specializationPerRegion":
                if (!isset($countries[$donor][$region])) {
                    $countries[$donor][$region]['count'] = 0;
                    $countries[$donor][$region]['totalAmount'] = 0;
                }
                $countries[$donor][$region]['count']++;
                $countries[$donor][$region]['totalAmount']+=$amount;
                break;
            case "specializationPerPurposeName":
                if (!isset($countries[$donor][$purposeName])) {
                    $countries[$donor][$purposeName]['count'] = 0;
                    $countries[$donor][$purposeName]['totalAmount'] = 0;
                }
                $countries[$donor][$purposeName]['count']++;
                $countries[$donor][$purposeName]['totalAmount']+=$amount;
                break;
            case "specializationPerCustomRegion":
                if (!isset($eRegions[$recipient]))
                    print $recipient . " not found in eRegions\n";
                else {
                    $region = $eRegions[$recipient];
                    if (!isset($countries[$donor][$region])) {
                        $countries[$donor][$region]['count'] = 0;
                        $countries[$donor][$region]['totalAmount'] = 0;
                    }
                    $countries[$donor][$region]['count']++;
                    $countries[$donor][$region]['totalAmount']+=$amount;
                }
                break;
            case "specializationPerIncomeGroup":
                if (!isset($eIncomeGroups[$recipient]))
                    print $recipient . " not found in eIncomeGroups\n";
                else {
                    $ig = $eIncomeGroups[$recipient];
                    if (!isset($countries[$donor][$ig])) {
                        $countries[$donor][$ig]['count'] = 0;
                        $countries[$donor][$ig]['totalAmount'] = 0;
                    }
                    $countries[$donor][$ig]['count']++;
                    $countries[$donor][$ig]['totalAmount']+=$amount;
                }
                break;
            case "specializationPerUNFCCGroup":
                if (!isset($eUNFCCGroups[$recipient]))
                    print $recipient . " not found in eUNFCCGroups\n";
                else {
                    $ugs = $eUNFCCGroups[$recipient];
                    foreach ($ugs as $ug) {
                        if ($ug == "")
                            continue;
                        if (!isset($countries[$donor][$ug])) {
                            $countries[$donor][$ug]['count'] = 0;
                            $countries[$donor][$ug]['totalAmount'] = 0;
                        }
                        $countries[$donor][$ug]['count']++;
                        $countries[$donor][$ug]['totalAmount']+=$amount;
                    }
                }
                break;
            case "amountGivenPerGdpPerCountry":
            case "amountGivenPerGdpTotal":
                if (!isset($countries[$donor][$recipient])) {
                    $countries[$donor][$recipient]['count'] = 0;
                    $countries[$donor][$recipient]['totalAmount'] = 0;
                }
                $countries[$donor][$recipient]['count']++;
                $countries[$donor][$recipient]['totalAmount']+=$amount;
                break;
            case "donorRecipientProjectAmount":
                if (!isset($countries[$donor][$recipient][$projecttitle])) {
                    $countries[$donor][$recipient][$projecttitle] = 0;
                }
                $countries[$donor][$recipient][$projecttitle] += $amount;
                break;
            default:
                break;
        }
    }
}

switch ($what) {
    case "sumofaid":
        arsort($sums);
        $i = 0;
        print "recipient\tamount\n";
        foreach ($sums as $recipient => $amount) {
            if ($i >= 50)
                continue;
            print $recipient . "\t" . $amount . "\n";
            $i++;
        }
        break;
    case "adaptationVsTotalReceived":
        ksort($sums);
        $i = 0;
        print "recipient\tadaptation received\ttotal received\tshare of adaptation\n";
        foreach ($sums as $recipient => $amount) {
            if (!isset($ereceived[$recipient])) {
                print "$recipient not found in ereceived\n";
                continue;
            }
            if ($ereceived[$recipient] != 0)
                $share = ($amount / $ereceived[$recipient]);
            else
                $share = 0;
            print $recipient . "\t" . $amount . "\t" . $ereceived[$recipient] . "\t" . $share . "\n";
            $i++;
        }
        break;
    case "adaptationVsTotalDonated":
        ksort($sums);
        $i = 0;
        print "recipient\tadaptation donated\ttotal donated\tshare of adaptation\n";
        foreach ($sums as $donor => $amount) {
            if (!isset($edonated[$donor])) {
                print "$donor not found in edonated\n";
                continue;
            }
            if ($edonated[$donor] != 0)
                $share = ($amount / $edonated[$donor]);
            else
                $share = 0;
            print $donor . "\t" . $amount . "\t" . $edonated[$donor] . "\t" . $share . "\n";
            $i++;
        }
        break;
    case "adaptationVsTotalReceived":
    case "adaptationVsTotalDonated":
    case "specializationPerSector":
        ksort($countries);
        print "donor\tsector\tnr of projects\ttotal amount\n";
        foreach ($countries as $country => $sectors) {
            arsort($sectors);
            foreach ($sectors as $sector => $ar)
                print $country . "\t" . $sector . "\t" . $ar['count'] . "\t=ROUND(" . $ar['totalAmount'] . ",2)\n";
        }
        break;
    case "specializationPerPurposeName":
        ksort($countries);
        print "donor\tpurposeName\tnr of projects\ttotal amount\n";
        foreach ($countries as $country => $purposeNames) {
            arsort($purposeNames);
            foreach ($purposeNames as $purposeName => $ar)
                print $country . "\t" . $purposeName . "\t" . $ar['count'] . "\t=ROUND(" . $ar['totalAmount'] . ",2)\n";
        }
        break;
    case "specializationPerRegion":
        ksort($countries);
        print "donor\tregion\tnr of projects\ttotal amount\n";
        foreach ($countries as $country => $regions) {
            arsort($regions);
            foreach ($regions as $region => $ar)
                print $country . "\t" . $region . "\t" . $ar['count'] . "\t=ROUND(" . $ar['totalAmount'] . ",2)\n";
        }
        break;
    case "specializationPerCustomRegion":
        ksort($countries);
        print "donor\tcustom region\tnr of projects\ttotal amount\n";
        foreach ($countries as $country => $regions) {
            arsort($regions);
            foreach ($regions as $region => $ar)
                print $country . "\t" . $region . "\t" . $ar['count'] . "\t=ROUND(" . $ar['totalAmount'] . ",2)\n";
        }
        break;
    case "specializationPerIncomeGroup":
        ksort($countries);
        print "donor\tIncome Group\tnr of projects\ttotal amount\n";
        foreach ($countries as $country => $igs) {
            arsort($igs);
            foreach ($igs as $ig => $ar)
                print $country . "\t" . $ig . "\t" . $ar['count'] . "\t=ROUND(" . $ar['totalAmount'] . ",2)\n";
        }
        break;
    case "specializationPerUNFCCGroup":
        ksort($countries);

        print "donor\tUNFCC Group\tnr of projects\ttotal amount\n";

        include_once('GEXF-library/Gexf.class.php');

        $gexf = new Gexf();
        $filename = "OECD RioMarkers - adaptation - 2 - country - UNFCCGroup";
        $gexf->setTitle("RioMarkers 20140106 " . $filename);
        $gexf->setEdgeType(GEXF_EDGE_DIRECTED);
        $gexf->setCreator("tools.digitalmethods.net");

        foreach ($countries as $country => $ugs) {
            arsort($ugs);
            foreach ($ugs as $ug => $ar) {
                print $country . "\t" . $ug . "\t" . $ar['count'] . "\t=ROUND(" . $ar['totalAmount'] . ",2)\n";

                // also create network
                $node1 = new GexfNode($country);
                $node1->addNodeAttribute("type", 'country', $type = "string");
                $gexf->addNode($node1);

                $node2 = new GexfNode($ug);
                $node2->addNodeAttribute("type", 'UNFCCGroup', $type = "string");
                $gexf->addNode($node2);

                $edge_id = $gexf->addEdge($node1, $node2, $ar['totalAmount']);
            }
        }
        // render the file
        $gexf->render();

        // write out the file
        file_put_contents("results/" . $filename . '.gexf', $gexf->gexfFile);
        break;
    case "amountGivenPerGdpPerCountry":
        ksort($countries);
        print "donor\trecipient\tnr of projects\ttotal amount\tamount per gdp (avg)\n";
        foreach ($countries as $donor => $recipients) {
            if (!isset($eGdpAvg[$donor])) {
                print $donor . " not found in eGdpAvg\n";
            } else {
                ksort($recipients);
                foreach ($recipients as $recipient => $ar) {
                    print $donor . "\t" . $recipient . "\t" . $ar['count'] . "\t" . $ar['totalAmount'] . "\t" . ($ar['totalAmount'] / $eGdpAvg[$donor]) . "\n";
                }
            }
        }
        break;
    case "amountGivenPerGdpTotal":
        ksort($countries);
        print "donor\tnr of projects\ttotal amount\tamount per gdp (avg)\n";
        foreach ($countries as $donor => $recipients) {
            if (!isset($eGdpAvg[$donor])) {
                print $donor . " not found in eGdpAvg\n";
            } else {
                $total = $count = 0;
                foreach ($recipients as $ar) {
                    $count += $ar['count'];
                    $total += $ar['totalAmount'];
                }
                print $donor . "\t" . $count . "\t" . $total . "\t" . ($total / $eGdpAvg[$donor]) . "\n";
            }
        }
        break;
    case "donorRecipientProjectAmount":
        ksort($countries);
        print "donor\trecipient\tamount\tproject title\n";
        foreach ($countries as $donor => $recipients) {
            ksort($recipients);
            foreach ($recipients as $recipient => $projects) {
                foreach ($projects as $projecttitle => $amount) {
                    $projecttitle = str_replace("\t", " ", $projecttitle);
                    print "$donor\t$recipient\t$amount\t$projecttitle\n";
                }
            }
        }
        break;
    default:
        break;
}
?>

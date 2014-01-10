<?php

/*
 * does various counts
 * look at riomarkers_clean.php to generate the data necessary for this script, or take them from the Google Drive > ParisSprintDataSets > Group 5 warehouse folder > data files
 * @author Erik Borra <erik@digitalmethods.net>
 */

ini_set('memory_limit', '2G');

/*
 *  specify here what visualization to produce
 */
$what = "sumofaid";
//$what = "donorSpecializationPerSector";
//$what = "donorSpecializationPerPurposeName";
//$what = "donorSpecializationPerRegion";
//$what = "donorSpecializationPerCustomRegion";
//$what = "donorSpecializationPerIncomeGroup";
//$what = "donorSpecializationPerUNFCCGroup";
//$what = "recipientSpecializationPerSector";
//$what = "recipientSpecializationPerPurposeName";
//$what = "recipientSpecializationPerRegion";
//$what = "recipientSpecializationPerCustomRegion"; // @todo, weird
//$what = "recipientSpecializationPerIncomeGroup";
//$what = "recipientSpecializationPerUNFCCGroup";
//$what = "amountGivenPerGdpPerCountry"; // @todo (above average and below average 
//$what = "amountGivenPerGdpTotal"; // @todo (above average and below average 
//$what = "adaptationVsTotalReceived";
//$what = "adaptationVsTotalDonated";
//$what = "donorRecipientProjectAmount";

/*
 * Load OECD riomaerks data
 */
$inputfile = "RioMarkers_cleaned.txt";
$file = file("data/" . $inputfile);

/*
 * Loop over all rows and group the data according to the specified $what
 */
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

    if ($climateAdaptation == 2) { // only use data for which the principle objective is climate adaptation
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
            case "donorSpecializationPerSector":
                if (!isset($countries[$donor][$sector])) {
                    $countries[$donor][$sector]['count'] = 0;
                    $countries[$donor][$sector]['totalAmount'] = 0;
                }
                $countries[$donor][$sector]['count']++;
                $countries[$donor][$sector]['totalAmount']+=$amount;
                break;
            case "donorSpecializationPerRegion":
                if (!isset($countries[$donor][$region])) {
                    $countries[$donor][$region]['count'] = 0;
                    $countries[$donor][$region]['totalAmount'] = 0;
                }
                $countries[$donor][$region]['count']++;
                $countries[$donor][$region]['totalAmount']+=$amount;
                break;
            case "donorSpecializationPerPurposeName":
                if (!isset($countries[$donor][$purposeName])) {
                    $countries[$donor][$purposeName]['count'] = 0;
                    $countries[$donor][$purposeName]['totalAmount'] = 0;
                }
                $countries[$donor][$purposeName]['count']++;
                $countries[$donor][$purposeName]['totalAmount']+=$amount;
                break;
            case "donorSpecializationPerCustomRegion":
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
            case "donorSpecializationPerIncomeGroup":
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
            case "donorSpecializationPerUNFCCGroup":
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
            case "recipientSpecializationPerSector":
                if (!isset($countries[$recipient][$sector])) {
                    $countries[$recipient][$sector]['count'] = 0;
                    $countries[$recipient][$sector]['totalAmount'] = 0;
                }
                $countries[$recipient][$sector]['count']++;
                $countries[$recipient][$sector]['totalAmount']+=$amount;
                break;
            case "recipientSpecializationPerRegion":
                if (!isset($countries[$recipient][$region])) {
                    $countries[$recipient][$region]['count'] = 0;
                    $countries[$recipient][$region]['totalAmount'] = 0;
                }
                $countries[$recipient][$region]['count']++;
                $countries[$recipient][$region]['totalAmount']+=$amount;
                break;
            case "recipientSpecializationPerPurposeName":
                if (!isset($countries[$recipient][$purposeName])) {
                    $countries[$recipient][$purposeName]['count'] = 0;
                    $countries[$recipient][$purposeName]['totalAmount'] = 0;
                }
                $countries[$recipient][$purposeName]['count']++;
                $countries[$recipient][$purposeName]['totalAmount']+=$amount;
                break;
            case "recipientSpecializationPerCustomRegion":
                if (!isset($eRegions[$donor]))
                    print $donor . " not found in eRegions\n";
                else {
                    $region = $eRegions[$donor];
                    if (!isset($countries[$recipient][$region])) {
                        $countries[$recipient][$region]['count'] = 0;
                        $countries[$recipient][$region]['totalAmount'] = 0;
                    }
                    $countries[$recipient][$region]['count']++;
                    $countries[$recipient][$region]['totalAmount']+=$amount;
                }
                break;
            case "recipientSpecializationPerIncomeGroup":
                if (!isset($eIncomeGroups[$donor]))
                    print $donor . " not found in eIncomeGroups\n";
                else {
                    $ig = $eIncomeGroups[$donor];
                    if (!isset($countries[$recipient][$ig])) {
                        $countries[$recipient][$ig]['count'] = 0;
                        $countries[$recipient][$ig]['totalAmount'] = 0;
                    }
                    $countries[$recipient][$ig]['count']++;
                    $countries[$recipient][$ig]['totalAmount']+=$amount;
                }
                break;
            case "recipientSpecializationPerUNFCCGroup":
                if (!isset($eUNFCCGroups[$donor]))
                    print $donor . " not found in eUNFCCGroups\n";
                else {
                    $ugs = $eUNFCCGroups[$donor];
                    foreach ($ugs as $ug) {
                        if ($ug == "")
                            continue;
                        if (!isset($countries[$recipient][$ug])) {
                            $countries[$recipient][$ug]['count'] = 0;
                            $countries[$recipient][$ug]['totalAmount'] = 0;
                        }
                        $countries[$recipient][$ug]['count']++;
                        $countries[$recipient][$ug]['totalAmount']+=$amount;
                    }
                }
                break;
            default:
                break;
        }
    }
}

/*
 * Load some extra information per country, if necessary
 */
if (array_search($what, array("sumofaid", "donorSpecializationPerSector", "donorSpecializationPerCustomRegion", "donorSpecializationPerIncomeGroup", "donorSpecializationPerPurposeName", "donorSpecializationPerUNFCCGroup", "donorSpecializationPerRegion", "amountGivenPerGdpPerCountry", "amountGivenPerGdpTotal", "recipientSpecializationPerSector", "recipientSpecializationPerCustomRegion", "recipientSpecializationPerIncomeGroup", "recipientSpecializationPerPurposeName", "recipientSpecializationPerUNFCCGroup", "recipientSpecializationPerRegion")) !== false) {
    $edata = "Merged Countries - Sheet 1.tsv";
    $efile = file("data/" . $edata);
    // name = 0
    // Tot Population 2010 2
    // Tot Population 2011 3
    // Tot Population 2012 4
    // Tot Population Average2010-2012 5
    // GDP 2010	6
    // GDP 2011	7
    // GDP 2012	8
    // TotGHG Excluding LUCF 2010 9
    // TotGHG Including LUCF 2010 10
    // region = 11  
    // incomeGroup = 12
    // ag 13, apg 14, grulac 15, eeg 16, weog 17, ddc 18, eit 19, 
    // dge 20, ocde 21, bric 22, g8 23, g20 24, g77 25, aocgcm 26, a1 27, na1 28, ldc 29, 
    // aosis 30, eu 31, ug 32, cfrn 33, cacam 34, opec 35, las 36, eig 37, ailac 38, alba 39,
    // oif 40
    // GDP sum 2010-2012 41
    // GHG per capita excluding LUCF 42
    // GHG per capita including LUCF 43
    $ef = explode("\t", $efile[0]);
    $eUNFCCGroupNames[] = array_splice($ef, 13, 28);
    for ($i = 1; $i < count($efile); $i++) {
        $ef = $efile[$i];
        $e = explode("\t", $ef);
        $eCountry = $e[0];
        $eRegions[$eCountry] = $e[11];
        $eIncomeGroups[$eCountry] = $e[12];
        for ($j = 13; $j < 41; $j++) {
            $eUNFCCGroups[$eCountry][] = $e[$j];
        }
        $eUNFCCGroups[$eCountry] = array_unique($eUNFCCGroups[$eCountry]);
        asort($eUNFCCGroups[$eCountry]);
        $ePopulationAverage[$eCountry] = $e[5];
        $eGdpSum[$eCountry] = ($e[6] + $e[7] + $e[8]);
        $eGhgExclLucf[$eCountry] = $e[9];  // only 2010 data
        $eGhgInclLucf[$eCountry] = $e[10];  // only 2010 data
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

/*
 * Do normalizations if necessary
 * and output in right format
 */
switch ($what) {
    case "sumofaid":
        arsort($sums);
        $i = 0;
        print "recipient\tamount\tamount per capita (amount / capita average 2010-2012)\tamount per gdp (amount / sum gdp 2010 - 2012)\n";
        foreach ($sums as $recipient => $amount) {
            $perCapita = $perGdp = "n/a";
            if (isset($ePopulationAverage[$recipient]))
                $perCapita = $amount / $ePopulationAverage[$recipient];
            if (isset($eGdpSum[$recipient]) && $eGdpSum[$recipient] != 0)
                $perGdp = $amount / $eGdpSum[$recipient];
            print $recipient . "\t" . $amount . "\t" . $perCapita . "\t" . $perGdp . "\n";
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
        print "donor\tadaptation donated\ttotal donated\tshare of adaptation\n";
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
    case "donorSpecializationPerSector":
        ksort($countries);
        print "donor\tsector\tnr of projects\ttotal amount\tamount per capita (amount / capita average 2010-2012)\tamount per gdp (amount / sum gdp 2010 - 2012)\n";
        foreach ($countries as $country => $sectors) {
            arsort($sectors);
            foreach ($sectors as $sector => $ar) {
                $perCapita = $ar['totalAmount'] / $ePopulationAverage[$country];
                $perGdp = $ar['totalAmount'] / $eGdpSum[$country];
                print $country . "\t" . $sector . "\t" . $ar['count'] . "\t=ROUND(" . $ar['totalAmount'] . ",2)\t$perCapita\t$perGdp\n";
            }
        }
        break;
    case "donorSpecializationPerPurposeName":
        ksort($countries);
        print "donor\tpurposeName\tnr of projects\ttotal amount\tamount per capita (amount / capita average 2010-2012)\tamount per gdp (amount / sum gdp 2010 - 2012)\n";
        foreach ($countries as $country => $purposeNames) {
            arsort($purposeNames);
            foreach ($purposeNames as $purposeName => $ar) {
                $perCapita = $ar['totalAmount'] / $ePopulationAverage[$country];
                $perGdp = $ar['totalAmount'] / $eGdpSum[$country];
                print $country . "\t" . $purposeName . "\t" . $ar['count'] . "\t=ROUND(" . $ar['totalAmount'] . ",2)\t$perCapita\t$perGdp\n";
            }
        }
        break;
    case "donorSpecializationPerRegion":
        ksort($countries);
        print "donor\tregion\tnr of projects\ttotal amount\tamount per capita (amount / capita average 2010-2012)\tamount per gdp (amount / sum gdp 2010 - 2012)\n";
        foreach ($countries as $country => $regions) {
            arsort($regions);
            foreach ($regions as $region => $ar) {
                $perCapita = $ar['totalAmount'] / $ePopulationAverage[$country];
                $perGdp = $ar['totalAmount'] / $eGdpSum[$country];
                print $country . "\t" . $region . "\t" . $ar['count'] . "\t=ROUND(" . $ar['totalAmount'] . ",2)\t$perCapita\t$perGdp\n";
            }
        }
        break;
    case "donorSpecializationPerCustomRegion":
        ksort($countries);
        print "donor\tcustom region\tnr of projects\ttotal amount\tamount per capita (amount / capita average 2010-2012)\tamount per gdp (amount / sum gdp 2010 - 2012)\n";
        foreach ($countries as $country => $regions) {
            arsort($regions);
            foreach ($regions as $region => $ar) {
                $perCapita = $ar['totalAmount'] / $ePopulationAverage[$country];
                $perGdp = $ar['totalAmount'] / $eGdpSum[$country];
                print $country . "\t" . $region . "\t" . $ar['count'] . "\t=ROUND(" . $ar['totalAmount'] . ",2)\t$perCapita\t$perGdp\n";
            }
        }
        break;
    case "donorSpecializationPerIncomeGroup":
        ksort($countries);
        print "donor\tIncome Group\tnr of projects\ttotal amount\tamount per capita (amount / capita average 2010-2012)\tamount per gdp (amount / sum gdp 2010 - 2012)\n";
        foreach ($countries as $country => $igs) {
            arsort($igs);
            foreach ($igs as $ig => $ar) {
                $perCapita = $ar['totalAmount'] / $ePopulationAverage[$country];
                $perGdp = $ar['totalAmount'] / $eGdpSum[$country];
                print $country . "\t" . $ig . "\t" . $ar['count'] . "\t=ROUND(" . $ar['totalAmount'] . ",2)\t$perCapita\t$perGdp\n";
            }
        }
        break;
    case "donorSpecializationPerUNFCCGroup":
        ksort($countries);

        print "donor\tUNFCC Group\tnr of projects\ttotal amount\tamount per capita (amount / capita average 2010-2012)\tamount per gdp (amount / sum gdp 2010 - 2012)\n";

        include_once('GEXF-library/Gexf.class.php');

        $gexf = new Gexf();
        $filename = "OECD RioMarkers - adaptation - 2 - donor - UNFCCGroup";
        $gexf->setTitle("RioMarkers 20140106 " . $filename);
        $gexf->setEdgeType(GEXF_EDGE_DIRECTED);
        $gexf->setCreator("tools.digitalmethods.net");

        foreach ($countries as $country => $ugs) {
            arsort($ugs);
            foreach ($ugs as $ug => $ar) {
                $perCapita = $ar['totalAmount'] / $ePopulationAverage[$country];
                $perGdp = $ar['totalAmount'] / $eGdpSum[$country];
                print $country . "\t" . $ug . "\t" . $ar['count'] . "\t=ROUND(" . $ar['totalAmount'] . ",2)\t$perCapita\t$perGdp\n";

                // also create network
                $node1 = new GexfNode($country);
                $node1->addNodeAttribute("type", 'donor', $type = "string");
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
        print "donor\trecipient\tnr of projects\ttotal amount\tamount per gdp (amount / sum gdp 2010-2012)\n";
        foreach ($countries as $donor => $recipients) {
            if (!isset($eGdpSum[$donor])) {
                print $donor . " not found in eGdpSum\n";
            } else {
                ksort($recipients);
                foreach ($recipients as $recipient => $ar) {
                    print $donor . "\t" . $recipient . "\t" . $ar['count'] . "\t" . $ar['totalAmount'] . "\t" . ($ar['totalAmount'] / $eGdpSum[$donor]) . "\n";
                }
            }
        }
        break;
    case "amountGivenPerGdpTotal":
        ksort($countries);
        print "donor\tnr of projects\ttotal amount\tamount per gdp (amount / sum gdp 2010 - 2012)\n";
        foreach ($countries as $donor => $recipients) {
            if (!isset($eGdpSum[$donor])) {
                print $donor . " not found in eGdpSum\n";
            } else {
                $total = $count = 0;
                foreach ($recipients as $ar) {
                    $count += $ar['count'];
                    $total += $ar['totalAmount'];
                }
                print $donor . "\t" . $count . "\t" . $total . "\t" . ($total / $eGdpSum[$donor]) . "\n";
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
    case "recipientSpecializationPerSector":
        ksort($countries);
        print "recipient\tsector\tnr of projects\ttotal amount\tamount per capita (amount / capita average 2010-2012)\tamount per gdp (amount / sum gdp 2010 - 2012)\n";
        foreach ($countries as $country => $sectors) {
            arsort($sectors);
            foreach ($sectors as $sector => $ar) {
                $perCapita = $perGdp = "n/a";
                if (isset($ePopulationAverage[$country]))
                    $perCapita = $ar['totalAmount'] / $ePopulationAverage[$country];
                if (isset($eGdpSum[$country]) && $eGdpSum[$country] != 0)
                    $perGdp = $ar['totalAmount'] / $eGdpSum[$country];
                print $country . "\t" . $sector . "\t" . $ar['count'] . "\t=ROUND(" . $ar['totalAmount'] . ",2)\t$perCapita\t$perGdp\n";
            }
        }
        break;
    case "recipientSpecializationPerPurposeName":
        ksort($countries);
        print "recipient\tpurposeName\tnr of projects\ttotal amount\tamount per capita (amount / capita average 2010-2012)\tamount per gdp (amount / sum gdp 2010 - 2012)\n";
        foreach ($countries as $country => $purposeNames) {
            arsort($purposeNames);
            foreach ($purposeNames as $purposeName => $ar) {
                $perCapita = $perGdp = "n/a";
                if (isset($ePopulationAverage[$country]))
                    $perCapita = $ar['totalAmount'] / $ePopulationAverage[$country];
                if (isset($eGdpSum[$country]) && $eGdpSum[$country] != 0)
                    $perGdp = $ar['totalAmount'] / $eGdpSum[$country];
                print $country . "\t" . $purposeName . "\t" . $ar['count'] . "\t=ROUND(" . $ar['totalAmount'] . ",2)\t$perCapita\t$perGdp\n";
            }
        }
        break;
    case "recipientSpecializationPerRegion":
        ksort($countries);
        print "recipient\tregion\tnr of projects\ttotal amount\tamount per capita (amount / capita average 2010-2012)\tamount per gdp (amount / sum gdp 2010 - 2012)\n";
        foreach ($countries as $country => $regions) {
            arsort($regions);
            foreach ($regions as $region => $ar) {
                $perCapita = $perGdp = "n/a";
                if (isset($ePopulationAverage[$country]))
                    $perCapita = $ar['totalAmount'] / $ePopulationAverage[$country];
                if (isset($eGdpSum[$country]) && $eGdpSum[$country] != 0)
                    $perGdp = $ar['totalAmount'] / $eGdpSum[$country];
                print $country . "\t" . $region . "\t" . $ar['count'] . "\t=ROUND(" . $ar['totalAmount'] . ",2)\t$perCapita\t$perGdp\n";
            }
        }
        break;
    case "recipientSpecializationPerCustomRegion":
        ksort($countries);
        print "recipient\tcustom region\tnr of projects\ttotal amount\tamount per capita (amount / capita average 2010-2012)\tamount per gdp (amount / sum gdp 2010 - 2012)\n";
        foreach ($countries as $country => $regions) {
            arsort($regions);
            foreach ($regions as $region => $ar) {
                $perCapita = $perGdp = "n/a";
                if (isset($ePopulationAverage[$country]))
                    $perCapita = $ar['totalAmount'] / $ePopulationAverage[$country];
                if (isset($eGdpSum[$country]) && $eGdpSum[$country] != 0)
                    $perGdp = $ar['totalAmount'] / $eGdpSum[$country];
                print $country . "\t" . $region . "\t" . $ar['count'] . "\t=ROUND(" . $ar['totalAmount'] . ",2)\t$perCapita\t$perGdp\n";
            }
        }
        break;
    case "recipientSpecializationPerIncomeGroup":
        ksort($countries);
        print "donor\tIncome Group\tnr of projects\ttotal amount\tamount per capita (amount / capita average 2010-2012)\tamount per gdp (amount / sum gdp 2010 - 2012)\n";
        foreach ($countries as $country => $igs) {
            arsort($igs);
            foreach ($igs as $ig => $ar) {
                $perCapita = $perGdp = "n/a";
                if (isset($ePopulationAverage[$country]))
                    $perCapita = $ar['totalAmount'] / $ePopulationAverage[$country];
                if (isset($eGdpSum[$country]) && $eGdpSum[$country] != 0)
                    $perGdp = $ar['totalAmount'] / $eGdpSum[$country];
                print $country . "\t" . $ig . "\t" . $ar['count'] . "\t=ROUND(" . $ar['totalAmount'] . ",2)\t$perCapita\t$perGdp\n";
            }
        }
        break;
    case "recipientSpecializationPerUNFCCGroup":
        ksort($countries);

        print "recipient\tUNFCC Group\tnr of projects\ttotal amount\tamount per capita (amount / capita average 2010-2012)\tamount per gdp (amount / sum gdp 2010 - 2012)\n";

        include_once('GEXF-library/Gexf.class.php');

        $gexf = new Gexf();
        $filename = "OECD RioMarkers - adaptation - 2 - recipient - UNFCCGroup";
        $gexf->setTitle("RioMarkers 20140106 " . $filename);
        $gexf->setEdgeType(GEXF_EDGE_DIRECTED);
        $gexf->setCreator("tools.digitalmethods.net");

        foreach ($countries as $country => $ugs) {
            arsort($ugs);
            foreach ($ugs as $ug => $ar) {
                $perCapita = $perGdp = "n/a";
                if (isset($ePopulationAverage[$country]))
                    $perCapita = $ar['totalAmount'] / $ePopulationAverage[$country];
                if (isset($eGdpSum[$country]) && $eGdpSum[$country] != 0)
                    $perGdp = $ar['totalAmount'] / $eGdpSum[$country];
                print $country . "\t" . $ug . "\t" . $ar['count'] . "\t=ROUND(" . $ar['totalAmount'] . ",2)\t$perCapita\t$perGdp\n";

                // also create network
                $node1 = new GexfNode($country);
                $node1->addNodeAttribute("type", 'recipient', $type = "string");
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
    default:
        break;
}
?>

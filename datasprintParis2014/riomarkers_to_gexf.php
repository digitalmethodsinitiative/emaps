<?php

/*
 * makes various types of GEXF files (see first switch statement)
 * look at oecd_clean.php to generate the data necessary for this script, or take them from the Google Drive > ParisSprintDataSets > Group 5 warehouse folder > data files
 * @author Erik Borra <erik@digitalmethods.net>
 */

ini_set('memory_limit', '2G');
include_once('GEXF-library/Gexf.class.php');

/*
 *  choose what to output
 */
//$whats = array("donor recipient", "donor sector", "recipient sector", "donor purposeName", "recipient purposeName");
//$whats = array("donor cluster", "recipient cluster");
$whats = array("donor purposeName", "recipient purposeName");
foreach ($whats as $what) {
    run($what);
}

function run($what) {
    $inputfile = "RioMarkers_cleaned.txt";

    /*
     * decide upon filename
     */
    switch ($what) {
        case "donor recipient":
            $filename = "OECD RioMarkers - adaptation - 2 - Donors - Recipients";
            break;
        case "donor sector":
            $filename = "OECD RioMarkers - adaptation - 2 - Donors - Sectors";
            break;
        case "recipient sector":
            $filename = "OECD RioMarkers - adaptation - 2 - Recipients - Sectors";
            break;
        case "donor cluster":
            $filename = "OECD RioMarkers - adaptation - 2 - Donors linked by recipients";
            break;
        case "recipient cluster":
            $filename = "OECD RioMarkers - adaptation - 2 - Recipients linked by donors";
            break;
        case "donor purposeName":
            $filename = "OECD RioMarkers - adaptation - 2 - Donors - Purpose Names";
            break;
        case "recipient purposeName":
            $filename = "OECD RioMarkers - adaptation - 2 - Recipients - Purpose Names";
            break;
        default:
            break;
    }

    /*
     * Load some extra information per country, if necessary
     */
    if (array_search($what, array("donor purposeName", "recipient purposeName")) !== false) {
        print "loading extra data\n";
        $edata = "Merged Countries - Sheet 1.tsv";
        $efile = file("data/" . $edata);
        $ef = explode("\t", $efile[0]);
        $eheaders = $ef;
        $eUNFCCGroupNames = array_splice($ef, 13, 28);
        for ($i = 1; $i < count($efile); $i++) {
            $ef = $efile[$i];
            $e = explode("\t", $ef);
            $eCountry = $e[0];
            for ($j = 13; $j < 41; $j++) {
                $eUNFCCGroups[$eCountry][$eheaders[$j]] = 0;
                if ($e[$j] != "")
                    $eUNFCCGroups[$eCountry][$eheaders[$j]] = 1;
            }
        }
    }

    /*
     * start new graph
     */
    $gexf = new Gexf();
    $gexf->setTitle("RioMarkers 20140106 " . $filename);
    if ($what == "donor cluster" || $what == "recipient cluster")
        $gexf->setEdgeType(GEXF_EDGE_UNDIRECTED);
    else
        $gexf->setEdgeType(GEXF_EDGE_DIRECTED);
    $gexf->setCreator("tools.digitalmethods.net");

    /*
     * Load OECD riomarkers data
     */
    $file = file("data/" . $inputfile);
    /*
     * Loop over all rows and decide which links should be made
     */
    print "starting loop\n";
    for ($i = 1; $i < count($file); $i++) {
        $e = explode("|", $file[$i]);
        if (count($e) != 31) {  // check for errors and print what does not go right
            print count($e) . "\n";
            print $file[$i] . "\n";
            continue;
        }
        $donor = $e[1];
        $recipient = $e[5];
        $climateMitigation = $e[11];
        $climateAdaptation = $e[12];
        $amount = $e[4]; // usd_commitment_defl
        $sector = $e[19];
        $purposeName = $e[7];

        if ($climateAdaptation == 2) { // only use data for which the principle objective is climate adaptation
            switch ($what) {
                case "donor recipient":
                    $node1 = new GexfNode($donor);
                    $node1->addNodeAttribute("type", 'donor', $type = "string");
                    $gexf->addNode($node1);

                    $node2 = new GexfNode($recipient);
                    $node2->addNodeAttribute("type", 'recipient', $type = "string");
                    $gexf->addNode($node2);

                    $edge_id = $gexf->addEdge($node1, $node2, $amount);
                    break;
                case "donor sector":
                    $node1 = new GexfNode($donor);
                    $node1->addNodeAttribute("type", 'donor', $type = "string");
                    $gexf->addNode($node1);

                    $node2 = new GexfNode($sector);
                    $node2->addNodeAttribute("type", 'sector', $type = "string");
                    $gexf->addNode($node2);

                    $edge_id = $gexf->addEdge($node1, $node2, $amount);
                    break;
                case "recipient sector":
                    $node1 = new GexfNode($sector);
                    $node1->addNodeAttribute("type", 'sector', $type = "string");
                    $gexf->addNode($node1);

                    $node2 = new GexfNode($recipient);
                    $node2->addNodeAttribute("type", 'recipient', $type = "string");
                    $gexf->addNode($node2);

                    $edge_id = $gexf->addEdge($node1, $node2, $amount);
                    break;
                case "donor cluster":
                    $recipients[$recipient][] = $donor;
                    break;
                case "recipient cluster":
                    $donors[$donor][] = $recipient;
                    break;
                case "donor purposeName":
                    $node1 = new GexfNode($donor);
                    $node1->addNodeAttribute("type", 'donor', $type = "string");
                    if (!isset($eUNFCCGroups[$donor])) {
                        foreach ($eUNFCCGroupNames as $groupName)
                            $node1->addNodeAttribute($groupName, -1, "integer");
                    } else {
                        foreach ($eUNFCCGroups[$donor] as $groupName => $val)
                            $node1->addNodeAttribute($groupName, $val, "integer");
                    }
                    $gexf->addNode($node1);

                    $node2 = new GexfNode($purposeName);
                    $node2->addNodeAttribute("type", 'purposeName', $type = "string");
                    $gexf->addNode($node2);

                    $edge_id = $gexf->addEdge($node1, $node2, $amount);
                    break;
                case "recipient purposeName":
                    $node1 = new GexfNode($purposeName);
                    $node1->addNodeAttribute("type", 'purposeName', $type = "string");
                    $gexf->addNode($node1);

                    $node2 = new GexfNode($recipient);
                    $node2->addNodeAttribute("type", 'recipient', $type = "string");
                    if (!isset($eUNFCCGroups[$recipient])) {
                        foreach ($eUNFCCGroupNames as $groupName)
                            $node2->addNodeAttribute($groupName, -1, "integer");
                    } else {
                        foreach ($eUNFCCGroups[$recipient] as $groupName => $val)
                            $node2->addNodeAttribute($groupName, $val, "integer");
                    }
                    $gexf->addNode($node2);

                    $edge_id = $gexf->addEdge($node1, $node2, $amount);
                    break;
                default:
                    break;
            }
        }
    }

    switch ($what) {

        case "donor cluster":
            foreach ($recipients as $recipient => $donors) {
                $donors = array_values(array_unique($donors));
                $donors2 = $donors;
                for ($i = 0; $i < count($donors); $i++) {
                    $node1 = new GexfNode($donors[$i]);
                    $node1->addNodeAttribute("type", 'donor', $type = "string");
                    $gexf->addNode($node1);
                    for ($j = $i + 1; $j < count($donors2); $j++) {
                        //if($recipient == "Burundi")
                        //    print $donors[$i]." ".$donors2[$j]."\n";
                        $node2 = new GexfNode($donors2[$j]);
                        $node2->addNodeAttribute("type", 'donor', $type = "string");
                        $gexf->addNode($node2);

                        $edge_id = $gexf->addEdge($node1, $node2, 1);
                    }
                }
            }
            break;
        case "recipient cluster":
            foreach ($donors as $donor => $recipients) {
                $recipients = array_values(array_unique($recipients));
                $recipients2 = $recipients;
                for ($i = 0; $i < count($recipients); $i++) {
                    $node1 = new GexfNode($recipients[$i]);
                    $node1->addNodeAttribute("type", 'recipient', $type = "string");
                    $gexf->addNode($node1);
                    for ($j = $i + 1; $j < count($recipients2); $j++) {
                        //if($recipient == "Burundi")
                        //    print $donors[$i]." ".$donors2[$j]."\n";
                        $node2 = new GexfNode($recipients2[$j]);
                        $node2->addNodeAttribute("type", 'donor', $type = "string");
                        $gexf->addNode($node2);

                        $edge_id = $gexf->addEdge($node1, $node2, 1);
                    }
                }
            }
            break;
        default:
            break;
    }

// render the file
    $gexf->render();

// write out the file
    file_put_contents("results/" . $filename . '.gexf', $gexf->gexfFile);
}

?>

<?php
include_once('config.php');

if (isset($_GET['issues'])) {
    $issues = preg_split("/\n/", $_GET['issues']);
    foreach ($issues as $issue) {
        if (!preg_match("/\"/", $issue)) {
            // simple, one line keyword
            $q = '(text:"' . $issue . '"';
        } else {
            $q = '(';
            $q .= preg_replace("/\"(.*?)\"/", "text:\"$1\"", $issue);
        }
        $q .= ')';
        if (!empty($hosts)) {
            foreach ($hosts as $host) {
                $issuesPerSite[$host][$issue] = 0;
                $sitesPerIssue[$issue][$host] = 0;
                $q .= ' AND url:*' . $host . '*';
                // more precise but results in API syntax error?
                //$q = 'url:*' . urlencode($hostProtocol[$host] . '://' . $host . '*');
                $queries[] = $q;
            }
        } else {
            $queries[] = $q;
        }
    }

    $totalrows = 0;
    foreach ($queries as $q) {

        $query_url = 'http://jiminy.medialab.sciences-po.fr/solr/hyphe-emaps2/select?';
        $query_url .= 'q=' . urlencode($q);
        $query_url .= '&wt=json&rows=1&fl=url';

        $curl = curl_init();
        curl_setopt_array($curl, array(
    	CURLOPT_URL => $query_url,
	CURLOPT_USERAGENT => 'hypheToLippmann',
	CURLOPT_RETURNTRANSFER => true,
	CURLOPT_USERPWD => $GLOBALS["user"] . ':' . $GLOBALS["password"],
	CURLOPT_HTTPAUTH => CURLAUTH_BASIC,
        ));
        $r = json_decode(curl_exec($curl), TRUE);
        curl_close($curl);

        if (array_key_exists('response', $r)) {
    	    $numFound = $r['response']['numFound'];
	    $totalrows += $numFound;
        }
    }

    if (isset($_GET['urls'])) {
        $urls = preg_split("/\n/", $_GET['urls']);
        if (count($urls) > 1 || strlen($_GET['urls'])) {
           $totalrows /= 900;
           $totalrows *= count($urls);
           if ($totalrows < 1) {
               $totalrows = 1;
           }
        }
    }

    $speed = 100;  // number of rows per second that can be retrieved from server
    $seconds = round($totalrows / $speed);
    if ($seconds < 4) { $seconds = 4; }
    echo "$seconds\n";
    exit();

}

echo "0\n";
exit();

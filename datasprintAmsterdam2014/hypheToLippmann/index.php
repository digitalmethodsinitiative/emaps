<?php
include_once('config.php');
include_once('interface.php');

$debug = FALSE;
if (isset($_GET['debug']) && $_GET['debug'] == 'true')
    $debug = TRUE;
if ($debug)
    error_reporting(E_ALL);

$GLOBALS['cloudid'] = 0;            // increments on every new cloud printed

$urls = $issues = $hostProtocol = $hosts = $sitesPerIssue = $issuesPerSite = $queries = $doneUrl = array();

$accept = FALSE;

if (array_key_exists('performaccept', $_GET) && $_GET['performaccept'] == 'accept') {
    $accept = TRUE;
}

if ($accept && isset($_GET['urls'])) {
    $urls = preg_split("/\n/", $_GET['urls']);
    foreach ($urls as $url) {
        if (array_key_exists($url, $doneUrl)) {
            continue;
        }
        $doneUrl[$url] = 1;

        if (!getHost($url))
            continue;
        list($host, $hostProtocol) = getHost($url);
        $hosts[] = $host;
        $hostProtocol[$host] = $hostProtocol;
    }
}
if ($accept && isset($_GET['issues'])) {
    $issues = preg_split("/\n/", $_GET['issues']);

    foreach ($issues as $issue) {
        // example query
        //http://jiminy.medialab.sciences-po.fr/solr/hyphe-emaps2/select?q=text%3A%22solar+scientists%22&wt=json&indent=true
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
                if ($debug)
                    echo "query: $q<br>";
                $queries[] = $q;
            }
        } else {
            if ($debug)
                echo "query: $q<br>";
            $queries[] = $q;
        }
    }

    $rows = 1000;
    foreach ($queries as $q) {

        $query_url = 'http://jiminy.medialab.sciences-po.fr/solr/hyphe-emaps2/select?';
        $query_url .= 'q=' . urlencode($q);
        $query_url .= '&wt=json&indent=true&rows=' . $rows . '&fl=url';

        $start = $numFound = 0;
        while ($start <= $numFound) {
            if ($start > 0)
                $query_url_offset = $query_url . "&start=$start";
            else
                $query_url_offset = $query_url;

            if ($debug) {
                print "doing <a href='$query_url_offset'>$query_url_offset</a><bR>";
                flush();
            }

            $curl = curl_init();
            curl_setopt_array($curl, array(
                CURLOPT_URL => $query_url_offset,
                CURLOPT_USERAGENT => 'hypheToLippmann',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_USERPWD => $GLOBALS["user"] . ':' . $GLOBALS["password"],
                CURLOPT_HTTPAUTH => CURLAUTH_BASIC,
            ));
            $r = json_decode(curl_exec($curl), TRUE);
            curl_close($curl);

            if (array_key_exists('response', $r)) {
                $numFound = $r['response']['numFound'];
                if ($debug) {
                    echo "found $numFound<br>";
                }
                foreach ($r['response']['docs'] as $doc) {
                    $url = $doc['url'];
                    list($host, $hostProtocol) = getHost($url);
                    if (!isset($issuesPerSite[$host][$issue]))
                        $issuesPerSite[$host][$issue] = 0;
                    if (!isset($sitesPerIssue[$issue][$host]))
                        $sitesPerIssue[$issue][$host] = 0;
                    $issuesPerSite[$host][$issue]++;
                    $sitesPerIssue[$issue][$host]++;
                }
            } elseif ($debug) {
                echo "found nothing<br>";
            }
            $start += $rows;
        }
    }

    // Generate tag clouds

    start_html(TRUE);
    include_javascript();
    // copy input interface
    include_input_interface();
    include_interface();

    include_start_clouds();

    // Issue clouds
    include_header_issue_clouds();

    foreach ($issuesPerSite as $site => $null) {
        echo "<em>Issue cloud for site $site</em><br>";
        $cloud = '';
        foreach ($issuesPerSite[$site] as $issue => $found) {
            $nice = nicify($issue);
            $cloud .= "$nice:$found\r\n";
        }
        javascript_produce_cloud($cloud);
    }

    echo "<em>Cummulative issue cloud for all sites</em><br>";
    $cloud = '';
    $cloudIssues = array();
    foreach ($issuesPerSite as $site => $null) {
        foreach ($issuesPerSite[$site] as $issue => $found) {
            $nice = nicify($issue);
            if (!array_key_exists($nice, $cloudIssues)) {
                $cloudIssues[$nice] = $found;
            } else {
                $cloudIssues[$nice] += $found;
            }
        }
    }
    foreach ($cloudIssues as $nice => $found) {
        $cloud .= "$nice:$found\r\n";
    }
    javascript_produce_cloud($cloud);

    echo "<br>";

    // Source clouds
    include_header_source_clouds();

    foreach ($sitesPerIssue as $issue => $null) {
        echo "<em>Source cloud for issue/query: $issue</em><br>";
        $cloud = '';
        foreach ($sitesPerIssue[$issue] as $site => $found) {
            $cloud .= "$site:$found\r\n";
        }
        javascript_produce_cloud($cloud);
    }

    echo "<em>Cummulative source cloud for all issues/queries</em><br>";
    $cloud = '';
    $cloudSources = array();
    foreach ($sitesPerIssue as $issue => $null) {
        foreach ($sitesPerIssue[$issue] as $site => $found) {
            if (!array_key_exists($site, $cloudSources)) {
                $cloudSources[$site] = $found;
            } else {
                $cloudSources[$site] += $found;
            }
        }
    }
    foreach ($cloudSources as $site => $found) {
        $cloud .= "$site:$found\r\n";
    }
    javascript_produce_cloud($cloud);

    echo "<br>";

    include_end_clouds();

    // store the number of clouds in the DOM
    javascript_store_cloudnum();

    end_html();

    exit();
} else {

    start_html();
    include_javascript();
    if (!$accept && isset($_GET['issues'])) {
        include_input_interface(TRUE);
    } else {
        include_input_interface();
    }
    end_html();

}

/* support functions */

function getHost($url) {
    $parse = parse_url($url);
    if (!array_key_exists('host', $parse)) {
        echo 'Attention! malformed url: "' . $url . '"<br>';
        return false;
    }
    $host = preg_replace("/_/", "", $parse['host']);
    return array($host, $parse['scheme']);
}

function nicify($issue) {
    // this function can be used to manipulate the label of the issue before being printed

    $issue = stripslashes($issue);

    /*
      Example: extract only the first keyword

      $issue = preg_replace("/^.*?\"/", "", $issue);
      $issue = preg_replace("/\".*$/", "", $issue);
     */ 

   return $issue;
}


<?php
include_once('config.php');

$debug = FALSE;
if (isset($_GET['debug']) && $_GET['debug'] == 'true')
    $debug = TRUE;
if ($debug)
    error_reporting(E_ALL);

$GLOBALS['cloudid'] = 0;            // increments on every new cloud printed

$urls = $issues = $hostProtocol = $hosts = $sitesPerIssue = $issuesPerSite = $queries = $doneUrl = array();

if (isset($_GET['urls'])) {
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
if (isset($_GET['issues'])) {
    $issues = preg_split("/\n/", $_GET['issues']);

    foreach ($issues as $issue) {
        // @todo: implement conditionals for the query
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


    foreach ($queries as $q) {

        $query_url = 'http://jiminy.medialab.sciences-po.fr/solr/hyphe-emaps2/select?';
        $query_url .= 'q=' . urlencode($q);
        $query_url .= '&wt=json&indent=true';

        if ($debug)
            print "doing <a href='$query_url'>$q</a><bR>";

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
            $found = $r['response']['numFound'];
            if ($debug) {
                echo "found $found<br>";
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
    }

    // Generate tag clouds

    start_html();
    include_javascript();

    // Issue clouds
    include_header_issue_clouds();

    foreach ($issuesPerSite as $site => $null) {
        echo "<br>Issue cloud for site <b>$site</b><br><br>";
        $cloud = '';
        foreach ($issuesPerSite[$site] as $issue => $found) {
            $nice = nicify($issue);
            $cloud .= "$nice:$found\r\n";
        }
        javascript_produce_cloud($cloud);
    }

    echo "<br>Cummulative issue cloud for <b>all</b> sites<br><br>";
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
        echo "<br>Source cloud for issue/query: <b>$issue</b><br><br>";
        $cloud = '';
        foreach ($sitesPerIssue[$issue] as $site => $found) {
            $cloud .= "$site:$found\r\n";
        }
        javascript_produce_cloud($cloud);
    }

    echo "<br>Cummulative source cloud for <b>all</b> issues<br><br>";
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

    // store the number of clouds in the DOM
    javascript_store_cloudnum();

    include_interface();
    end_html();

    exit();
} else {
    ?>
    <html>
        <head><title>Webcorpus Lippmannian</title>
        </head>
        <body>
            <form method="GET">
                <br>
                Enter URLs:<br><textarea name="urls" cols="80" rows="10"></textarea><br><br>
                Enter queries:<br><textarea type="textarea" name="issues" cols="80" rows="10"></textarea><br><br>
                <input type="submit" value="Submit">
            </form>        
        </body>
    </html>

    <?php
}

/* support functions */

function getHost($url) {
    $parse = parse_url($url);
    if (!array_key_exists('host', $parse)) {
        echo 'malformed url: ' . $url . '<br>';
        return false;
    }
    $host = preg_replace("/_/", "", $parse['host']);
    return array($host, $parse['scheme']);
}

function javascript_store_cloudnum() {
    ?>
    <script type="text/javascript">
        $( document ).ready(function() {
            document.clouds = <?php echo $GLOBALS["cloudid"]; ?>;
        });
    </script>
    <?php
}

function javascript_produce_cloud($cloud) {
    $GLOBALS['cloudid']++;
    $id = $GLOBALS['cloudid'];

    echo "<div id=\"input$id\" style=\"display: none;\">$cloud</div>";
    echo "<div id=\"output$id\" class=\"output\"></div>";
    ?>
    <!-- produce the cloud -->
    <script type="text/javascript">
        $( document ).ready(function() {
            makeCloudNumber(<?php echo $id; ?>);
        });
    </script>
    <?php
}

function nicify($issue) {
    $issue = preg_replace("/^.*?\"/", "", $issue);
    $issue = preg_replace("/\".*$/", "", $issue);
    return $issue;
}

function getval($par) {
    if (array_key_exists($par, $_GET)) {
        return $_GET[$par];
    } else {
        return FALSE;
    }
}

function initval($par) {
    return getval($par) ? getval($par) : 0;
}

function start_html() {
    ?>
    <html>
        <head>
            <title>lippmanWeb results</title>
        </head>
        <body>
            <?php
        }

        function end_html() {
            ?>
        </body>
    </html>
    <?php
}

function include_javascript() {
    ?>
    <script src="//ajax.googleapis.com/ajax/libs/jquery/1.11.0/jquery.min.js"></script>

    <script type="text/javascript" language="javascript" src='script.js'></script>

    <link rel="stylesheet" type="text/css" href="style.css">

    <?php
}

function include_header_source_clouds() {
    // four identifiers for the unique tag clouds, css class output
    ?>
    <em>Source cloud</em>

    <p>
        Show the partisanship or commitment of sources to issues. The cloud displays sources, each resized according to the number of mentions of a particular issue. 
    </p>
    <?php
}

function include_header_issue_clouds() {
    ?>        
    <em>Issue cloud</em>

    <p>
        Show the issue commitment or partisanship of a single source or multiple sources. The cloud displays issues, each resized according to the number of mentions by one or more sources. 
    </p>
    <?php
}

function include_interface() {
    ?>



    <div id="interface">

        <fieldset id="if_input">

            <legend class="heading">Input</legend>

            <form onsubmit="return false;">

                <div id="text" class="input_areas">
                    <textarea id="textinput">auto-generated</textarea>
                </div>
                <div id="layout" class="input_areas">
                    Layout:
                    <input type="radio" name="layout" value="inline" checked="true" onchange="interfaceChange()" /> tagcloud
                    <input type="radio" name="layout" value="block" onchange="interfaceChange()" /> taglist
                </div>
                <div id="order" class="input_areas">
                    Order:
                    <input type="radio" name="order" value="alpha" checked="true" onchange="interfaceChange()" /> alphabetically
                    <input type="radio" name="order" value="rank" onchange="interfaceChange()" /> by size
                </div>
                <div id="case" class="input_areas">
                    Text case:
                    <input type="radio" name="case" value="asis" checked="true" onchange="interfaceChange()" /> as is
                    <input type="radio" name="case" value="upper" onchange="interfaceChange()" /> uppercase
                    <input type="radio" name="case" value="lower" onchange="interfaceChange()" /> lowercase
                </div>
                <div id="size" class="input_areas">
                    Width: <input class="inputfield" type="text" id="size_width" value="950" /> px <input type="button" value="apply" onclick="interfaceChange()" />
                </div>

            </form>

        </fieldset>

        <fieldset id="if_howto">
            <legend class="heading">Tag Cloud HTML Generator, an Introduction</legend>
            <div class="input_areas">Input tags and values in wordle format to produce a HTML tag cloud or tag list.<br/><br/>Creates a tagcloud from input.<br/>
                <br/>
                Insert tags into the input field in wordle format (tag:value), one tag per line. Now also works with the Web of Science format used in analyze.txt (tag{tab}value.<br/>
                <br/>
                To use in a graphics program, print as PDF and open.<br/></div>
        </fieldset>

    </div>

    <?php
}
?>
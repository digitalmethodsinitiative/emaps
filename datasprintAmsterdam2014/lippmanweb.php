<?php

$debug = FALSE;

$GLOBALS['cloudid'] = 0;            // increments on every new cloud printed
$GLOBALS['user'] = 'medialab';
$GLOBALS['password'] = 'patipata';

if (isset($_GET['urls'])) {
    $urls = preg_split("/\n/", $_GET['urls']);
    $issues = preg_split("/\n/", $_GET['issues']);
    
    $doneUrl = array();
    $hostProtocol = array();
    $hosts = array();
    foreach ($urls as $url) {
        if (array_key_exists($url, $doneUrl)) {
            continue;
        }
        $doneUrl[$url] = 1;
        $parse = parse_url($url);
        if (!array_key_exists('host', $parse)) {
            echo 'malformed url: ' . $url . '<br>';
            continue;
        }
        $host = preg_replace("/_/", "", $parse['host']);
        $hosts[] = $host;
        $hostProtocol[$host] = $parse['scheme'];
    }
    
    $sitesPerIssue = array();
    $issuesPerSite = array();
    
    // issues for source (individual hosts)

    /* query each host for all the issues */
    foreach ($hosts as $host) {
        $issuesPerSite[$host] = array();
        foreach ($issues as $issue) {
            if (!array_key_exists($issue, $sitesPerIssue)) {
                $sitesPerIssue[$issue] = array();
            }
            // @todo: implement conditionals for the query
            
            if (!preg_match("/\"/", $issue)) {
                // simple, one line keyword
                $q = '(text:"' . $issue . '"';
            } else {
                $q = '(';
                $q .= preg_replace("/\"(.*?)\"/", "text:\"$1\"", $issue);
            }
            $q .= ') AND url:*' . $host . '*';
         
            // more precise but results in API syntax error?
            //$q = 'url:*' . urlencode($hostProtocol[$host] . '://' . $host . '*');
            
            if ($debug) {
                echo "query: $q<br>";
            }
            
            // example query
            http://jiminy.medialab.sciences-po.fr/solr/hyphe-emaps2/select?q=text%3A%22solar+scientists%22&wt=json&indent=true
            
            $query_url = 'http://jiminy.medialab.sciences-po.fr/solr/hyphe-emaps2/select?';
            $query_url .= 'q=' . urlencode($q);
            $query_url .= '&wt=json&indent=true';
            
            $curl = curl_init();
            curl_setopt_array($curl, array(
                CURLOPT_URL => $query_url,
                CURLOPT_USERAGENT => 'lippmannWeb',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_USERPWD => $GLOBALS["user"].':'.$GLOBALS["password"],
                CURLOPT_HTTPAUTH => CURLAUTH_BASIC,
            ));
            $r = json_decode(curl_exec($curl), TRUE);
            curl_close($curl);
            
            if (array_key_exists('response', $r)) {                       
                $found = $r['response']['numFound'];
                if ($debug) {
                    echo "found $found<br>";
                }
            } elseif ($debug) {
                echo "found nothing<br>";
            }
            
            /* add to issues per site (host) */
            
            if ($found) {
                $issuesPerSite[$host][$issue] = $found;
            } else {
                // found nothing
                $issuesPerSite[$host][$issue] = 0;
            }            
            
            /* add to sites per issue (query) */
            if ($found) {
                $sitesPerIssue[$issue][$host] = $found;
            } else {
                // found nothing
                $sitesPerIssue[$issue][$host] = 0;
            }                        
            
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
		
	<script type="text/javascript" language="javascript">
	
	var _styles = ["font-size:10px;color:#777777;",
			       "font-size:12px;color:#777777;",
				   "font-size:14px;color:#666666;",
				   "font-size:16px;color:#666666;",
				   "font-size:18px;color:#555555;",
				   "font-size:20px;color:#555555;",
				   "font-size:22px;color:#444444;",
				   "font-size:24px;color:#444444;",
				   "font-size:26px;color:#333333;",
				   "font-size:28px;color:#333333;",
				   "font-size:30px;color:#222222;",
				   "font-size:32px;color:#222222;",
				   "font-size:34px;color:#000000;",
				   "font-size:36px;color:#000000;"]

	var _biggest = 0;
	var _smallest = 100000000000000;
        
        function makeCloudNumber(num) {
		_layout = $("#layout input:checked").val();
		_order = $("#order input:checked").val();
		_width = $("#size_width").val();
		_case = $("#case input:checked").val();
		
		createCloud(_order,_layout,_width,_case, num);
        }
        	
	function interfaceChange() {
		
		_layout = $("#layout input:checked").val();
		_order = $("#order input:checked").val();
		_width = $("#size_width").val();
		_case = $("#case input:checked").val();
		
                /* Old */
		//createCloud(_order,_layout,_width,_case);
                
                for (i = 1; i <= document.clouds; i++) {
                    createCloud(_order,_layout,_width,_case,i);
                }
	}
	
	
	function createCloud(_order,_layout,_width,_case,num) {
		
                // old:
		//var _textinput = $("#textinput").val();
                var _textinput = $("#input" + num).html();
		
		var _taglist = {};
		var _textlines = _textinput.split(/[\n\r]/);

		
		if(_textlines[0].match(/:\d/)) {
			for(var i = 0; i < _textlines.length; i++) {
				_textlines[i] = $.trim(_textlines[i]);
				var _tmp = _textlines[i].split(/[,\t:]/);
				_taglist[_tmp[0]] = parseInt(_tmp[1]);
			}
		} else if(_textlines[0].match(/\t\d/)) {
			for(var i = 0; i < _textlines.length; i++) {
				_textlines[i] = $.trim(_textlines[i]);
				var _tmp = _textlines[i].split(/[,\t:]/);
				_taglist[_tmp[0]] = parseInt(_tmp[1]);
			}
		} else {
			for(var i = 0; i < _textlines.length; i++) {
				_textlines[i] = $.trim(_textlines[i]);
				if(typeof(_taglist[_textlines[i]]) == "undefined") {
					_taglist[_textlines[i]] = 1;
				} else {
					_taglist[_textlines[i]]++;
				}
			}
		}
		
		
		
		
		for(var _key in _taglist) {

			if(_taglist[_key] < _smallest) {
				_smallest = _taglist[_key];
			}
			
			if(_taglist[_key] > _biggest) {
				_biggest = _taglist[_key];
			}
		}
	
	
		var _sorted = [];
		for(var _key in _taglist) {
			var _tag = _key;
			if(_case == "upper") { var _tag = _key.toUpperCase(); }
			if(_case == "lower") { var _tag = _key.toLowerCase(); }
			_sorted.push([_tag, _taglist[_key]])
		}
	
		if(_order == 'alpha') {
			_sorted.sort(function(a, b) { 
				if(a[0][0] < b[0][0]) return -1;
				if(a[0][0] > b[0][0]) return 1;
				return 0;
			});
		}
		
		if(_order == 'rank') {
			_sorted.sort(function(a, b) {return parseInt(a[1]) - parseInt(b[1])})
			_sorted.reverse();
		}
		
		//console.log(_sorted);
	
		
		var _html = "";
		
		for(var i = 0; i < _sorted.length; i++) {
			
			if (_sorted[i][1] >= _smallest) {
			
				var _tmpsize = _sorted[i][1] - _smallest;
		
				if(_tmpsize != 0) {
					_tmpsize = Math.round(_tmpsize / (_biggest - _smallest) * (_styles.length - 1));
				}

				//console.log(_tmpsize);
				
				_sorted[i][0] = _sorted[i][0].replace(/\s/,"&nbsp;");
				
				_html += '<span class="tag" style="'+ _styles[_tmpsize] +'">' + _sorted[i][0] + '&nbsp;(' + _sorted[i][1] + ') </span>';
			}
		}


                // old
		//$("#output").css("width",_width  + "px");
		//$("#output").html(_html);
                $("#output" + num).css("width",_width  + "px");
		$("#output" + num).html(_html);		
	
		if(_layout == "inline") {
			$(".tag").css("display",_layout);
			$(".tag").css("line-height","normal");
			if(_order == 'alpha') {
				$(".tag").css("line-height","35px");
			} else {
				$(".tag").css("line-height","150%");
			}
			
		} else {
			$(".tag").css("display",_layout);
			$(".tag").css("line-height","normal");
			$(".tag").css("line-height","130%");
			$(".tag").css("padding-top","8px");
		}
	}
	
	</script>
	
	<style>

		body,html {
			font-family:Arial, Helvetica, sans-serif;
			font-size:12px;
			margin:0px;
		}
		
		#title {
			font:normal 1.4em/2em Georgia, "Times New Roman", Times, serif;
			margin:10px;
		}
		
		#interface {
			border-bottom: 1px solid #C0C0C0;
			float:left;
			margin:0px 0px 30px 10px;
		}
		
		.heading {
			font-family:Georgia, "Times New Roman", Times, serif;
			font-size: 14px;
		}
		
		#if_input {
			float:left;
			display: inline;
			margin:auto;
			padding:5px;
			margin:10px 10px 20px 0px;
			background-color:#F4F1E9;
			border:1px solid #C0C0C0;
		}
		
		#if_howto {
			float:left;
			display: inline;
			padding:5px;
			margin:10px 10px 20px 10px;
			border:1px solid #C0C0C0;
			width:350px;
		}
		
		#textinput {
			width:550px;
			height:100px;
		}
		
		.output {
			margin:10px;
			text-align:justify;
			line-height: 30px;
		}
		
		.tag {

		}
		
		.input_areas {
			margin:8px;
			font-family:Georgia, "Times New Roman", Times, serif;
		}
		
		
		.inputfield {
			width:30px;
		}
		
	</style>    
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
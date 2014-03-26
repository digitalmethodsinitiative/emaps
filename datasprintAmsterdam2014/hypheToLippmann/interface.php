<?php

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

function start_html($results = FALSE) {
    ?>
    <html>
        <head>
            <title>Hyphe to Lippmann <?php if ($results) { echo 'results'; }?></title>
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

function include_input_interface() {
    ?>
   <div id="interface">

        <fieldset id="if_input">

            <legend class="heading">Input</legend>

            <form method="GET">
                <div id="layout" class="input_areas">
                Enter URLs, or leave empty to query the entire database:<br><textarea name="urls" cols="80" rows="7"></textarea><br>
                Enter queries:<br><textarea type="textarea" name="issues" cols="80" rows="7"></textarea><br><br>
                <input type="submit" value="Submit">
		</div>
            </form>

        </fieldset>

        <fieldset id="if_howto">
            <legend class="heading">Query Hyphe corpus</legend>
            <div class="input_areas">Use the form on the left to formulate your query, then press submit.
                <br/><br/>
                You can leave the URL list empty to search through the entire corpus.
                <br/><br/>
                You can make conditional queries by using curled brackets and the logical operators AND OR.
                <br/>Some examples:<br/><br/>
                "polar bear" AND "climate"<br/>
                "risk" OR "climate change"
        </fieldset>

    </div>

    <?php
}

function include_start_clouds() {
    ?>
    <div id="clouds">
    <br/>
    <?php
}

function include_end_clouds() {
    ?>
    </div>
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
    <em>Source clouds</em>

    <p>
        Shows the partisanship or commitment of sources to issues. The cloud displays sources, each resized according to the number of mentions of a particular issue. 
    </p>
    <?php
}

function include_header_issue_clouds() {
    ?>        
    <em>Issue clouds</em>

    <p>
        Shows the issue commitment or partisanship of a single source or multiple sources. The cloud displays issues, each resized according to the number of mentions by one or more sources. 
    </p>
    <?php
}

function include_interface() {
    ?>
    <div id="interface">

        <fieldset id="if_input">

            <legend class="heading">Input</legend>

            <form onsubmit="return false;">

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
            <legend class="heading">Tag Cloud HTML</legend>
            <div class="input_areas">Use the form on the left to adjust visual aspects of the tag clouds, then press apply.
		<br/><br/>
                To use in a graphics program, print as PDF and open.<br/></div>
        </fieldset>

    </div>

    <?php
}
?>

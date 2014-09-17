<?php

$file = file("matrix_viz/data/substance_of_adaptation_unique_recipients.csv");

$key = 'ZrWsGszV34FrZ3z7F8_Fw_.H8_HkGdM6zqR4MzL3g6S7BifTjU4cEQOWb48xvA--';
$apiendpoint = 'http://wherein.yahooapis.com/v1/document';
$inputType = 'text/plain';
$outputType = 'xml';

$cf = count($file);
$file_mapped = "";
for ($i = 1; $i < $cf; $i++) {
    $e = explode(";", $file[$i]);
    $source = trim($e[0]);
    $country = trim($e[1]);
    $country_mapped = "";
    if (!empty($country)) {
        $post = 'appid=' . $key . '&documentType=' . $inputType . '&outputType=' . $outputType . '&documentContent=' . $country;
        $ch = curl_init($apiendpoint);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $results = curl_exec($ch);

        $places = simplexml_load_string($results, 'SimpleXMLElement', LIBXML_NOCDATA);

        if ($places->document->placeDetails) {
            if (count($places->document->placeDetails) > 1)
                print("more places detected for [$country]" . var_export($places->document->placeDetails, 1) . "\n");
            foreach ($places->document->placeDetails as $p) {
                $country_mapped = $p->place->name;
                //$p->place->type;
                //$p->place->woeId;
                //$p->place->centroid->latitude;
                //$p->place->centroid->longitude;
            }
        }
    }
    $map = trim($file[$i]) . ";" . $country_mapped . "\n";
    //print $map;
    $file_mapped .= $map;
    //sleep(rand(1, 3));
}
file_put_contents("matrix_viz/data/substance_of_adaptation_mapping_of_countries.csv", $file_mapped);
?>

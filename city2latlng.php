//Runs in crontab nighty to convert cities to longitide, latitude, city name format;

<?php

// 1. Force the PHP engine to drop all execution clock boundaries
set_time_limit(0);
ini_set('max_execution_time', 0);


$inputFile = 'all_cities.txt';

$outputFile = 'mycitylatlng.txt';

// Put this near the top of your script (before the foreach loop)
$file = fopen("notfound.txt", "w");
fclose($file);

if (!file_exists($inputFile)) {
    die("Error: input file not found.");
}               

$locations = file($inputFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
$out = fopen($outputFile, 'w');

$options = array(
    'http' => array(
        'method' => "GET",
        'header' => "User-Agent: CleanGeoFetcher_2026 (contact@yourdomain.com)\r\n",
        'ignore_errors' => true 
    )
);
$context = stream_context_create($options);

foreach ($locations as $rawLocation) {
    $rawLocation = trim($rawLocation); // This is your "Input City Name"
    
    $queryParams = array(
        'q' => $rawLocation,
        'format' => 'json',
        'limit' => 1,
        'addressdetails' => 1,
        'accept-language' => 'en' 
    );
//Open Source data
//Limits on queries per second. Check out https://nominatim.openstreetmap.org for details

    $url = "https://nominatim.openstreetmap.org/search?" . http_build_query($queryParams);
    $response = @file_get_contents($url, false, $context);
    
    if ($response) {
        $data = json_decode($response, true);
        
        if (!empty($data) && isset($data[0])) {
            $result = $data[0];
            
            // OPTION A: Output exactly what was in the text file
            $finalName = $rawLocation;

            /* 
               OPTION B: If you want "Input Name, Country" 
               uncomment the lines below:
               
               $country = isset($result['address']['country']) ? $result['address']['country'] : '';
               $finalName = $rawLocation . ", " . $country;
            */

            $line = "lat: " . $result['lat'] . ", lng: " . $result['lon'] . ", name: \"" . $finalName . "\"\n";
            
            fwrite($out, $line);
            echo "Processed: $rawLocation -> [ " . $result['lat'] . ", " . $result['lon'] . " ]\n";
        } else {
            echo "Location not found in API response: $rawLocation\n";
            $notfound ="Location not found in API response: $rawLocation\n";
            file_put_contents("notfound.txt", $notfound, FILE_APPEND | LOCK_EX);
        }
    } else {
         echo "Failed to connect to API for: $rawLocation\n";
    }
    // MANDATORY 2026: Respect Nominatim Usage Policy (1 request per second)
    sleep(1); 
}

fclose($out);
echo "Done! Results saved to $outputFile.";
?>
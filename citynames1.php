<?php
header('Content-Type: application/javascript; charset=utf-8');

echo "var cities = [ ";
$allCities = [];

// --- PART A: FILE-BASED DATA ---
$dataFile = 'mycitylatlng.txt';
if (file_exists($dataFile)) {
    $lines = file($dataFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if (preg_match('/^(.*name:\s*)(.*)$/', $line, $matches)) {
            $prefix = $matches[1];
            $cityName = trim($matches[2], " ,'\""); 
            $allCities[] = '{ ' . $prefix . '"' . $cityName . '" }';
        } else {
            $allCities[] = '{ ' . $line . ' }';
        }
    }
}

// --- PART B: ADD MANUAL ADDITIONS IF YOU NEED THEM--- 
$allCities[] = '{ lat: 33.86, lng: 36.03, name: "Aali en Nahri, Lebanon" }';
$allCities[] = '{ lat: 45.807, lng: 15.166, name: "Novo Mesto, Slovenia" }';
$allCities[] = '{ lat: 31.937, lng: 35.039, name: "Modi\'in Illit, Israel" }';

echo implode(",\n", $allCities);
echo " ];\n\n";

echo "var createCitiesPlugin = function(citiesData) {
  var cityIcon = new Image();
  cityIcon.crossOrigin = 'Anonymous'; 
  cityIcon.src = 'https://birdbreath.com';
  
  var iconLoaded = false;
  cityIcon.onload = function() { iconLoaded = true; };
  var selectedCity = null;
  var hasRunAudit = false;

  return function(planet) {
    planet.onInit(function() {
      var totalLoaded = citiesData.length;
      var totalFound = 0;
      var totalNotFound = 0;

      citiesData.forEach(function(city) {
        var lat = parseFloat(city.lat);
        var lng = parseFloat(city.lng);
        if (!isNaN(lat) && !isNaN(lng) && lat >= -90 && lat <= 90 && lng >= -180 && lng <= 180) {
          totalFound++;
        } else {
          totalNotFound++;
        }
      });

      console.log('G7a-Log: Total loaded ' + totalLoaded + '.');
      console.log('G7a-Log: Total found ' + totalFound + '.');
      console.log('G7a-Log: Total not found ' + totalNotFound + '.');
      
      // Store these for the Draw Audit
      planet.totalFound = totalFound;
    });

    planet.onDraw(function() {
      var rotate = planet.projection.rotate();
      var center = [-rotate[0], -rotate[1]];
      var actuallyDrawn = 0;
      var behindGlobe = 0;

      planet.withSavedContext(function(context) {
        citiesData.forEach(function(city) {
          var coords = [city.lng, city.lat];
          var pos = planet.projection(coords);
          var isVisible = d3.geo.distance(coords, center) < Math.PI / 2;
          
          if (isVisible) {
            if (pos) {
              actuallyDrawn++;
              // Draw marker
              if (iconLoaded && cityIcon.complete) {
                context.drawImage(cityIcon, pos[0] - 5, pos[1] - 5, 10, 10);
              } else {
                context.beginPath();
                context.arc(pos[0], pos[1], 3, 0, 2 * Math.PI);
                context.fillStyle = 'red';
                context.fill();
              }
            }
          } else {
            behindGlobe++;
          }
        });

        // Clicked label logic
        if (selectedCity) {
          var sCoords = [selectedCity.lng, selectedCity.lat];
          var sPos = planet.projection(sCoords);
          if (d3.geo.distance(sCoords, center) < Math.PI / 2 && sPos) {
            context.font = '13px Arial';
            context.fillStyle = '#FFFFFF';
            context.fillText(selectedCity.name, sPos[0] + 12, sPos[1] + 5);
          }
        }
      });

      // NEW AUDIT: Runs once after the globe starts rotating
      if (!hasRunAudit && actuallyDrawn > 0) {
        var notVisibleFront = planet.totalFound - actuallyDrawn - behindGlobe;
        console.log('G7a-Log: Cities in front but not displaying: ' + notVisibleFront + '.');
        hasRunAudit = true;
      }
    });
  };
};";
?>
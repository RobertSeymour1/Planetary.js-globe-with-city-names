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
        // Standardize keys and ensure they aren't wrapped in quotes for JS logic
        if (preg_match('/^(.*name:\s*)(.*)$/', $line, $matches)) {
            $prefix = $matches[1];
            $cityName = trim($matches[2], " ,'\""); 
            $allCities[] = '{ ' . $prefix . '"' . $cityName . '" }';
        } else {
            $allCities[] = '{ ' . $line . ' }';
        }
    }
}

/// --- PART B: ADD MANUAL ADDITIONS IF YOU NEED THEM--- 
$allCities[] = '{ lat: 33.86, lng: 36.03, name: "Aali en Nahri, Lebanon" }';
$allCities[] = '{ lat: 45.807, lng: 15.166, name: "Novo Mesto, Slovenia" }';
$allCities[] = '{ lat: 31.937, lng: 35.039, name: "Modi\'in Illit, Israel" }';

echo implode(",\n", $allCities);
echo " ];\n\n";

echo "var createCitiesPlugin = function(citiesData) {
  var cityIcon = new Image();
  cityIcon.crossOrigin = 'Anonymous'; 
  cityIcon.src = 'https://yourwebsite.com/.../city-icon.png'; // Ensure this points to a valid image file
  
  var iconLoaded = false;
  cityIcon.onload = function() { iconLoaded = true; };
  var selectedCity = null;
  var hasRunAudit = false;

  return function(planet) {
    // Correct way to handle clicks in Planetary.js
    planet.onInit(function() {
      var canvas = planet.canvas; // Access the raw canvas
      
      canvas.addEventListener('click', function(event) {
        var rect = canvas.getBoundingClientRect();
        var mouseX = event.clientX - rect.left;
        var mouseY = event.clientY - rect.top;

        // Convert pixel click to [lng, lat] using D3 inversion
        var mouseCoords = planet.projection.invert([mouseX, mouseY]);

        if (mouseCoords) {
          var closest = null;
          var minDistance = 0.08; // Click sensitivity (in radians)

          citiesData.forEach(function(city) {
            var d = d3.geo.distance(mouseCoords, [city.lng, city.lat]);
            if (d < minDistance) {
              closest = city;
              minDistance = d;
            }
          });
          selectedCity = closest; // Update the label to draw
        }
      });
    });

    planet.onDraw(function() {
      var rotate = planet.projection.rotate();
      var center = [-rotate[0], -rotate[1]];
      var actuallyDrawn = 0;
      var behindGlobe = 0;

      planet.withSavedContext(function(context) {
        // Draw all city markers
        citiesData.forEach(function(city) {
          var coords = [city.lng, city.lat];
          var pos = planet.projection(coords);
          var isVisible = d3.geo.distance(coords, center) < Math.PI / 2;
          
          if (isVisible && pos) {
            if (iconLoaded && cityIcon.complete) {
              context.drawImage(cityIcon, pos[0] - 5, pos[1] - 5, 10, 10);
            } else {
              context.beginPath();
              context.arc(pos[0], pos[1], 3, 0, 2 * Math.PI);
              context.fillStyle = 'red';
              context.fill();
            }
          }
        });

        // Draw the selected city label
        if (selectedCity) {
          var sCoords = [selectedCity.lng, selectedCity.lat];
          var sPos = planet.projection(sCoords);
          var sVisible = d3.geo.distance(sCoords, center) < Math.PI / 2;

          if (sVisible && sPos) {
            context.font = '13px Arial';
            context.fillStyle = '#FFFFFF';
            context.shadowBlur = 4;
            context.shadowColor = 'black'; // Makes white text readable on light backgrounds
            context.fillText(selectedCity.name, sPos[0] + 12, sPos[1] + 5);
          }
        }
        // NEW AUDIT: Runs once after the globe starts rotating
        if (!hasRunAudit && actuallyDrawn > 0) {
          var notVisibleFront = planet.totalFound - actuallyDrawn - behindGlobe;
          console.log('G7a-Log: Cities in front but not displaying: ' + notVisibleFront + '.');
          hasRunAudit = true;
        }     
      });
    });
  };
};";
?>

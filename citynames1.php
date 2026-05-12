<?php
header('Content-Type: application/javascript; charset=utf-8');

echo "var cities = [ ";
$allCities = [];

$dataFile = 'mycitylatlng.txt';
if (file_exists($dataFile)) {
    $lines = file($dataFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if (preg_match('/^(.*name:\s*)(.*)$/', $line, $matches)) {
            $allCities[] = '{ ' . $matches[1] . '"' . trim($matches[2], " ,'\"") . '" }';
        } else {
            $allCities[] = '{ ' . $line . ' }';
        }
    }
}
$allCities[] = '{ lat: 33.86, lng: 36.03, name: "Aali en Nahri, Lebanon" }';
$allCities[] = '{ lat: 45.807, lng: 15.166, name: "Novo Mesto, Slovenia" }';
echo implode(",\n", $allCities);
echo " ];\n\n";

echo "var createCitiesPlugin = function(citiesData) {
  var cityIcon = new Image();
  cityIcon.crossOrigin = 'Anonymous'; 
  cityIcon.src = 'https://birdbreath.com';
  
  var iconLoaded = false;
  cityIcon.onload = function() { iconLoaded = true; };
  var selectedCity = null;

  return function(planet) {
    planet.onInit(function() {
      var canvas = planet.canvas;
      canvas.addEventListener('click', function(event) {
        var rect = canvas.getBoundingClientRect();
        var mouseX = (event.clientX - rect.left) * (canvas.width / rect.width);
        var mouseY = (event.clientY - rect.top) * (canvas.height / rect.height);
        var mouseCoords = planet.projection.invert([mouseX, mouseY]);

        if (mouseCoords) {
          var closest = null;
          var minDistance = 0.08; 
          citiesData.forEach(function(city) {
            var d = d3.geo.distance(mouseCoords, [city.lng, city.lat]);
            if (d < minDistance) { closest = city; minDistance = d; }
          });
          selectedCity = closest;
        }
      });
    });

    planet.onDraw(function() {
      var rotate = planet.projection.rotate();
      var center = [-rotate[0], -rotate[1]];
      var canvas = planet.canvas;
      var rect = canvas.getBoundingClientRect();
      var dpr = canvas.width / rect.width;

      planet.withSavedContext(function(context) {
        citiesData.forEach(function(city) {
          var coords = [city.lng, city.lat];
          var pos = planet.projection(coords);
          var isVisible = d3.geo.distance(coords, center) < Math.PI / 2;
          
          if (isVisible && pos) {
            context.save();
            // Reset transformation so we are drawing in raw pixels
            // This prevents the dot from growing with the globe zoom
            context.setTransform(1, 0, 0, 1, 0, 0); 
            
            // Map the projection coordinate (physical pixels) to the raw screen
            var x = pos[0];
            var y = pos[1];

            // On Retina (Mac/iPhone), we draw at '10 * dpr' to keep visual 10px size
            var size = 10 * dpr; 
            var radius = 3 * dpr;

            if (iconLoaded && cityIcon.complete) {
              context.drawImage(cityIcon, x - (size/2), y - (size/2), size, size);
            } else {
              context.beginPath();
              context.arc(x, y, radius, 0, 2 * Math.PI);
              context.fillStyle = 'red';
              context.fill();
            }
            context.restore();
          }
        });

        if (selectedCity) {
          var sCoords = [selectedCity.lng, selectedCity.lat];
          var sPos = planet.projection(sCoords);
          if (d3.geo.distance(sCoords, center) < Math.PI / 2 && sPos) {
            context.save();
            context.setTransform(1, 0, 0, 1, 0, 0);
            
            context.font = (13 * dpr) + 'px Arial';
            context.fillStyle = '#FFFFFF';
            context.shadowBlur = 3 * dpr;
            context.shadowColor = 'black';
            context.fillText(selectedCity.name, sPos[0] + (12 * dpr), sPos[1] + (5 * dpr));
            context.restore();
          }
        }
      });
    });
  };
};";
?>
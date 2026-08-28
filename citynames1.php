<?php
header('Content-Type: application/javascript');

// ===================================================================
// 1. DYNAMIC DATA LOADER ENGINE (From your citynames1.php)
// ===================================================================
echo "var cities = [ \n";
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
$allCities[] = '{ lat: 27.35, lng: 153.11, name: "Cribb Island,Australia" }';
$allCities[] = '{ lat: -61.11, lng: 55.14, name: "Point Wild, Antarctica" }';
$allCities[] = '{ lat: 45.807, lng: 15.166, name: "Novo Mesto, Slovenia" }';
$allCities[] = '{ lat: 45.807, lng: 15.166, name: "Novo Mesto, Slovenia" }';


echo implode(",\n", $allCities);
echo " \n];\n\n";
?>

// ===================================================================
// 2. THE UNIFIED PLANETARY INTERACTIVE DISPLAY LAYER
// ===================================================================
(function() {
    var cityIcon = new Image();
    cityIcon.crossOrigin = "Anonymous";
    cityIcon.src = "https://birdbreath.com";
    var useIcon = false;
    
    cityIcon.onload = function() { useIcon = true; };
    cityIcon.onerror = function() { useIcon = false; };

    window.myCitiesPlugin = function(config) {
        return function(planet) {
            var selectedCity = null;
 
            // ---------------------------------------------------------------
            // BIND RELEVANT CLICKS FOR BOTH DESKTOP AND MOBILE DEVICES
            // ---------------------------------------------------------------
            planet.onInit(function() {
                var canvas = planet.canvas;
                canvas.addEventListener('click', function(e) {
                    var rect = canvas.getBoundingClientRect();
                    
                    // Unified Coordinate Mapping: Works flawlessly on high-resolution smartphone screens
                    var mouseX = (e.clientX - rect.left) * (canvas.width / rect.width);
                    var mouseY = (e.clientY - rect.top) * (canvas.height / rect.height);

                    var clickedCoords = planet.projection.invert([mouseX, mouseY]);
                    if (!clickedCoords) return;
                    
                    var minDistance = 0.08;
                    selectedCity = null;
 
                    for (var i = 0; i < cities.length; i++) {
                        var city = cities[i];
                        var dist = d3.geo.distance([city.lng, city.lat], clickedCoords);
                        if (dist < minDistance) {
                            minDistance = dist;
                            selectedCity = city;
                        }
                    }
                });
            });

            // ---------------------------------------------------------------
            // UNIFIED RENDERING ANIMATION REFRESH CYCLE (DPI-SAFE)
            // ---------------------------------------------------------------
            planet.onDraw(function() {
                // Safeguard the drawing layer loop by working completely inside the library context
                planet.withSavedContext(function(context) {
                    var center = planet.projection.invert([planet.canvas.width / 2, planet.canvas.height / 2]);
                    if (!center) return;
                    
                    var size = 10;
                    var radius = 6; // Keep the larger dot radius
     
                    // A. Draw all static location circles completely matching your design specs
                    for (var i = 0; i < cities.length; i++) {
                        var city = cities[i];
                        var coords = [city.lng, city.lat];
                        var isVisible = d3.geo.distance(coords, center) < Math.PI / 2;
                        var pos = planet.projection(coords);
                        
                        if (isVisible && pos) {
                            var x = pos[0];
                            var y = pos[1];
                            
                            if (useIcon) {
                                context.drawImage(cityIcon, x - (size / 2), y - (size / 2), size, size);
                            } else {
                                context.beginPath();
                                context.arc(x, y, radius, 0, 2 * Math.PI);
                                
                                // Clean, bold, solid red dot
                                context.fillStyle = '#FF0000';
                                context.fill();
                                
                                // High-override solid red outline border framework
                                context.strokeStyle = '#FF0000';
                                context.lineWidth = 2;
                                context.stroke();
                            }
                        }
                    }
     
                    // B. Draw selected city typography text labels with clear font scaling
                    if (selectedCity) {
                        var scoords = [selectedCity.lng, selectedCity.lat];
                        var spos = planet.projection(scoords);
                        
                        if (d3.geo.distance(scoords, center) < Math.PI / 2 && spos) {
                            var sx = spos[0];
                            var sy = spos[1];
                            
                            // Moves the text labels safely off to the side of the large red circles
                            context.translate(sx + 14, sy + 5);
                            
                            context.font = "18px Arial, Helvetica, sans-serif";
                            context.fillStyle = '#ffffff';
                            context.shadowBlur = 3;
                            context.shadowColor = 'black';
                            context.fillText(selectedCity.name, 0, 0);
                        }
                    }
                });
            });
        };
    };
})();
(function() {
    window.addEventListener('load', function() {

        var canvas = document.getElementById('rotatingGlobe');
        var width = 400; 
        var height = 400;

        // Set the internal drawing resolution first
        canvas.width = width;
        canvas.height = height;
        var globe = planetaryjs.planet();
        
        // 1. Autorotate Plugin
        globe.loadPlugin(autorotate(3));
        

        // 2. Earth Configuration Plugin
        globe.loadPlugin(planetaryjs.plugins.earth({
            topojson: { file: '/scripts/world-110m-withlakes.json' },
            oceans:   { fill: '#000080' },
            land:     { fill: '#339966' },
            borders:  { stroke: '#008000' }
        }));
        
        // 3. Lakes Plugin
        globe.loadPlugin(lakes({
            fill: '#000080'
        }));
        
        
        // 5. Zoom Plugin configuration
globe.loadPlugin(planetaryjs.plugins.zoom({ scaleExtent: [100, 1500], initialScale: 200  })); // Some versions support this
        
        // 6. Drag Event Handlers
        globe.loadPlugin(planetaryjs.plugins.drag({
            onDragStart: function() {
                this.plugins.autorotate.pause();
            },
            onDragEnd: function() {
                this.plugins.autorotate.resume();
            }
        }));
        
        // Projection Sizing
        globe.projection.scale(200).translate([canvas.width / 2, canvas.height / 2]).rotate([92, 0, 0]).clipAngle(90);;
        
        // Ping Generation Setup
        var colors = ['red', 'yellow', 'white', 'orange', 'green', 'cyan', 'pink'];
        
        setInterval(function() {
            var lat = Math.random() * 170 - 85;
            var lng = Math.random() * 360 - 180;
            var color = colors[Math.floor(Math.random() * colors.length)];
            globe.plugins.pings.add(lng, lat, { 
                color: color, 
                ttl: 2000,
                angle: Math.random() * 10 
            });
        }, 150);
        
        // Canvas targeting and handling retina displays
        var canvas = document.getElementById('rotatingGlobe');
        if (window.devicePixelRatio == 2) {
            canvas.width = 800;
            canvas.height = 800;
            var context = canvas.getContext('2d');
            context.scale(2, 2);
        }
        
        // Final Core Engine Drawing Call
        globe.loadPlugin(displayCityNames());
        globe.draw(canvas);
        
        // Plugin Declaration Helper: Autorotate
        function autorotate(degPerSec) {
            return function(planet) {
                var lastTick = null;
                var paused = false;
                
                planet.plugins.autorotate = {
                    pause:  function() { paused = true; },
                    resume: function() { paused = false; }
                };
                
                planet.onDraw(function() {
                    if (paused || !lastTick) {
                        lastTick = new Date();
                    } else {
                        var now = new Date();
                        var delta = now - lastTick;
                        var rotation = planet.projection.rotate();
                        rotation[0] += degPerSec * delta / 1000;
                        if (rotation[0] >= 180) rotation[0] -= 360;
                        planet.projection.rotate(rotation);
                        lastTick = now;
                    }
                });
            };
        }
        
        // Plugin Declaration Helper: Lakes
        function lakes(options) {
            options = options || {};
            var lakes = null;
            return function(planet) {
                planet.onInit(function() {
                    var world = planet.plugins.topojson.world;
                    lakes = topojson.feature(world, world.objects.ne_110m_lakes);
                });
                
                planet.onDraw(function() {
                    planet.withSavedContext(function(context) {
                        context.beginPath();
                        planet.path.context(context)(lakes);
                        context.fillStyle = options.fill || 'black';
                        context.fill();
                    });
                });
            };
        }

        // NEW WORKER LOGIC: Interactive Dot Placement and Selection System
function displayCityNames() {
    return function(planet) {
        var selectedCity = null;

        planet.onInit(function() {
            var globeCanvas = document.getElementById('rotatingGlobe');
            
            globeCanvas.addEventListener('click', function(event) {
                var rect = globeCanvas.getBoundingClientRect();
                
                // FIX: Keep coordinates in the 1x CSS space (0-400) to perfectly 
                // match the values returned by planet.projection()
                var clickX = event.clientX - rect.left;
                var clickY = event.clientY - rect.top;

                var closestCity = null;
                var clickTolerance = 10; // Click radius in pixels around a dot
                
                var rotation = planet.projection.rotate();
                var globeCenter = [-rotation[0], -rotation[1]];

                window.cities.forEach(function(city) {
                    var distance = d3.geo.distance([city.lng, city.lat], globeCenter);
                    
                    if (distance < Math.PI / 2) {
                        var coordinates = planet.projection([city.lng, city.lat]);
                        if (coordinates) {
                            var dx = clickX - coordinates[0];
                            var dy = clickY - coordinates[1];
                            var pixelDist = Math.sqrt(dx * dx + dy * dy);

                            if (pixelDist < clickTolerance) {
                                clickTolerance = pixelDist;
                                closestCity = city;
                            }
                        }
                    }
                });

                selectedCity = closestCity;
            });
        });

        planet.onDraw(function() {
            if (!window.cities || window.cities.length === 0) return;

            planet.withSavedContext(function(context) {
                var rotation = planet.projection.rotate();
                var globeCenter = [-rotation[0], -rotation[1]];

                // 1. Render all static red dots across facing geography
                window.cities.forEach(function(city) {
                    var distance = d3.geo.distance([city.lng, city.lat], globeCenter);
                    
                    if (distance < Math.PI / 2) {
                        var coordinates = planet.projection([city.lng, city.lat]);
                        if (coordinates) {
                            context.beginPath();
                            context.arc(coordinates[0], coordinates[1], 4, 0, 2 * Math.PI);
                            context.fillStyle = "red";
                            context.fill();
                        }
                    }
                });

                // 2. Render only the clicked city's label string on top
                if (selectedCity) {
                    var distance = d3.geo.distance([selectedCity.lng, selectedCity.lat], globeCenter);
                    
                    if (distance < Math.PI / 2) {
                        var coordinates = planet.projection([selectedCity.lng, selectedCity.lat]);
                        if (coordinates) {
                            context.fillStyle = "white";
                            context.font = "bold 11px sans-serif";
                            context.textAlign = "center";
                            context.textBaseline = "bottom";
                            
                            context.shadowColor = "black";
                            context.shadowBlur = 4;

                            context.fillText(selectedCity.name, coordinates[0], coordinates[1] - 6);
                        }
                    } else {
                        selectedCity = null; 
                    }
                }
            });
        });
    };
}
        
        // Successful Console Output Anchor
        console.log("Globe drawn successfully with cities:", window.cities.length);
    });
})();
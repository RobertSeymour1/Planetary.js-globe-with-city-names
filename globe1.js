(function() {
  console.log("G7a-Log: Script start...");

  if (typeof createCitiesPlugin === 'undefined' || typeof cities === 'undefined') {
      console.error("G7a-Log: ERROR: Dependencies missing! Check if citynames1.php is loaded.");
      return; 
  }

  // Load the TopoJSON file via D3
  d3.json('/scripts/planetary.js-1.1.2/dist/world-110m-withlakes.json', function(error, worldData) {
    if (error) {
      console.error("G7a-Log: D3 Error:", error);
      return;
    }
    console.log("G7a-Log: Data loaded. Initializing...");

    var canvas = document.getElementById('rotatingGlobe');
    var globe = planetaryjs.planet();

    // 1. Load Data into Plugins
    // Note: 'land' is specified because your JSON uses the key "land"
    globe.loadPlugin(planetaryjs.plugins.topojson({ world: worldData })); 
    globe.loadPlugin(planetaryjs.plugins.earth({
      topojson: { world: worldData },
      oceans:   { fill: '#000080' },
      land:     { fill: '#339966' },
      borders:  { stroke: '#008000' }
    }));

    // 2. Load Functionality Plugins
    globe.loadPlugin(autorotate(3)); 
    globe.loadPlugin(lakes({ fill: '#000080' })); 
    globe.loadPlugin(planetaryjs.plugins.zoom({ scaleExtent: [100, 2000] }));
    globe.loadPlugin(planetaryjs.plugins.drag());
    
    // Load Cities last so they are on top
    globe.loadPlugin(createCitiesPlugin(cities)); 

    // 3. Configure Projection
    globe.projection
      .scale(300)
      .translate([canvas.width / 2, canvas.height / 2])
      .rotate([0, -10, 0])
      .clipAngle(90);

    // 5. Start Drawing
    // IMPORTANT: planetaryjs.draw() starts its own internal animation loop.
    // Do NOT wrap this in a manual requestAnimationFrame loop.
    console.log("G7a-Log: Starting draw.");
    globe.draw(canvas); 
  });

  // --- Helper Functions ---
  function autorotate(degPerSec) {
    return function(planet) {
      var lastTick = new Date();
      var paused = false;
      planet.plugins.autorotate = {
        pause: function() { paused = true; },
        resume: function() { paused = false; }
      };
      planet.onDraw(function() {
        if (!paused) {
          var now = new Date();
          var delta = now - lastTick;
          var rotation = planet.projection.rotate();
          rotation[0] += degPerSec * delta / 1000;
          if (rotation[0] >= 180) rotation[0] -= 360;
          planet.projection.rotate(rotation);
          lastTick = now;
        } else {
          lastTick = new Date();
        }
      });
    };
  }
  
  function lakes(options) {
    options = options || {}; 
    var lakeFeatures = null;
    return function(planet) {
      planet.onInit(function() {
        var world = planet.plugins.topojson.world;
        // Search specifically for the lakes object in your TopoJSON
        if (world && world.objects && world.objects.ne_110m_lakes) {
            lakeFeatures = topojson.feature(world, world.objects.ne_110m_lakes);
        }
      });
      planet.onDraw(function() {
        if (!lakeFeatures) return;
        planet.withSavedContext(function(context) {
          context.beginPath(); 
          planet.path.context(context)(lakeFeatures); 
          context.fillStyle = options.fill || '#000080'; 
          context.fill();
        });
      });
    };
  }
})();

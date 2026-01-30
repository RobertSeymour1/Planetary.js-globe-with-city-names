<?php
?> 

<html>
    <head>
	<!-- IMPORTANT: Load plugins in this exact order -->
        <script type='text/javascript' src='citynames1.php'></script>
        <script type='text/javascript' src='https://d3js.org/d3.v3.min.js'></script>
        <script type='text/javascript' src='https://d3js.org/topojson.v1.min.js'></script>
        <script type='text/javascript' src='https://...path to your.../planetary.js-1.1.2/dist/planetaryjs.min.js'></script>
    </head>
    <body>
       <div style="text-align: center; width: 400px; margin: auto;">
         <canvas id='rotatingGlobe' width='600' height='600'></canvas>
       </div>
       <!-- IMPORTANT: Ensure filenames match exactly what is on your server -->
       <script type='text/javascript' src='globe1.js'></script>
    </body>
</html>
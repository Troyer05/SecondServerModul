<?php
if (isset($_GET["docman"])) {
    $rootpath = $_GET["docman"];
    $lastSite = Vars::this_file();

    COOKIE::set("rootpath", $rootpath);
    COOKIE::set("lastSite", $lastSite);
    
    Ref::to("docman");
}
?>

<script>
    // window.addEventListener('load', function() {
    //     const loader = document.getElementById('site_loading_indicator');
    //     loader.style.display = 'none';
    // });

    window.addEventListener('beforeunload', function() {
        const loader = document.getElementById('site_loading_indicator');
        loader.style.display = 'block';
    });
</script>

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <script src="assets/js/egg.js" async defer></script>
    <link rel="stylesheet" href="assets/vendor/bootstrap/css/bootstrap.min.css">
    <link href="assets/vendor/fonts/circular-std/style.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/libs/css/style.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/vendor/fonts/fontawesome/css/fontawesome-all.css">
    <link rel="stylesheet" href="assets/vendor/charts/chartist-bundle/chartist.css">
    <link rel="stylesheet" href="assets/vendor/charts/morris-bundle/morris.css">
    <link rel="stylesheet" href="assets/vendor/fonts/material-design-iconic-font/css/materialdesignicons.min.css">
    <link rel="stylesheet" href="assets/vendor/charts/c3charts/c3.css">
    <link rel="stylesheet" href="assets/vendor/fonts/flag-icon-css/flag-icon.min.css">
    <link rel="icon" type="image/x-icon" href="assets/images/logo.png">
    <title>greenbucket® ShareSuite</title>
</head>

<div id="site_loading_indicator" style="display: block;">
    <h4 id="site_loading_indicator_h4">Moment...</h4>
    <div class="container-tree">
        <div class="tree">
            <div class="branch" style="--x:0">
                <span style="--i:0;"></span>
                <span style="--i:1;"></span>
                <span style="--i:2;"></span>
                <span style="--i:3;"></span>
            </div>
            <div class="branch" style="--x:1">
                <span style="--i:0;"></span>
                <span style="--i:1;"></span>
                <span style="--i:2;"></span>
                <span style="--i:3;"></span>
            </div>
            <div class="branch" style="--x:2">
                <span style="--i:0;"></span>
                <span style="--i:1;"></span>
                <span style="--i:2;"></span>
                <span style="--i:3;"></span>
            </div>
            <div class="branch" style="--x:3">
                <span style="--i:0;"></span>
                <span style="--i:1;"></span>
                <span style="--i:2;"></span>
                <span style="--i:3;"></span>
            </div>
            <div class="stem">
                <span style="--i:0;"></span>
                <span style="--i:1;"></span>
                <span style="--i:2;"></span>
                <span style="--i:3;"></span>
            </div>
            <span class="shadow-tree"></span>
        </div>
    </div>
</div>

<center>
    <div id="loader"></div>
</center>

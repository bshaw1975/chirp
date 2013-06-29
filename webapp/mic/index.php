<!doctype html>
<?php require_once '../webapp.php';?>
<html>
<head>
<style>
</style>
<link rel="stylesheet" type="text/css" href="/mic/wami.css">
<script src="/mic/swfobject.js"></script>
<script src="/mic/wami.js"></script>
<script>
function setupMain() {
    Wami.setup({
        id : "wami",
        opaque : true, // opaque so the GUI is skipped
        onReady : function () {}
    });
}
</script>
</head>
<body onload="setupMain()">
right-click for settings
<div id="wami" name="wami"></div>
</body>
</html>
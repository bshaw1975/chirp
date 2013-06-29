<!doctype html>
<?php require_once 'webapp.php';?>
<html>
<head>
<title>Living Sound</title>
<style>
.yui3-tabview-content,
.yui3-tabview-panel,
.yui3-tab-panel {
    height:100%;
}
.tabframe,
.yui3-tabview, 
.yui3-tabview-content {
    width:99%;
}
.tabframe {
    border:none;
}
iframe, body {
    width:100%;
}
body {
    height:2400px;
    overflow-x:hidden;
}
#tabview {
    margin-right:20px;
    height:100%;
    display:inline;
}
#about    { height:400px; }
#tutor    { height:300px; }
#nwinfo   { height:250px; }
#mic      { height:250px; }
#annotate { height:300px; }
#taxonomy { height:400px; }
</style>
<script src="<?php echo webapp::config('yui_base');?>/build/yui/yui-min.js"></script>
<script src="/js/ydialog.js"></script>
<script>

window.openUrl = function(url, tall) {
    ydialog(url, tall);
}

YUI().use('tabview', function(Y) {
var tabview = new Y.TabView({
children: [{
label: '---',//'<a onclick="onTabShow(false)">Toggle</a>',
content: ''
}, {
label: 'About',
content: '<iframe id="about" scrollbars="no" class="tabframe" src="/about" />'
}, /*{
label: 'Tutorial',
content: '<iframe id="tutor" scrollbars="no" class="tabframe" src="/tutor" />'
},*/{
label: 'Network',
content: '<iframe id="nwinfo" scrollbars="no" class="tabframe" src="/nwinfo" />'
},{
label: 'Flash Setup',
content: '<iframe id="mic" scrollbars="no" class="tabframe" src="/mic" />'
},/*{
label: 'Annotation',
content: '<iframe id="annotate" scrollbars="no" class="tabframe" src="/annotate" />'
},*/{
label: 'Taxonomy',
content: '<iframe id="taxonomy" scrollbars="no" class="tabframe" src="/taxonomy" />'
}]
});
tabview.render('#tabview');
tabview.selectChild(0);
});
</script>
</head>
<body class="yui3-skin-sam">
<div id="tabview"></div>
<iframe src="/main" style="height:95%;width:95%" />
</body>
</html>
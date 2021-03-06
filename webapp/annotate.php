<!doctype html>
<?php require_once 'webapp.php';?>
<html>
<head>
<style>
body {
    overflow-x:hidden;
    display:inline-block;
    width:100%;
}
#editor {
    height:2000px;
}
</style>
<script src="<?php echo webapp::config('yui_base');?>/build/yui/yui-min.js"></script>

<script type="text/javascript">
YUI( ).use(
'editor-base', 'gallery-itsatoolbar', function (Y) {
  var extracss = 'h1 {color:#FF00FF;font-size:24px;} h2 {color:#7F003F;font-size:18px;font-family:"Arial Black";} h3 {color:#C49B71;font-size:14px;font-family:"Tahoma";font-weight:normal;} h4 {color:#482C1B;font-size:10px;font-family:"Lucida Console";font-style:italic;font-weight:normal;} h5 {color:#AEA945;font-size:8px;font-weight:normal;} h6 {color:#7FA37C;font-size:6px;font-weight:normal}';
  var myEditor = new Y.EditorBase({
      extracss : extracss,
      content : '<p>Hello world. Just edit the text using the toolbar.<br />created by <b>Its Asbreuk</b></p><h2>this is a header</h2><p>Yes, headers are also supported!<br />The editor has been tested for firefox, ie8, chrome and safari. Opera unfortunately fails when making selections.</p>'
    });
  myEditor.plug(Y.Plugin.ITSAToolbar, {
    srcNode : '#toptoolbar',
    btnSize : 1
  });
  myEditor.render('#editor');
});
</script>
</head>
<body>
<div id='toptoolbar'></div>
<div id='editor'></div>
</body>
</html>

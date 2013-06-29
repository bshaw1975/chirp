<!doctype html>
<?php require_once 'webapp.php';?>
<html>
<head>
<style>
iframe, table, body, html { 
    overflow:hidden;border:none;width:100%;height:100%;
}
#right { position: fixed; right: 0; top: 0; width: 30%; }
#center { position: absolute; left: 240px; top: 0px; margin: 4px; font-size:small; width: 60%; }
#right, #center { height:100%; }
</style>

<link rel="stylesheet" type="text/css" href="/mic/wami.css"/>
<script src="/mic/swfobject.js"></script>
<script src="/mic/wami.js"></script>

<script>
window.contact = {
    'id':'-1',
    'certain':'-1',
    'location':'-1'
}
window.openUrl = function(url, tall) {
    window.parent.openUrl(url, tall);
}
window.getLocalDtime = function() {
    return new Date().toString();
}
window.contactSelected = function(id, name) {
    window.contact.id = id;
}
function onLoadBody() {
    Wami.setup({
        id : "wami",
        opaque : false,
        onReady : onWamiReady
    });
    // until this can be done with the GUI
    window.contact.certain = 1.0;
    window.contact.location = navigator.appName+','+navigator.appCodeName+','+navigator.appVersion;

}
function onWamiReady() {
    var gui = new Wami.GUI({
        id : "wami",
        recordUrl : "/mic/save",
        playUrl : "/mic/play"
    });
    gui.setPlayEnabled(false);
    document.getElementById('recflashwarn').innerHTML = '';
    document.getElementById('center').style.left='90px';
    document.getElementById('wamigui').style.position='absolute';
}
function onMicSubmit(form) {
    form.recname.value = prompt('Clip Name ?');
    if (!form.recname.value) return false;
    form.contact.value = window.contact.id;
    form.certain.value = window.contact.certain;
    form.location.value = window.contact.location;
    form.localdt.value = window.getLocalDtime();
    form.recsubmit.disabled = true;
    form.recsubmit.style.cursor=
    form.style.cursor='progress';
    return true;
}
</script>
</head>

<body class="yui3-skin-sam" onload="onLoadBody()">
    <form name="myForm" id="myForm" action="/mic/submit" method="post" onsubmit="onMicSubmit(this);">
        <div id="wami" name="wami">
            <div id="recflashwarn">adobe flash micro phone required</div>
        </div>
        <input id="recsubmit" name="" type="submit" value="" title="Publish" /><br/>
        <input id="recname" name="name" type="hidden" value="unset" />
        <input id="localdt" name="localdt" type="hidden" value="unset" />
        <input id="location" name="location" type="hidden" value="unset" />
        <input id="contact" name="contact" type="hidden" value="unset" />
        <input id="certain" name="certain" type="hidden" value="unset" />
        <div id="calltype"><br/>Call Type<br/>
<?php foreach (array_reverse(array(0=>'Other',1=>'Unknown',2=>'Speech',3=>'Song Solo', 4=>'Song Duet',5=>'Song Chorus',6=>'Mating',7=>'False Alarm',8=>'Alarm',9=>'Territory',10=>'Fighting',11=>'Mimicry',12=>'Mobbing',13=>'Flight'), true) as $id=>$vocaliz) echo '<INPUT value="'.$id.'" type="radio" name="vocaliz" '.(0==$id?'checked':'')." />$vocaliz<br/>\n";?>
        </div>
    </form>
    
    <div id="center">
        <iframe scrolling="no" src="treeview" ></iframe>
    </div>

    <div id="right">
        <iframe scrolling="no" src="upload" ></iframe>
    </div>
</body>
</html>
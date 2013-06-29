<script src="/js/jquery-1.9.1.min.js"></script>
<script src="/js/jquery.MultiFile.pack.js"></script>
<script>
$(function(){ // wait for document to load
    $('#multif').MultiFile({
        STRING: {
            file: 
'<em title="Click to remove" style="cursor:pointer" onclick="$(this).parent().prev().click()">$file</em>'
            ,remove: '<button>x</button>'//'<img src="" height="16" width="16" alt="x"/>'
        },
        max: 5,
        accept: '*',//'gif|jpg|png|bmp|swf'
        onFileRemove: function(element, value, master_element){
        },
        afterFileRemove: function(element, value, master_element){
        },
        onFileAppend: function(element, value, master_element){
        },
        afterFileAppend: function(element, value, master_element){
        },
        onFileSelect: function(element, value, master_element){
        },
        afterFileSelect: function(element, value, master_element){
        }
    });
});
function multifOnSubmit(form) {
    form.localdt.value=updtime();
    form.location.value=uplocation();
    form.contact.value=upcontact();
    form.certain.value=upcertain();
    return true;
}
</script>
</head>
<body>
<div id="outerdiv">
<form action="/upload/" method="post" enctype="multipart/form-data" onsubmit="return multifOnSubmit(this);">
<p>Uploader type: html4</p>
<fieldset style="display:inline-block;">
    <input type="file" id="multif" name="files[]" /><!--DISABLES EVENTS class="multi"-->
    <input type="hidden" id="localdt" name="localdt" value="unset" />
    <input type="hidden" id="location" name="location" value="unset" />
    <input type="hidden" id="contact" name="contact" value="unset" />
    <input type="hidden" id="certain" name="certain" value="unset" />
</fieldset>
<input style="display:inline-block" type="submit" value="Submit"/>
</form>

<?php
require_once 'view.php'; // lists upload/content
?>

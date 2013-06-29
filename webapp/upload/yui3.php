<style>
    #filelist {
        margin-top: 15px;
    }
    #uploaderContainer {
        float:right;
    }
    #uploadFilesButtonContainer, #selectFilesButtonContainer, #overallProgress {
        display: inline-block;
    }
</style>
<script src="<?php echo webapp::config('yui_base');?>/build/yui/yui-min.js"></script>
</head>
<body class="yui3-skin-sam" style="font-size:small">
<div id="outerdiv">
<span class="yui3-tabview">
<div id="uploaderContainer">
  <div id="selectFilesButtonContainer"></div>
    <div id="uploadFilesButtonContainer">
        <button type="button" id="uploadFilesButton"
        class="yui3-button" style="width:100px; height:35px;">Send All</button>
  </div>
  <div id="filelist">
    <table id="filenames" style="border:1px solid;width:100%;">
        <thead>
            <tr><th>Name</th><th>Size</th><th>% Sent</th></tr>
            <tr id="nofiles">
                <td colspan="3">No files are selected.</td>
            </tr>
        </thead>
        <tbody>
        </tbody>
    </table>
  </div>
  <div id="overallProgress"></div>
</div>
<script>
YUI({
    filter:"raw"
}).use("uploader", function(Y) {
    Y.one("#overallProgress").set("text", "Uploader type: " + Y.Uploader.TYPE);
    if (Y.Uploader.TYPE != "none" && !Y.UA.ios) {
        var uploader = new Y.Uploader({
            width: "100px",
            height: "35px",
            withCredentials: false,
            multipleFiles: true,
            simLimit: 2,
            uploadURL: "/upload/"
            /* gets stuck 
            ,swfURL: "<?php echo webapp::config('yui_base');?>/build/uploader/assets/flashuploader.swf"
            */
        });
        var uploadDone = false;
        
        uploader.render("#selectFilesButtonContainer");
        
        uploader.after("fileselect", function (event) {
            
            var fileList = event.fileList;
            var fileTable = Y.one("#filenames tbody");
            if (fileList.length > 0 && Y.one("#nofiles")) {
                Y.one("#nofiles").remove();
            }
            
            if (uploadDone) {
                uploadDone = false;
                fileTable.setHTML("");
            }
            
            Y.each(fileList, function (fileInstance) {
                fileTable.append("<tr id='" + fileInstance.get("id") + "_row" + "'>" +
                "<td class='filename'>" + fileInstance.get("name") + "</td>" +
                "<td class='filesize'>" + fileInstance.get("size") + "</td>" +
                "<td class='percentdone'>Hasn't started yet</td>");
            });
        });
        
        uploader.on("uploadprogress", function (event) {
            var fileRow = Y.one("#" + event.file.get("id") + "_row");
            fileRow.one(".percentdone").set("text", event.percentLoaded + "%");
        });
        
        uploader.on("uploadstart", function (event) {
            uploader.set("enabled", false);
            Y.one("#uploadFilesButton").addClass("yui3-button-disabled");
            Y.one("#uploadFilesButton").detach("click");
            uploader.postVarsPerFile = {
                'localdt': updtime(),
                'location': uplocation(),
                'contact': upcontact(),
                'certain': upcertain()
            }
            /*uploader.uploadHeaders = {
                'HTTP_LOCALDT': new Date().toString(),
                'HTTP_LOCATION': navigator.appName+','+navigator.appCodeName+','+navigator.appVersion,
                'HTTP_CONTACT': upcontact()
            }*/
        });
        
        uploader.on("uploadcomplete", function (event) {
            var fileRow = Y.one("#" + event.file.get("id") + "_row");
            fileRow.one(".percentdone").set("text", "Finished!");
            window.location.reload();
        });
        
        uploader.on("totaluploadprogress", function (event) {
            Y.one("#overallProgress").setHTML("Total uploaded: <strong>" + event.percentLoaded + "%" + "</strong>");
        });
        
        uploader.on("alluploadscomplete", function (event) {
            uploader.set("enabled", true);
            uploader.set("fileList", []);
            Y.one("#uploadFilesButton").removeClass("yui3-button-disabled");
            Y.one("#uploadFilesButton").on("click", function () {
                if (!uploadDone && uploader.get("fileList").length > 0) {
                    uploader.uploadAll();
                }
            });
            Y.one("#overallProgress").set("text", "Uploads complete!");
            uploadDone = true;
        });
        
        Y.one("#uploadFilesButton").on("click", function () {
            if (!uploadDone && uploader.get("fileList").length > 0) {
                uploader.uploadAll();
            }
        });
    }
    else {
        //Y.one("#uploaderContainer").set("text", "We are sorry, but to use the uploader, you either need a browser that support HTML5 or have the Flash player installed on your computer.");
        window.location="/upload/multif";
    }
});
</script>
</span>

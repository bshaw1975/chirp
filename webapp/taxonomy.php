<!doctype html>
<?php require_once 'webapp.php';?>
<html>
<head>
<style>
#search {
    width:80%;
}
#treetop {
    float:left;
}
.yui3-treeview-search-result {
    background-color: #80B2FF;
    color: white;
}
</style>
</head>
<?php
function asJary($rs) {
    $top = array();
    while ($row = $rs->fetch_assoc()) {
        $r =& $top;
        $prev =& $r;
        foreach (explode(' ',$row['taxonomy']) as $label) {
            // find this class
            for ($i=0; $i < count($r); $i++)
                if ($label == substr($r[$i]['label'], 0, strlen($label)))
                    break;
            // if missing add class
            if (count($r)==$i) $r[$i] = array(
                'label'=>$label, 'children'=>array()
            );
            // next subclass
            $prev =& $r[$i];
            $r =& $r[$i]['children'];
        }
        $lastrow = $row;
        $r[count($r)]['label'] = isset($row['con_id']) ? webapp::asHtCtrl(
            $row['con_id'], $row['con_name'], $row['upl_id'],$row['upl_name'],$row['filen'],$row['exten']
        ) : $row['common'];
    }
    return json_encode($top);
}
$db=webapp::newRoMysqli();
$rs = webapp::query($db,'
select 
  eukary.taxonomy as taxonomy, eukary.common as common,
  contact.id as con_id, contact.name as con_name, 
  upload.id as upl_id, upload.name as upl_name, upload.filen as filen, upload.exten as exten
from eukary 
 left join contact_euk on eukary.id =contact_euk.euk_id
 left join contact     on contact.id=contact_euk.con_id
 left join contact_upl on contact.id=contact_upl.con_id
 left join upload      on upload.id=contact_upl.upl_id
');
?>

<body class="yui3-skin-sam" >
<input id="search" class="popup-menu-item" type="text" placeholder="type here to search">
<button id="treetop" onclick="treeview.toggleAll()">Toggle</button>
<div id="treeview" style="padding-top:10px;"></div>

<script src="<?php echo webapp::config('yui_base');?>/build/yui/yui-min.js"></script>
<script src="<?php echo webapp::config('yui_base');?>/build/gallery-sm-treeview/gallery-sm-treeview-min.js"></script>
<script src="<?php echo webapp::config('yui_base');?>/build/gallery-sm-treeview-templates/gallery-sm-treeview-templates-min.js"></script>

<script>

    var treetop = null
    var treeview = null;
    var nodeTitles = null;
    
    YUI().use('gallery-scrollintoview','gallery-sm-treeview',function (Y) {

        nodeTitles = {};
        treetop = Y.one('#treetop');
        treeview = new Y.TreeView({
            container : "#treeview",
            lazyRender: true,
            nodes: <?php 
                print(asJary($rs));
                $rs->close(); 
                $db->close(); 
            ?>
        });
        
        treeview.render();

        Y.on('keyup', function() {
            Y.log('keyup');
            treeview.closeAll();
            Y.one('#treeview').all('.yui3-treeview-node').removeClass('yui3-treeview-search-result');
            var term = Y.one('#search').get('value');
            if (term.length < 1) return;
            var regex = new RegExp(term, 'i');
            for (var title in nodeTitles) {
                if (true===regex.test(title)) {
                    nodeTitles[title].addClass('yui3-treeview-search-result');
                }
            }
            treeview.openSelected();
        }, '#search');
        
        Y.TreeView.prototype.toggleAll = function () {
            var first = this.getHTMLNode(this.children[0]);
            if (first.hasClass('yui3-treeview-open')) {
                this.closeAll();
            }
            else {
                this.openAll();
            }
        }
        
        Y.TreeView.prototype.closeAll = function () {
            var tree = this;
            Y.all('.yui3-treeview-has-children').each(function() {
                var node = tree.getNodeById(this.get('id'));
                node.close();
            });
            treetop.scrollIntoView();
        };
        
        Y.TreeView.prototype.openAll = function () {
            var tree = this;
            Y.all('.yui3-treeview-has-children').each(function () {
                var node = tree.getNodeById(this.get('id'));
                node.open();
            });
            treetop.scrollIntoView();
        };
        
        Y.TreeView.prototype.openRecurs = function (title) {
            var tree = this;
            nodeTitles[title].ancestors().each(function() {
                if (this.hasClass('yui3-treeview-has-children')) {
                    var node = tree.getNodeById(this.get('id'));
                    node.open();
                }
            });
        }
        
        Y.TreeView.prototype.openSelected = function () {
            var tree = this;
            Y.one('#treeview').all('.yui3-treeview-search-result').each(function() {
                tree.openRecurs(this.get('text'));
            });
        }

        Y.one('#treeview').all('.yui3-treeview-node:not(.yui3-treeview-can-have-children)').each(function() {
            nodeTitles[this.get('text')] = this;
        });
    });
</script>
</body>
</html>
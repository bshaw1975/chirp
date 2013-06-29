<!doctype html>
<?php require_once 'webapp.php';?>
<html>
<head>
<style>
body {
    border:none;margin-left:4px;
}
#search {
    width:70%;
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
function asJary($rows) {
    $tree = array(
        0=>array('label'=>'All Contacts','children'=>array()),
        1=>array('label'=>'Just Sounds','children'=>array())
    );
    $snd =& $tree[1]['children'];
    $all =& $tree[0]['children'];
    $lastcid = $c1id = null;
    foreach ($rows as $row) { // unknown
        $lastcid = $c1id;
        $c1id = $row['c1id'];
        if ($lastcid!=$c1id) {
            $all[] = array('label'=>strval($row['c1name']));
        }
        $htctA = webapp::asHtCtrl(
$row['c1id'], $row['c1name'], $row['u1id'],$row['u1name'],$row['u1filen'],$row['u1exten']
        );
        $htctB = null;
        if (isset($row['u2id'])) {
            $htctB = webapp::asHtCtrl(
$row['c2id'], $row['c2name'], $row['u2id'],$row['u2name'],$row['u2filen'],$row['u2exten']
            );
        }
        $curr =& $all[ count($all) - 1 ];
        $nodeA = array('label'=>$htctA);
        if (isset($htctB)) {
            // TODO add the dial for the diff here
            $nodeA['children'] = array(0=>array('label'=>$htctB));
        }
        $curr['children'][] = $nodeA;
        if (webapp::isFilext('audio',$row['u1exten'])) {
            $snd[] = array(
                'label'=>$row['c1name'],
                'children'=>array(
                    0=>array(
                        'label'=>webapp::asHtCtrl(
                            $row['c1id'], $row['c1name'], $row['u1id'],$row['u1name'],$row['u1filen'],$row['u1exten']
                        )
                    )
                )
            );
        }
    }
    return json_encode($tree, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE);
}

$db=webapp::newRoMysqli();
$rs = webapp::query($db,'
 select 
    round(min(ifnull(clip_diff.diff, -1)),4) as diff,
    c1.id as c1id, c1.name as c1name, u1.id as u1id, u1.name as u1name, u1.filen as u1filen, u1.exten as u1exten,
    c2.id as c2id, c2.name as c2name, u2.id as u2id, u2.name as u2name, u2.filen as u2filen, u2.exten as u2exten
 from 
    contact as c1 /* get outer contacts */
    join contact_upl as cup1 on c1.id=cup1.con_id 
    join upload as u1 on u1.id=cup1.upl_id /* get outer uploads */
    left join clip_diff on ( /* get clip diff */
        u1.id =clip_diff.upl1_id or
        u1.id =clip_diff.upl2_id /* 1st or 2nd ID */
    )
    left join upload as u2 on ( /* get inner upload */
        u2.id!=u1.id and ( /* not same as original */
            u2.id =clip_diff.upl1_id or
            u2.id =clip_diff.upl2_id /* 1st or 2nd ID */
        )
    )
    left join contact_upl as cup2 on ( /* get inner contact */
        u2.id=cup2.upl_id and c1.id!=cup2.con_id /* not outer */
    )
    left join contact as c2 on c2.id=cup2.con_id
 group by c1.id, u1.id 
 order by c1.id desc, ifnull(u2.id,0) desc
');

$rows = array();
while ($row = $rs->fetch_assoc()) $rows[]=$row;
$rs->close();
$db->close();
?>

<body class="yui3-skin-sam">
<!--<pre>here is the tree</pre>-->
<input id="search" class="popup-menu-item" type="text" placeholder="type here to search">
<button id="treetop" onclick="treeview.toggleAll()">Toggle</button>
<div id="treeview" style="padding-top:20px;"></div>

<script src="<?php echo webapp::config('yui_base');?>/build/yui/yui-min.js"></script>
<script src="<?php echo webapp::config('yui_base');?>/build/gallery-sm-treeview/gallery-sm-treeview-min.js"></script>
<script src="<?php echo webapp::config('yui_base');?>/build/gallery-sm-treeview-templates/gallery-sm-treeview-templates-min.js"></script>
<script>
    
    var treetop = null
    var treeview = null;
    var nodeTitles = null;
    
    YUI().use('dial','gallery-scrollintoview','gallery-sm-treeview',function (Y) {
        
        nodeTitles = {};
        treetop = Y.one('#treetop');
        treeview = new Y.TreeView({
            container : "#treeview",
            lazyRender: true,
            nodes://undefined
            <?php
                print(asJary($rows));
                unset($rows);
            ?>
            
        });
        
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
        
        treeview.render();
        
        treeview.on("select", function (e) {
            window.parent.contactSelected(e.id, e.name);
        });
        
        //treeview.openAll(); lazy load the embedded content
        
        Y.one('#treeview').all('.yui3-treeview-node:not(.yui3-treeview-can-have-children)').each(function() {
            nodeTitles[this.get('text')] = this;
        });
        Y.log(nodeTitles);
    });
</script>
</body>
</html>
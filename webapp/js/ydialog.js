function ydialog(url, tall) { 

  host = skin = '';
  uage =  (
    window.sidebar ? 'moz' : ( // Mozilla
    window.external && !window.chrome ? 'ie' : ( // MSIE
    window.opera && window.print ? 'ope' : ( // Opera
    'webk' // Safari / Chrome / etc...
  ))));

  function wind() {
      return window;//window.parent;
  }

  if ( (typeof YUI!=='function') || (document.readyState !== 'complete') ) {
    wind().setTimeout("ydialog('"+url+"')", 200);
    return;
  }
  
  if (yDialog=wind().yDialog) {
    wind().yDialogBody.children[0].style.display='inline';
    wind().yDialogBody.children[0].src = yDialog.url = url;
    yDialog._resHeight = tall ? Math.max(400, yDialog._resHeight) : 200;
    yDialog.restore();
    return;
  }

  yskins=['sam','night'];
  skin = parseInt(skin);
  skin = isNaN(skin) ? 0:skin;
  skin = Math.min(Math.max(skin,0),yskins.length-1);
  yui3skin=yskins[skin];

  YUI({ 
    skin: yui3skin // use insertBefore to apply a style sheet
  }).use(
'resize-constrain','resize-proxy','resize-plugin','resize','dd','panel','node','event',
  function (Y) {
    Y.one(wind().document.body).appendChild(
      Y.Node.create('\
<div class="yui3-skin-'+yui3skin+'"><span class="yui3-tabview">\
<div id="yDialogContent" style="overflow:hidden"></div>\
<div id="yDialogBody"><iframe src="'+url+'"\
  scrolling="yes" border="0" marginwidth="0" frameborder="0" style="overflow-x:hidden;overflow-y:scroll;min-width:100%;min-width:100%;width:100%;height:100%;border:none;"/>\
</div></span></div>'
      )
    );
    yDialog = wind().yDialog = new Y.Panel({
      buttons: { header : [ /*{ name:'close'}*/ ] },
      contentBox: Y.one('#yDialogContent'),
      bodyContent: Y.one('#yDialogBody'),
      modal: false,
      centered:true,render:true,stack:true,visible:true,draggable:true,constraintoviewport:true
    });
    yDialog.url = url;
    url = null;
    yDialog.showHW = function(h,w) {
      this.set('offsetHeight', h);
      this.set('height', h);
      this.set('offsetWidth', w);
      this.set('width', w);
      this.show();
    }
    yDialog.maximize = function () {
      this.showHW(wind().innerHeight, wind().innerWidth);
    }
    yDialog.minimize = function() {
      this.showHW(this._minHeight, this._minWidth);
    };
    yDialog.restore = function() {
      this.showHW(this._resHeight, this._resWidth);
      this.move(200,100,100); // near top middle
    };
    if ('f'==skin[0]) { // full screen
      yDialog.maximize();
    }
    yDialog.addButton({
      action: function(e) { // minimize
        e.preventDefault();
        h=yDialog.get('height');
        w=yDialog.get('width');
        if (''==h || ''==w) {
          yDialog.minimize();
        }
        else if (yDialog._minHeight < h) {
          yDialog._resHeight = h;
          yDialog._resWidth =  w;
          yDialog.minimize();
        }
        else {
          yDialog.restore();
        }
        this.callback = false;
      },section: 'header'
    });
    yDialog.addButton({
      action: function(e) { // restore
        e.preventDefault();
        yDialog.restore();
        this.callback = false;
      },section: 'header'
    });
    yDialog.addButton({
      action: function(e) { // open / maximize
        //window.opener=self;
        wind().open(yDialog.url,'_WETLLC',
          'directories=no,titlebar=no,toolbar=no,location=no,status=no,menubar=no'+
          ',width='+yDialog._resWidth+
          ',height='+yDialog._resHeight
        );
        yDialog.hide();
      },section: 'header'
    });
    yDialog.addButton({
      action: function(e) { // close
        e.preventDefault();
        this.callback = false;
        yDialog.hide();
        //yDialog.destroy();
        //yDialog = wind().yDialog = null;
        // ie8 leaves the iframe rendered - below does not help
        wind().yDialogBody.children[0].style.display='none';
      },section: 'header'
    });
    
    titleBar = wind().yDialogContent.children[0];
    buttons =  titleBar.children[0].children;
    for (i=0; i < buttons.length && (btn = buttons[i]); i++) {
      btn.title=[/*'No-Icon-A','No-Icon-B',*/
        'Minimize','Restore','Pop Out','Close'
      ][i];
      if (i >= 0) { // greater than count of text only buttons
        if ('ie8'!=uage) {
          // breaks in yui3 for IE8 which already supplies this effect!
          var el,elBgClr,elBgImg,node=Y.Node(btn);
          node.on('mouseenter', function(e){
            el = document.getElementById(this.get('id'));
            elBgImg = el.style.backgroundImage;
            elBgClr = el.style.backgroundColor;
            el.style.backgroundColor=titleBar.style.backgroundColor;
          });
          node.on('mouseleave', function(e){
            el.style.backgroundImage=elBgImg;
            el.style.backgroundColor=elBgClr;
          });
        }
        btn.style.backgroundImage=yDialogIcons.style.backgroundImage;
        btn.style.backgroundPosition='6px '+((i - 3) * 30 + 6)+'px';
        btn.style.backgroundColor='transparent';
        btn.style.backgroundRepeat='no-repeat';
      }
      else {
        btn.innerText = btn.textContent = btn.title;
      }
      btn.style.verticalAlign='top';
      btn.style.height=titleBar.style.height='25px';
    }

    bodyFrame = wind().yDialogContent.children[1];
    bodyFrame.style.padding='4px';

    yDialog.plug(Y.Plugin.Resize,{
      handles: ['tl','tr','bl','br','l','t','r','b']
    });

    //isMp3 = yDialog.url.search(/\.mp3$/) > 0;
    yDialog._maxWidth = 1000;
    yDialog._maxHeight= 2000;
    yDialog._resWidth =
    yDialog._minWidth = 500;
    yDialog._resHeight= tall ? 400 : 200;
    yDialog._minHeight= wind().yDialogContent.children[0].offsetHeight;
    wind().yDialogBody.style.width=
    wind().yDialogBody.style.height='100%';

    yDialog.resize.plug(Y.Plugin.ResizeConstrained, {
      minWidth :yDialog._minWidth,
      minHeight:yDialog._minHeight,
      maxWidth :yDialog._maxWidth,
      maxHeight:yDialog._maxHeight,
      preserveRatio: false
    });
    
    yDialog.resize.on('resize:end', function(e) { 
        yDialog._resWidth=e.info.offsetWidth-25;
        yDialog._resHeight=e.info.offsetHeight-50;
    }); 
    
    drag = new Y.DD.Drag({
      node: yDialog.resize.get('wrapper'),
      dragMode: 'intersect'
      }).plug(Y.Plugin.DDProxy, {
      }).plug(Y.Plugin.DDConstrained, {
      constrain2node: '#body'
    });

    yDialog.restore();
  });

  document.body.style.cursor='auto';
}

//preload the icons
var yDialogIcons=document.createElement('div');
yDialogIcons.style.height='1px';
yDialogIcons.style.width='1px';
//'url("'+host+'/yui/build/assets/skins/'+yui3skin+'/sprite_icons.png")';
yDialogIcons.style.backgroundImage='url("/js/ydialicon.png")';
document.getElementsByTagName('head')[0].appendChild(yDialogIcons);

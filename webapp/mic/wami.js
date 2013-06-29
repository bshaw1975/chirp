var Wami = window.Wami || {};

Wami.createID = function() {
	return "wid" + ("" + 1e10).replace(/[018]/g, function(a) {
		return (a ^ Math.random() * 16 >> a / 4).toString(16)
	});
}

Wami.nameCallback = function(cb, cleanup) {
	Wami._callbacks = Wami._callbacks || {};
	var id = Wami.createID();
	Wami._callbacks[id] = function() {
		if (cleanup) {
			Wami._callbacks[id] = null;
		}
		cb.apply(null, arguments);
	};
	var named = "Wami._callbacks['" + id + "']";
	return named;
}

Wami.setup = function(options) {
	if (Wami.startRecording) {
		// Wami's already defined.
		if (options.onReady) {
			options.onReady();
		}
		return;
	}

	Wami.swfobject = Wami.swfobject || swfobject;

	if (!Wami.swfobject) {
		alert("Unable to find swfobject to help embed the SWF.");
	}

	var _options;
	setOptions(options);
	embedWamiSWF(_options.id, options.opaque, Wami.nameCallback(delegateWamiAPI));

	function setOptions(options) {
		// Start with default options
		_options = {
			swfUrl : "/mic/wami.swf",
			onReady : function() {
				Wami.hide();
			},
			onSecurity : checkSecurity,
			onError : function(error) {
				alert(error);
			}
		};

		if (typeof options == 'undefined') {
			alert('Need at least an element ID to place the Flash object.');
		}

		if (typeof options == 'string') {
			_options.id = options;
		} else {
			_options.id = options.id;
		}

		if (options.swfUrl) {
			_options.swfUrl = options.swfUrl;
		}

		if (options.onReady) {
			_options.onReady = options.onReady;
		}

		if (options.onLoaded) {
			_options.onLoaded = options.onLoaded;
		}

		if (options.onSecurity) {
			_options.onSecurity = options.onSecurity;
		}

		if (options.onError) {
			_options.onError = options.onError;
		}

		// Create a DIV for the SWF under _options.id

		var container = document.createElement('div');
		//container.style.position = 'absolute';
		_options.cid = Wami.createID();
		container.setAttribute('id', _options.cid);

		var swfdiv = document.createElement('div');
		//swfdiv.style.cssText='position:absolute;left:60px;top:100px:z-index:99;';

		var id = Wami.createID();
		swfdiv.setAttribute('id', id);

		document.getElementById(_options.id).appendChild(container);
		container.appendChild(swfdiv);

		_options.id = id;
	}

	function checkSecurity() {
		var settings = Wami.getSettings();
		if (settings.microphone.granted) {
			_options.onReady();
		} else {
			// Show any Flash settings panel you want:
			// http://help.adobe.com/en_US/FlashPlatform/reference/actionscript/3/flash/system/SecurityPanel.html
			Wami.showSecurity("privacy", "Wami.show", Wami
					.nameCallback(_options.onSecurity), Wami
					.nameCallback(_options.onError));
		}
	}

	// Embed the WAMI SWF and call the named callback function when loaded.
	function embedWamiSWF(id, opaque, initfn) {
		var flashVars = {
			visible : false,
			loadedCallback : initfn
		}

		var params = {
			allowScriptAccess : "always"
		}

		params.wmode = opaque ? "opaque":"transparent";

		if (typeof console !== 'undefined') {
			flashVars.console = true;
		}

		var version = '10.0.0';
		document.getElementById(id).innerHTML = 
'<a href="https://get.adobe.com/flashplayer/">Get Flash Player</a>';

		// This is the minimum size due to the microphone security panel
		Wami.swfobject.embedSWF(_options.swfUrl, id, 214, 137, version, null,
				flashVars, params);

		// Without this line, Firefox has a dotted outline of the flash
		Wami.swfobject.createCSS("#" + id, "outline:none");
	}

	// To check if the microphone settings were 'remembered', we
	// must actually embed an entirely new Wami client and check
	// whether its microphone is granted. If it is, it was remembered.
	function checkRemembered(finishedfn) {
		var id = Wami.createID();
		var div = document.createElement('div');
		div.style.top = '-999px';
		div.style.left = '-999px';
		div.setAttribute('id', id);
		var body = document.getElementsByTagName('body').item(0);
		body.appendChild(div);

		var fn = Wami.nameCallback(function() {
			var swf = document.getElementById(id);
			Wami._remembered = swf.getSettings().microphone.granted;
			Wami.swfobject.removeSWF(id);
			eval(finishedfn + "()");
		});

		embedWamiSWF(id, true, fn);
	}

	// Attach all the audio methods to the Wami namespace in the callback.
	function delegateWamiAPI() {
		var recorder = document.getElementById(_options.id);

		function delegate(name) {
			Wami[name] = function() {
				return recorder[name].apply(recorder, arguments);
			}
		}
		delegate('startPlaying');
		delegate('stopPlaying');
		delegate('startRecording');
		delegate('stopRecording');
		delegate('startListening');
		delegate('stopListening');
		delegate('getRecordingLevel');
		delegate('getPlayingLevel');
		delegate('setSettings');

		// Append extra information about whether mic settings are sticky
		Wami.getSettings = function() {
			var settings = recorder.getSettings();
			settings.microphone.remembered = Wami._remembered;
			return settings;
		}

		Wami.showSecurity = function(panel, startfn, finishedfn, failfn) {
			// Flash must be on top for this.
			var container = document.getElementById(_options.cid);

			var augmentedfn = Wami.nameCallback(function() {
				checkRemembered(finishedfn);
				//container.style.cssText = "position:absolute; top:0px; z-index: 99;";
			});

			recorder.showSecurity(panel, startfn, augmentedfn, failfn);
		}

		Wami.show = function() {
			//if (!supportsTransparency()) {
			//	recorder.style.visibility = "visible";
			//}
		}

		Wami.hide = function() {
			// Hiding flash in all the browsers is tricky. Please read:
			// https://code.google.com/p/wami-recorder/wiki/HidingFlash
			//if (!supportsTransparency()) {
			//	recorder.style.visibility = "hidden";
			//}
		}

		// If we already have permissions, they were previously 'remembered'
		Wami._remembered = recorder.getSettings().microphone.granted;

		if (_options.onLoaded) {
			_options.onLoaded();
		}

		if (!_options.noSecurityCheck) {
			checkSecurity();
		}
	}
}

Wami.GUI = function(options) {
	var RECORD_BUTTON = 1;
	var PLAY_BUTTON = 2;

	setOptions(options);
	setupDOM();

	var recordButton, playButton;
	var recordInterval, playInterval, timeoutInterval;

	function createDiv(id, style) {
		var div = document.createElement("div");
		if (id) {
			div.setAttribute('id', id);
		}
		if (style) {
			div.style.cssText = style;
		}
		return div;
	}

	function setOptions(options) {
		if (!options.buttonUrl) {
			options.buttonUrl = "/mic/buttons.gif";
		}

		if (typeof options.listen == 'undefined' || options.listen) {
			listen();
		}
	}

	function setupDOM() {
		var guidiv = createDiv("wamigui","top:14px;left:6px");// *******************TOP
		document.getElementById(options.id).appendChild(guidiv);

		var rid = Wami.createID();
		var recordDiv = createDiv(rid,"");
		guidiv.appendChild(recordDiv);

		recordButton = new Button(rid, RECORD_BUTTON, options.buttonUrl);
		recordButton.onstart = startRecording;
		recordButton.onstop = stopRecording;

		recordButton.setEnabled(true);

		if (!options.singleButton) {
			var pid = Wami.createID();
			var playDiv = createDiv(pid,"");
			guidiv.appendChild(playDiv);

			playButton = new Button(pid, PLAY_BUTTON, options.buttonUrl);
			playButton.onstart = startPlaying;
			playButton.onstop = stopPlaying;
		}
	}

	/**
	 * These methods are called on clicks from the GUI.
	 */
	function startRecording() {
		if (!options.recordUrl) {
			alert("No record Url specified!");
		}
		recordButton.setActivity(1);
		playButton.setEnabled(false);
		Wami.startRecording(options.recordUrl,
				Wami.nameCallback(onRecordStart), Wami
						.nameCallback(onRecordFinish), Wami
						.nameCallback(onError));
	}

	function stopRecording() {
		Wami.stopRecording();
		clearInterval(recordInterval);
		clearInterval(timeoutInterval);
		recordButton.setActivity(-10);
	}

	function startPlaying() {
		if (!options.playUrl) {
			alert('No play URL specified!');
		}
		playButton.setActivity(1);
		recordButton.setEnabled(false);
		Wami.startPlaying(options.playUrl, Wami.nameCallback(onPlayStart), Wami
				.nameCallback(onPlayFinish), Wami.nameCallback(onError));
	}

	function stopPlaying() {
		Wami.stopPlaying();
		playButton.setActivity(-10);
	}

	this.setPlayUrl = function(url) {
		options.playUrl = url;
	}

	this.setRecordUrl = function(url) {
		options.recordUrl = url;
	}

	this.setPlayEnabled = function(val) {
		playButton.setEnabled(val);
	}

	this.setRecordEnabled = function(val) {
		recordButton.setEnabled(val);
	}

	/**
	 * Callbacks from the flash indicating certain events
	 */
	function onError(e) {
		alert(e);
	}

	function onRecordStart() {
		// WAV runs about 1 MB per minute
		// full trace takes 1 minute per 100KB
		// tiny trace takes about same time as sample
		var limit = 4;
		recordInterval = setInterval(function() {
			if (recordButton.isActive()) {
				var level = Wami.getRecordingLevel();
				recordButton.setActivity(level);
			}
		}, 190); // 5 times per second
		timeoutInterval = setInterval(function() {
			if (recordButton.isActive()) {
				stopRecording();
				alert('reached max limit '+limit+' minutes');
			}
		}, limit * 60 * 1000); // minutes * sec * msec
		if (options.onRecordStart) {
			options.onRecordStart();
		}
	}

	function onRecordFinish() {
		playButton.setEnabled(true);
		recordButton.setEnabled(true);
		document.getElementById('recsubmit').disabled = false;
		document.getElementById('recsubmit').style.display = 'inline';
		if (options.onRecordFinish) {
			options.onRecordFinish();
		}
	}

	function onPlayStart() {
		playInterval = setInterval(function() {
			if (playButton.isActive()) {
				var level = Wami.getPlayingLevel();
				playButton.setActivity(level);
			}
		}, 190);
		if (options.onPlayStart) {
			options.onPlayStart();
		}
	}

	function onPlayFinish() {
		clearInterval(playInterval);
		recordButton.setEnabled(true);
		playButton.setEnabled(true);
		if (options.onPlayFinish) {
			options.onPlayFinish();
		}
	}

	function listen() {
		Wami.startListening();
		// Continually listening when the window is in focus allows us to
		// buffer a little audio before the users clicks, since sometimes
		// people talk too soon. Without "listening", the audio would record
		// exactly when startRecording() is called.
		window.onfocus = function() {
			Wami.startListening();
		};

		// Note that the use of onfocus and onblur should probably be replaced
		// with a more robust solution (e.g. jQuery's $(window).focus(...)
		window.onblur = function() {
			Wami.stopListening();
		};
	}

	function Button(buttonid, type, url) {
		var self = this;
		self.active = false;
		self.type = type;

		init();

		// Get the background button image position
		// Index: 1) normal 2) pressed 3) mouse-over
		function background(index) {
			if (index == 1)
				return "-56px 0px";
			if (index == 2)
				return "0px 0px";
			if (index == 3)
				return "-112px 0";
			alert("Background not found: " + index);
		}

		// Get the type of meter and its state
		// Index: 1) enabled 2) meter 3) disabled
		function meter(index, offset) {
			var top = 5;
			if (offset)
				top += offset;
			if (self.type == RECORD_BUTTON) {
				if (index == 1)
					return "-169px " + top + "px";
				if (index == 2)
					return "-189px " + top + "px";
				if (index == 3)
					return "-249px " + top + "px";
			} else {
				if (index == 1)
					return "-269px " + top + "px";
				if (index == 2)
					return "-298px " + top + "px";
				if (index == 3)
					return "-327px " + top + "px";
			}
			alert("Meter not found: " + self.type + " " + index);
		}

		function silhouetteWidth() {
			if (self.type == RECORD_BUTTON) {
				return "20px";
			} else {
				return "29px";
			}
		}

		function mouseHandler(e) {
			var rightclick;
			if (!e)
				var e = window.event;
			if (e.which)
				rightclick = (e.which == 3);
			else if (e.button)
				rightclick = (e.button == 2);

			if (!rightclick) {
				if (self.active && self.onstop) {
					self.active = false;
					self.onstop();
				} else if (!self.active && self.onstart) {
					self.active = true;
					self.onstart();
				}
			}
		}

		function init() {
			var div = document.createElement("div");
			var elem = document.getElementById(buttonid);
			if (elem) {
				elem.appendChild(div);
			} else {
				alert('Could not find element on page named ' + buttonid);
			}

			self.guidiv = document.createElement("div");
			self.guidiv.style.width = '56px';
			self.guidiv.style.height = '63px';
			self.guidiv.style.cursor = 'pointer';
			self.guidiv.style.background = "url(" + url + ") no-repeat";
			self.guidiv.style.backgroundPosition = background(1);
			div.appendChild(self.guidiv);

			// margin auto doesn't work in IE quirks mode
			// http://stackoverflow.com/questions/816343/why-will-this-div-img-not-center-in-ie8
			// text-align is a hack to force it to work even if you forget the doctype.
			self.guidiv.style.textAlign = 'center';

			self.meterDiv = document.createElement("div");
			self.meterDiv.style.width = silhouetteWidth();
			self.meterDiv.style.height = '63px';
			self.meterDiv.style.margin = 'auto';
			self.meterDiv.style.cursor = 'pointer';
			self.meterDiv.style.position = 'relative';
			self.meterDiv.style.background = "url(" + url + ") no-repeat";
			self.meterDiv.style.backgroundPosition = meter(2);
			//self.meterDiv.style.zIndex = 8;
			self.guidiv.appendChild(self.meterDiv);

			self.coverDiv = document.createElement("div");
			self.coverDiv.style.width = silhouetteWidth();
			self.coverDiv.style.height = '63px';
			self.coverDiv.style.margin = 'auto';
			self.coverDiv.style.cursor = 'pointer';
			self.coverDiv.style.position = 'relative';
			self.coverDiv.style.background = "url(" + url + ") no-repeat";
			self.coverDiv.style.backgroundPosition = meter(1);
			//self.meterDiv.style.zIndex = 8;
			self.meterDiv.appendChild(self.coverDiv);

			self.active = false;
			self.guidiv.onmousedown = mouseHandler;
		}

		self.isActive = function() {
			return self.active;
		}

		self.setActivity = function(level) {
			self.guidiv.onmouseout = function() {
			};
			self.guidiv.onmouseover = function() {
			};
			self.guidiv.style.backgroundPosition = background(2);
			self.coverDiv.style.backgroundPosition = meter(1, 5);
			self.meterDiv.style.backgroundPosition = meter(2, 5);

			var totalHeight = 31;
			var maxHeight = 9;

			// When volume goes up, the black image loses height,
			// creating the perception of the colored one increasing.
			var height = (maxHeight + totalHeight - Math.floor(
				1.0 * level / 50/*--100--*/* totalHeight
			));
			self.coverDiv.style.height = height + "px";
		}

		self.setEnabled = function(enable) {
			var guidiv = self.guidiv;
			self.active = false;
			if (enable) {
				self.coverDiv.style.backgroundPosition = meter(1);
				self.meterDiv.style.backgroundPosition = meter(1);
				guidiv.style.backgroundPosition = background(1);
				guidiv.onmousedown = mouseHandler;
				guidiv.onmouseover = function() {
					guidiv.style.backgroundPosition = background(3);
				};
				guidiv.onmouseout = function() {
					guidiv.style.backgroundPosition = background(1);
				};
			} else {
				self.coverDiv.style.backgroundPosition = meter(3);
				self.meterDiv.style.backgroundPosition = meter(3);
				guidiv.style.backgroundPosition = background(1);
				guidiv.onmousedown = null;
				guidiv.onmouseout = function() {
				};
				guidiv.onmouseover = function() {
				};
			}
		}
	}
}

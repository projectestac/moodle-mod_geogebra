var adapter = {
	startTime : Math.floor(new Date().getTime() / 1000),

	applet : new Object(),

	properties : new Array(),

	propertyString : "",

	initialized: false,

	init : function() {
		if (!this.initialized) {
			tmp = this.propertyString.split('&');
			for (i in tmp) {
				tmppr = tmp[i].split('=');
				this.properties[tmppr[0]] = tmppr[1];
			}
			if (this.properties['duration'] === undefined) {
				this.properties['duration'] = 0;
			}
			if (this.properties['state'] != undefined) {
				console.log('Geogebra data loaded');
				this.applet.setBase64(unescape(this.properties['state']));
			}
			this.encodeProperties();
			this.initialized = true;
		}
	},

	doExit : function() {
		duration = Math.floor(new Date().getTime() / 1000) - this.startTime;
		this.properties['state'] = this.applet.getBase64();
		this.properties['grade'] = this.applet.getValue('grade');
		this.properties['duration'] = parseInt(this.properties['duration']) + duration;
		this.encodeProperties();
	},

	encodeProperties : function() {
		tmp = "";
		for ( var i in this.properties) {
			tmp += i + '=' + this.properties[i] + '&';
		}
		this.propertyString = tmp;
	}
}

function geogebra_addEvent(object, eventName, callback) {
	if (object.addEventListener) {
		object.addEventListener(eventName, callback, false);
	} else {
		object.attachEvent('on' + eventName, callback);
	}
}

function geogebra_submit_attempt(){
	var form = document.getElementById('geogebra_form');

    adapter.doExit();
    form.appletInformation.value = adapter.propertyString;
    form.submit();
}


function ggbOnInit() {
	var form = document.getElementById('geogebra_form');

	adapter.propertyString = form.prevAppletInformation.value;
	if (ggbApplet == undefined) {
		adapter.applet = document.ggbApplet;
	} else {
		adapter.applet = ggbApplet;
	}
	adapter.init();

	var save = document.getElementById('geogebra_form_save');
	geogebra_addEvent(save, 'click', function(e) {
		return geogebra_submit_attempt();
	});

	var submit = document.getElementById('geogebra_form_submit');
	geogebra_addEvent(submit, 'click', function(e) {
		form.f.value = 1;
		return geogebra_submit_attempt();
	});
}


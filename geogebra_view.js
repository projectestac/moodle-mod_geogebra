var adapter = {

    startTime: Math.floor(new Date().getTime() / 1000),
    applet: {},
    properties: [],
    propertyString: '',
    initialized: false,

    init: function () {
        if (!this.initialized) {
            var tmp = this.propertyString.split('&');
            for (var i in tmp) {
                var tmppr = tmp[i].split('=');
                this.properties[tmppr[0]] = tmppr[1];
            }
            if (this.properties.duration === undefined) {
                this.properties.duration = 0;
            }
            if (this.properties.state !== undefined) {
                console.log('Geogebra data loaded');
                document.ggbApplet.setBase64(unescape(this.properties.state));
            }
            this.encodeProperties();
            this.initialized = true;
        }
    },

    doExit: function () {
    	if (RT_GGBExitHook) {RT_GGBExitHook(); }
        var duration = Math.floor(new Date().getTime() / 1000) - this.startTime;
        this.properties.state = this.applet.getBase64();
        this.properties.grade = this.applet.getValue('grade');
        this.properties.duration = parseInt(this.properties.duration) + duration;
        this.encodeProperties();
    },

    encodeProperties: function () {
        var tmp = '';
        for (var i in this.properties) {
            tmp += i + '=' + this.properties[i] + '&';
        }
        this.propertyString = tmp;
    }

};

function geogebra_addEvent(object, eventName, callback) {
    if (object == null) {
        return;
    }
    if (object.addEventListener) {
        object.addEventListener(eventName, callback, false);
    } else {
        object.attachEvent('on' + eventName, callback);
    }
}

function geogebra_submit_attempt() {
    var form = document.getElementById('geogebra_form');

    adapter.doExit();
    form.appletInformation.value = adapter.propertyString;
    form.submit();
}

function init_ggb() {
    if (typeof ggbApplet == 'undefined') {
        var applet = document.ggbApplet;
    } else {
        var applet = ggbApplet;
    }

    // Modified: 20/10/2021
    // Use `document.ggbApplet` instead of this.applet
    // Retry until `document.ggbApplet` is created 
    if (!document.ggbApplet) {
        setTimeout(init_ggb, 1000);
        return;
    }

    var form = document.getElementById('geogebra_form');

    adapter.propertyString = form.prevAppletInformation.value;
    adapter.applet = applet;
    adapter.init();

    var save = document.getElementById('geogebra_form_save');
// here is where to saving starts from. They all end up in calling geogebra_submit-attempt
    geogebra_addEvent(save, 'click', function () {
        return geogebra_submit_attempt();
    });

    var submit = document.getElementById('geogebra_form_submit');

    geogebra_addEvent(submit, 'click', function () {
        form.f.value = 1;
        return geogebra_submit_attempt();
    });
}

geogebra_addEvent(window, 'load', function() {
    init_ggb();
});

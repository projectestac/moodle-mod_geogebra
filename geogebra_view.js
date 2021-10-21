var adapter = {

    startTime: Math.floor(new Date().getTime() / 1000),
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
            if (document.ggbApplet && this.properties.state !== undefined) {
                console.log('Geogebra data loaded');
                document.ggbApplet.setBase64(unescape(this.properties.state));
            }
            this.encodeProperties();
            this.initialized = true;
        }
    },

    doExit: function () {
        // Check if `document.ggbApplet` is already initialized
        if (document.ggbApplet) {
            var duration = Math.floor(new Date().getTime() / 1000) - this.startTime;
            this.properties.state = document.ggbApplet.getBase64();
            this.properties.grade = document.ggbApplet.getValue('grade');
            this.properties.duration = parseInt(this.properties.duration, 10) + duration;
            this.encodeProperties();
        }
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

geogebra_addEvent(window, 'load', function () {
    init_ggb();
});

// Modified: 20/10/2021
// Use `document.ggbApplet` instead of this.applet
function init_ggb() {

    // Retry until `document.ggbApplet` is created 
    if (!document.ggbApplet) {
        setTimeout(init_ggb, 1000);
        return;
    }

    var form = document.getElementById('geogebra_form');

    adapter.propertyString = form.prevAppletInformation.value;
    adapter.init();

    var save = document.getElementById('geogebra_form_save');

    geogebra_addEvent(save, 'click', function () {
        return geogebra_submit_attempt();
    });

    var submit = document.getElementById('geogebra_form_submit');

    geogebra_addEvent(submit, 'click', function () {
        form.f.value = 1;
        return geogebra_submit_attempt();
    });
}

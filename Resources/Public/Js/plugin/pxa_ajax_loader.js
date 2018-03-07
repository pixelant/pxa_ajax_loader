PxaAjaxLoader = (function () {
    /**
     * Simulate singleton
     */
    var _instance = null;

    /**
     * Init settings
     *
     * @param settings
     * @constructor
     */

    function PxaAjaxLoader(settings) {
        this.settings = settings;

        this.elements = [];
    }

    PxaAjaxLoader.prototype = {
        /**
         * Init events
         */
        init: function () {
            if (this.settings.querySelector !== undefined) {
                this.elements = document.querySelectorAll(this.settings.querySelector);
                this._loadAjaxContent();
            }
        },

        /**
         * Go through all elements and laod its ajax content
         *
         * @private
         */
        _loadAjaxContent: function () {
            for (var i in this.elements) if (this.elements.hasOwnProperty(i)) {
                var element = this.elements[i];

                this._sendAjax(
                    element.getAttribute('data-ajax-url'),
	                element,
                    function (element, responseText) {
                        if (typeof responseText === 'string') {
                            element.innerHTML = responseText;
                        }
                    }
                );
            }
        },

        /**
         * Send ajax request
         *
         * @param url
         * @param element
         * @param callback
         * @private
         */
        _sendAjax: function (url, element, callback) {
            var x = this._getXhr();

            x.open('GET', url, true);
            x.onreadystatechange = function () {
                if (x.readyState === 4 && x.status === 200) {
                    callback(element, x.responseText);
                }
            };
            x.send()
        },

        /**
         * Initialize xhr
         *
         * @returns object
         * @private
         */
        _getXhr: function () {
            if (typeof XMLHttpRequest !== 'undefined') {
                return new XMLHttpRequest();
            }

            var versions = [
                "MSXML2.XmlHttp.6.0",
                "MSXML2.XmlHttp.5.0",
                "MSXML2.XmlHttp.4.0",
                "MSXML2.XmlHttp.3.0",
                "MSXML2.XmlHttp.2.0",
                "Microsoft.XmlHttp"
            ];

            var xhr;

            for (var i = 0; i < versions.length; i++) {
                try {
                    xhr = new ActiveXObject(versions[i]);
                    break;
                } catch (e) {
                }
            }

            return xhr;
        }
    };

    /**
     * public method
     */
    return {
        init: function (settings) {
            if (_instance === null) {
                _instance = new PxaAjaxLoader(settings);
                _instance.init();
            }
        }
    }
})();
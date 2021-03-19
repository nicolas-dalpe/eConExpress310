export const init = () => {

    // Make sure Moodle loads our stuff.
    window.console.log('k1.js is loaded.');

    // Taken from /mod/hvp/library/js/h5p.js.
    var H5P = window.H5P = window.H5P || {};

    H5P.EventDispatcher.call(this);

    H5P.ED = {};

    H5P.ED.prototype = Object.create(H5P.EventDispatcher.prototype);
    H5P.ED.prototype.constructor = H5P.ED;

    window.console.log(H5P.ED);

    H5P.ED.on('buttonPressed', function (event) {
        console.log('Someone pressed a button!');
        console.log(event);
    });

    let buttonText = 'blabla';
    H5P.MyClass.trigger('buttonPressed', buttonText);
};

/**
 * @class
 * @augments H5P.EventDispatcher
 * @param {Object} displayOptions
 * @param {boolean} displayOptions.export Triggers the display of the 'Download' button
 * @param {boolean} displayOptions.copyright Triggers the display of the 'Copyright' button
 * @param {boolean} displayOptions.embed Triggers the display of the 'Embed' button
 * @param {boolean} displayOptions.icon Triggers the display of the 'H5P icon' link

 H5P.k1 = (function ($, EventDispatcher) {
    "use strict";

    function k1() {
      EventDispatcher.call(this);
    }

    k1.prototype = Object.create(EventDispatcher.prototype);
    k1.prototype.constructor = k1;

    return k1;

  })(H5P.jQuery, H5P.EventDispatcher);
 */
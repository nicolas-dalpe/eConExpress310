/** Allow console log method */
/* eslint no-console: 0 */

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
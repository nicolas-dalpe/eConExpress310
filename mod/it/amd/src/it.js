/* eslint-disable no-console */

import $ from 'jquery';

export const init = () => {

    // Make sure the H5P iframe is loaded.
    setTimeout(function() {a.start();}, 5000);
};

let a = {
    start () {

    // Make sure Moodle loads our stuff.
    window.console.log('it.js is loaded.');

    // Access iframeH5P
    const iframeH5P = document.getElementsByClassName('h5p-iframe')[0].contentWindow.H5P;

    // Access iframeVideo
    const iframeVideo = iframeH5P.instances[0].video;

    // init videoInterval
    let videoInterval;

    // Check Video status
    iframeVideo.on('stateChange', function (event) {
        switch (event.data) {
            case iframeH5P.Video.ENDED:
                window.console.log('Ended');

                // clear interval
                clearInterval(videoInterval);
            break;

            case iframeH5P.Video.PLAYING:
                window.console.log('Playing');

                // every second, update the current time
                videoInterval = setInterval(function() {
                    let currentTime = iframeVideo.getCurrentTime();

                    window.console.log(currentTime);

                    $("p#subtitle span").each(function() {
                        let timeMin = parseInt($(this).data('min'));
                        let timeMax = parseInt($(this).data('max'));
                        if (currentTime > timeMin && currentTime < timeMax) {
                            $(this).addClass('hightlighted');
                        } else {
                            $(this).removeClass('hightlighted');
                        }
                    });
                }, 100);
            break;

            case iframeH5P.Video.PAUSED:
                window.console.log('Paused');

                // clear interval
                clearInterval(videoInterval);
                break;

            /* case iframeH5P.Video.BUFFERING:
                window.console.log('Wait on your slow internet connection...');
            break; */
        }
    });
    }
};
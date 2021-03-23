export const init = () => {

	// Make sure Moodle loads our stuff.
	window.console.log('k1.js is loaded.');

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
					window.console.log(iframeVideo.getCurrentTime());
				}, 1000);
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
};
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Add JS to copy assessment link to the clipboard.
 *
 * @module     mod_ainst/clipboard
 * @package    mod_ainst
 * @copyright  2020 Knowledge One Inc. {@link http://knowledgeone.ca}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define(['jquery', 'core/str', 'core/notification'],
    function($, str, notification) {

    return {

        /**
         * Function being called by the js_call_amd()
         */
        init: function(msg_copied_success) {

            // Copy the Assignment index page URL.
            // MTHYMOOD-605 - As a teacher, I want a link to a course assignment list.
            $('.copytrigger').click(function() {

                // Create textare element object.
                const el = document.createElement('textarea');

                // Set the textarea value.
                el.value = $("#copyme").val();

                // Add some style to hide the textarea.
                el.setAttribute('readonly', '');
                el.style.position = 'absolute';
                el.style.left = '-9999px';
                document.body.appendChild(el);

                // Select the textarea value.
                el.select();

                // Copy to clipboard.
                document.execCommand('copy');

                // Remove the textarea from the document.
                document.body.removeChild(el);

                // Display the Notification.
                notification.addNotification({message: msg_copied_success, type: "success"});

                // Hide Notification after x seconds.
                hideNotificationQueue("#user-notifications .alert-success");
            });

            /**
             * hideNotificationQueue()
             * Hide notification after x milliseconds
             * str e The element selector to hide
             * int t The timeout length in millisecond
             */
            function hideNotificationQueue(e, t = 10000) {

                // Make sure we have a timeout value.
                t = parseInt(t, 10);
                if (isNaN(t)) {
                    t = 5000;
                }

                // Hide notification after t milliseconds
                setTimeout(function() {hideNotification(e, t);}, t);
            }

             /**
             * hideNotification()
             * Hide notification immediately
             * str e The element selector to hide
             */
            function hideNotification(e) {
                // Cache element
                var el = $(e);

                // Make sure we have an element to hide.
                if (el.length) {

                    // Hide and remove the notification.
                    el.hide('slow', function() {el.remove();});
                }
            }
        } // init()
    };
});

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
 * Get the status of the hybrid question within an attempt.
 *
 * @module     local_qrsub/attempt_status
 * @package    local_qrsub
 * @copyright  2021 Knowledge One Inc. {@link http://knowledgeone.ca}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

 define(['jquery', 'core/str', 'core/ajax'],
    function($, str, ajax) {

    return {

        /**
         * Function being called by the js_call_amd()
         * @param str type The item to reorder. assignment or section
         */
        init: function(step, attemptid, cmid) {

            if (step == 'FINISHED') {
                setInterval(function () { get_attempt_number(attemptid); }, 1000);
            } else {
                setInterval(function () { get_attempt_status(attemptid); }, 1000);
            }

            function get_attempt_number(attemptid) {
                console.log('get_attempt_number');
                // Send the new order to the server.
                var promises = ajax.call([{
                    methodname: 'local_qrsub_attempt_number',
                    args: { 'attemptid': attemptid }
                }]);

                // Process the server response.
                promises[0].done(function (r) {
                    if (r.number == 0) {
                        document.location.href = '/mod/quiz/view.php?id=' + cmid.id;
                    }
                }).fail(function () {
                    console.log('get_attempt_number() fail');
                });
            }

            function get_attempt_status(attemptid) {

                console.log('get_attempt_status');

                // Send the new order to the server.
                var promises = ajax.call([{
                    methodname: 'local_qrsub_attempt_status',
                    args: { 'attemptid': attemptid }
                }]);

                // Process the server response.
                promises[0].done(function (r) {
                    if (r.status == 'finished') {
                        document.location.href = '/mod/quiz/view.php?id=' + cmid.id;
                    }
                    $(".hybrid_status").html(r.status);
                }).fail(function () {
                    console.log('fail');
                });
            }


        } // init()
    };
});

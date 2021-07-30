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
 * JavaScript to make drag-drop into text questions work.
 *
 * Some vocabulary to help understand this code:
 *
 * The question text contains 'drops' - blanks into which the 'drags', the missing
 * words, can be put.
 *
 * The thing that can be moved into the drops are called 'drags'. There may be
 * multiple copies of the 'same' drag which does not really cause problems.
 * Each drag has a 'choice' number which is the value set on the drop's hidden
 * input when this drag is placed in a drop.
 *
 * These may be in separate 'groups', distinguished by colour.
 * Things can only interact with other things in the same group.
 * The groups are numbered from 1.
 *
 * The place where a given drag started from is called its 'home'.
 *
 * @module     qtype_hybrid/helper
 * @package    qtype_hybrid
 * @copyright  2021 Knowledge One Inc.
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since      3.9
 */
/* eslint-disable no-console */

import $ from 'jquery';

export const init = (answer) => {

    // Cache the answer textarea.
    let studentanswered_txt = $('.studentanswered_txt');

    // Cache the answer checkbox.
    let studentanswered_chk = $('#studentanswered_chk');

    // If the textarea is filled-up, check the answer box.
    checkifanswered(studentanswered_txt, studentanswered_chk, answer);

    // Toggle the text in the textarea.
    studentanswered_chk.on('change', function() {
        togglestudentanswer(studentanswered_txt, studentanswered_chk, answer);
    });
};

/**
 * This answer will check the checkbox if the textarea contains the answer.
 * This check happen on page load.
 *
 * @param {jQuery object} t The textarea containing the answer.
 * @param {jQuery object} c The checkbox to mark this question as answered.
 * @param {str} a The answer passed from Moodle.
 */
const checkifanswered = (t, c, a) => {

    // If the textarea contains the answer, check the checkbox.
    if (t.val() == a) {
        c.prop('checked', true);
    } else {
        c.prop('checked', false);
    }
};

/**
 * This answer will toggle the answer in the textarea.
 *
 * @param {jQuery object} t The textarea containing the answer.
 * @param {jQuery object} c The checkbox to mark this question as answered.
 * @param {str} a The answer passed from Moodle.
 */
const togglestudentanswer = (t, c, a) => {

    // Set the answer in the textarea if the student check the answer box.
    if (c.is(':checked')) {
        t.val(a);
    } else {
        // Leave the textarea empty if the checkbox is unchecked.
        t.val('');
    }
};
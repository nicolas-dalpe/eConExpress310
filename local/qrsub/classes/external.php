<?php
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
 * Quiz external API
 *
 * @package    mod_quiz
 * @category   external
 * @copyright  2016 Juan Leyva <juan@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since      Moodle 3.1
 */

defined('MOODLE_INTERNAL') || die;

require_once($CFG->libdir . '/externallib.php');
require_once($CFG->dirroot . '/mod/quiz/attemptlib.php');
require_once($CFG->dirroot . '/mod/quiz/accessmanager.php');

use local_qrsub\local\qrsub_attempt_info_block;

/**
 * Quiz external functions
 *
 * @package    mod_quiz
 * @category   external
 * @copyright  2016 Juan Leyva <juan@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since      Moodle 3.1
 */
class local_qrsub_external extends external_api {

    /**
     * Describes the parameters for attempt_status.
     *
     * @return external_function_parameters
     * @since Moodle 3.1
     */
    public static function attempt_status_parameters() {
        return new external_function_parameters (
            array(
                'attemptid' => new external_value(PARAM_INT, 'attempt instance id'),
            )
        );
    }

    /**
     * Trigger the course module viewed event and update the module completion status.
     *
     * @param int $quizid quiz instance id
     * @return array of warnings and status result
     * @since Moodle 3.1
     * @throws moodle_exception
     */
    public static function attempt_status($attemptid) {
        global $DB;
        $params = self::validate_parameters(self::attempt_status_parameters(), array('attemptid' => $attemptid));

        $attemptobj = quiz_attempt::create($attemptid);

        $attempt = $DB->get_record('quiz_attempts', array('id' => $attemptid));
        if ($attempt->state == 'finished') {
            return array('status' => 'finished');
        }

        $hybridinfo = new qrsub_attempt_info_block($attemptobj);
        $hybrids = $hybridinfo->get_questions();

        $status = '';

        // Display the question status only if we have hybrid question.
        if (count($hybrids) > 0) {

            // Display the question.
            foreach ($hybrids as $hybrid) {
                $status .= html_writer::tag(
                    'div',
                    $hybrid['name'] . ' ' . $hybrid['complete']->get_identifier(),
                    array('class' => $hybrid['complete_css_class'])
                );
            }
        }

        return array('status' => $status);
    }

    /**
     * Describes the view_quiz return value.
     *
     * @return external_single_structure
     * @since Moodle 3.1
     */
    public static function attempt_status_returns() {
        return new external_single_structure(
            array(
                'status' => new external_value(PARAM_RAW, 'Attempt status')
            )
        );
    }

    /**
     * Describes the parameters for attempt_number.
     *
     * @return external_function_parameters
     * @since Moodle 3.1
     */
    public static function attempt_number_parameters() {
        return new external_function_parameters(
            array(
                'attemptid' => new external_value(PARAM_INT, 'attempt instance id'),
            )
        );
    }


    /**
     * Trigger the course module viewed event and update the module completion status.
     *
     * @param int $quizid quiz instance id
     * @return array of warnings and status result
     * @since Moodle 3.1
     * @throws moodle_exception
     */
    public static function attempt_number($attemptid) {
        global $DB;

        $params = self::validate_parameters(self::attempt_status_parameters(), array('attemptid' => $attemptid));

        $attempt = $DB->get_record('quiz_attempts', array('id' => $attemptid));
        if (!$attempt) {
            $number = 0;
        } else {
            $number = $attempt->attempt;
        }
        return array('number' => $number);
    }

    /**
     * Describes the view_quiz return value.
     *
     * @return external_single_structure
     * @since Moodle 3.1
     */
    public static function attempt_number_returns() {
        return new external_single_structure(
            array(
                'number' => new external_value(PARAM_INT, 'Attempt status')
            )
        );
    }
}

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
 * Implementaton of the quizaccess_qrsubaccess plugin.
 *
 * @package   quizaccess_qrsubaccess
 * @copyright 2011 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/quiz/accessrule/accessrulebase.php');

use local_qrsub\local\qrsub;

/**
 * A rule requiring the student to promise not to cheat.
 *
 * @copyright  2011 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class quizaccess_qrsubaccess extends quiz_access_rule_base {

    /**
     * It is possible for one rule to override other rules.
     *
     * The aim is that third-party rules should be able to replace sandard rules
     * if they want. See, for example MDL-13592.
     *
     * @return array plugin names of other rules that this one replaces.
     *      For example array('ipaddress', 'password').
     */
    public function get_superceded_rules() {
        global $DB, $USER;

        // Containes the rules to supercede.
        $supercededrules = array();

        //////////////////////////////////
        // QRMOOD-33 -As a student, I don't want quiz timer when uploading my files.
        //
        // if there is no attempt, show the timer
        // if there is an attempt started but not finished, show the timer
        // if there is a finished attempt and hybrid question, bypass the timer

        // Get the first attempt record.
        $attempt = $DB->get_record(
            'quiz_attempts',
            array(
                'quiz' => $this->quiz->id,
                'userid' => $USER->id,
                'attempt' => '1',
                'state' => 'finished'
            )
        );

        // If there are hybris question in the quiz, add the
        // timelimit supercede rule.
        if ($attempt) {

            // Create the quiz_attempt object.
            $qrsub = new qrsub();
            $hashybrid = $qrsub->has_hybrid_question(
                quiz_attempt::create($attempt->id)
            );
            // If the quiz contains hybrid question, supercede the timer.
            if ($hashybrid) {
                $supercededrules[] = 'timelimit';
            }
        }
        // QRMOOD-33

        return $supercededrules;
    }

    public static function make(quiz $quizobj, $timenow, $canignoretimelimits) {

        if (empty($quizobj->get_quiz()->qrsubaccessrequired)) {
            return null;
        }

        return new self($quizobj, $timenow);
    }

    /**
     * Adds the select menu in the quiz settings.
     */
    public static function add_settings_form_fields(
            mod_quiz_mod_form $quizform, MoodleQuickForm $mform) {
        $mform->addElement('select', 'qrsubaccessrequired',
                get_string('qrsubaccessrequired', 'quizaccess_qrsubaccess'),
                array(
                    0 => get_string('notrequired', 'quizaccess_qrsubaccess'),
                    1 => get_string('qrsubaccessrequiredoption', 'quizaccess_qrsubaccess'),
                ));
        $mform->addHelpButton('qrsubaccessrequired',
                'qrsubaccessrequired', 'quizaccess_qrsubaccess');
    }

    /**
     * Saves the settings in the quizaccess_qrsubaccess table.
     */
    public static function save_settings($quiz) {
        global $DB;
        if (empty($quiz->qrsubaccessrequired)) {
            $DB->delete_records('quizaccess_qrsubaccess', array('quizid' => $quiz->id));
        } else {
            if (!$DB->record_exists('quizaccess_qrsubaccess', array('quizid' => $quiz->id))) {
                $record = new stdClass();
                $record->quizid = $quiz->id;
                $record->qrsubaccessrequired = 1;
                $DB->insert_record('quizaccess_qrsubaccess', $record);
            }
        }
    }

    public static function delete_settings($quiz) {
        global $DB;
        $DB->delete_records('quizaccess_qrsubaccess', array('quizid' => $quiz->id));
    }

    public static function get_settings_sql($quizid) {
        return array(
            'qrsubaccessrequired',
            'LEFT JOIN {quizaccess_qrsubaccess} qrsubaccess ON qrsubaccess.quizid = quiz.id',
            array());
    }
}

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
 * Defines the renderer for the quiz module.
 *
 * @package   mod_quiz
 * @copyright 2011 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/quiz/renderer.php');

// Import the hybrid question info block.
use local_qrsub\local\qrsub_attempt_info_block;

// Library for QRSub module.
use local_qrsub\local\qrsub;

/**
 * The renderer for the quiz module.
 *
 * @copyright  2011 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class theme_boost_mod_quiz_renderer extends \mod_quiz_renderer {

    /**
     * Generates the view page.
     *
     * @param int $course The id of the course
     * @param array $quiz Array conting quiz data
     * @param int $cm Course Module ID
     * @param int $context The page context ID
     * @param array $infomessages information about this quiz
     * @param mod_quiz_view_object $viewobj
     * @param string $buttontext text for the start/continue attempt button, if
     *      it should be shown.
     * @param array $infomessages further information about why the student cannot
     *      attempt this quiz now, if appicable this quiz
     */
    public function view_page($course, $quiz, $cm, $context, $viewobj) {
        global $COURSE, $PAGE;

        $output = '';
        $output .= $this->view_information($quiz, $cm, $context, $viewobj->infomessages);
        $output .= $this->view_table($quiz, $context, $viewobj);

        // We need at least one attempt object.
        if (isset($viewobj->attemptobjs[0])) {

            // $quizattemptobj = $viewobj->attemptobjs[0];

            // If the first attempt is completed.

            // Render the QR Code if there is an hybrid que in the attempt.
            $qrsub = new qrsub();
            $hashybrid = $qrsub->has_hybrid_question($viewobj->attemptobjs[0]);
            if ($hashybrid) {
                $a = quiz_attempt::IN_PROGRESS;
                $b = quiz_attempt::FINISHED;
                if (
                    ($viewobj->attemptobjs[0]->get_attempt_number() == 1 && $viewobj->attemptobjs[0]->get_state() == quiz_attempt::FINISHED) ||
                    ($viewobj->attemptobjs[0]->get_attempt_number() == 2 && $viewobj->attemptobjs[0]->get_state() == quiz_attempt::IN_PROGRESS)

                ) {
                    $output .= $qrsub->get_qrcode($cm);
                }
            }
        }

        $output .= $this->view_result_info($quiz, $context, $cm, $viewobj);
        $output .= $this->box($this->view_page_buttons($viewobj), 'quizattempt');
        return $output;
    }

    /**
     * Generate a brief textual desciption of the current state of an attempt.
     * @param quiz_attempt $attemptobj the attempt
     * @param int $timenow the time to use as 'now'.
     * @return string the appropriate lang string to describe the state.
     */
    public function attempt_state($attemptobj) {
        global $DB;
        switch ($attemptobj->get_state()) {
            case quiz_attempt::IN_PROGRESS:
                // Contains the question and their completion status.
                $questionstatus = $status = '';

                // Load the questions in the attempt object.
                $attemptobj->load_questions();

                // Get the hybrid question and their status.
                $hybridinfo = new qrsub_attempt_info_block($attemptobj);
                $hybrids = $hybridinfo->get_questions();

                // Display the question status only if we have hybrid question.
                if (count($hybrids) > 0) {

                    $this->page->requires->js_call_amd(
                        'local_qrsub/attempt_status',
                        'init',
                        array('IN_PROGRESS', $attemptobj->get_attemptid(), $attemptobj->get_cm())
                    );

                    $status = new lang_string('hybrid_upload', 'local_qrsub');

                    // Display the question.
                    foreach ($hybrids as $hybrid) {
                        $questionstatus .= html_writer::tag(
                            'div',
                            $hybrid['name'] . ' ' . $hybrid['complete']->get_identifier(),
                            array('class' => $hybrid['complete_css_class'])
                        );
                    }

                    $status .= html_writer::tag(
                        'div',
                        $questionstatus,
                        array('class' => 'hybrid_status')
                    );
                } else {
                    $status .= new lang_string('stateinprogress', 'quiz');
                }

                return $status;

            case quiz_attempt::OVERDUE:
                return get_string('stateoverdue', 'quiz') . html_writer::tag(
                    'span',
                    get_string(
                        'stateoverduedetails',
                        'quiz',
                        userdate($attemptobj->get_due_date())
                    ),
                    array('class' => 'statedetails')
                );

            case quiz_attempt::FINISHED:

                $status = '';

                // Load the questions in the attempt object.
                $attemptobj->load_questions();

                // Get the hybrid question and their status.
                $hybridinfo = new qrsub_attempt_info_block($attemptobj);
                $hybrids = $hybridinfo->get_questions();

                if ($attemptobj->get_attempt_number() == 1 && count($hybrids) > 0) {

                    $this->page->requires->js_call_amd(
                        'local_qrsub/attempt_status',
                        'init',
                        array('FINISHED', $attemptobj->get_attemptid(), $attemptobj->get_cm())
                    );

                    $status .= get_string('non_hybrid_finished', 'local_qrsub') . html_writer::tag(
                        'span',
                        get_string(
                            'statefinisheddetails',
                            'quiz',
                            userdate($attemptobj->get_submitted_date())
                        ),
                        array('class' => 'statedetails')
                    );
                } else {
                    $status .= get_string('statefinished', 'quiz') . html_writer::tag(
                        'span',
                        get_string(
                            'statefinisheddetails',
                            'quiz',
                            userdate($attemptobj->get_submitted_date())
                        ),
                        array('class' => 'statedetails')
                    );
                }

                return $status;

            case quiz_attempt::ABANDONED:
                return get_string('stateabandoned', 'quiz');
        }
    }
}

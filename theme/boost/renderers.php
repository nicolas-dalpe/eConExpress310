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

use assignsubmission_qrcodea\local\qrcodegenerator;

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

        if ($viewobj->numattempts >= 1) {
            $qrcodeoptions = new stdClass();

            // Course id.
            $qrcodeoptions->courseid = $COURSE->id;

            // Get the assignment cmid to link to.
            $qrcodeoptions->assignmentid = $PAGE->cm->id;

            // Build the QR Code.
            $qrcodegenerator = new qrcodegenerator($qrcodeoptions);

            // Generate the QR Code URL.
            $url = new moodle_url('/local/qrsub/startqrsub.php', array(
                'cmid' => $cm->id
            ));

            // Output the QR Code image.
            $qrcode = $qrcodegenerator->output_image($url);

            // Prepare the data for the template.
            $tpldata = new stdClass();
            $tpldata->legend = new lang_string('instruction_qrcode', 'assignsubmission_qrcodea');

            // Set the QR Code format.
            if ($qrcodegenerator->get_format() == 1) {
                $tpldata->qrcodesvg = $qrcode;
            } else {
                $tpldata->qrcodepng = $qrcode;
            }

            // Render the QR Code.
            $renderable = new \assignsubmission_qrcodea\output\qrcode_page($tpldata);
            $qrcodea_renderer = $PAGE->get_renderer('assignsubmission_qrcodea');
            $output .= $qrcodea_renderer->render($renderable);
        }

        $output .= $this->view_result_info($quiz, $context, $cm, $viewobj);
        $output .= $this->box($this->view_page_buttons($viewobj), 'quizattempt');
        return $output;
    }
}

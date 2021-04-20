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
 * Assessment instruction.
 * Display a list of assessments in the requested course.
 *
 * @package     mod_ainst
 * @copyright   2020 Knowledge One Inc. <knowledgeone.ca>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__.'/../../config.php');

//require_once(__DIR__.'/lib.php');

// Get the course id.
$id = optional_param('id', false, PARAM_INT);

// Get the course shortname.
$courseshortname = optional_param('course', false, PARAM_RAW);

// Global assignment functions.
$assignment = new \mod_ainst\local\assignment();

// Get the course object.
if (is_int($id)) {
    $course = get_course($id);
} else {
    $course = $assignment->get_course_from_shortname($courseshortname);
}

require_course_login($course);

$coursecontext = context_course::instance($course->id);

$PAGE->set_url('/mod/ainst/index.php', array('id' => $id));

// Set the browser window title.
$PAGE->set_title(format_string($course->fullname));

// Set page title.
$PAGE->set_heading(get_string('pt_Assignments_Overview', 'ainst'));
$PAGE->set_context($coursecontext);

// Set the page to use the course layout.
$PAGE->set_pagelayout('course');

// Add the clipboard manager.
// MTHYMOOD-605 - As a teacher, I want a link to a course assignment list.
$PAGE->requires->js_call_amd('mod_ainst/mod_ainst_clipboard', 'init', array(
    get_string('copy_success', 'ainst')
));

// Trigger instances list viewed event.
$event = \mod_ainst\event\course_module_instance_list_viewed::create(
    array('context' => $coursecontext)
);
$event->add_record_snapshot('course', $course);
$event->trigger();

// Set breadcrumb navigation.
$PAGE->navbar->add(get_string('pt_Assignments_Overview', 'ainst'));

// Get the plugin renderer mod/ainst/classes/output/renderer.php.
$output = $PAGE->get_renderer('mod_ainst');

echo $OUTPUT->header();

// Render the Instruction and sections.
$renderable = new \mod_ainst\output\index_page($course);

echo $output->render($renderable);

echo $OUTPUT->footer();

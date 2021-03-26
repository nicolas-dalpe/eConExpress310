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
 * Page use to reorder assessments of a given course.
 *
 * @package     mod_ainst
 * @copyright   2020 Knowledge One Inc. <knowledgeone.ca>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__.'/../../config.php');

require_once(__DIR__.'/lib.php');

// Get the course id.
$id = required_param('id', PARAM_INT);

// Get the course object from the course id.
$course = get_course($id);

// Checks that the current user is logged in and has the required privileges.
require_login($course);

// Set page URL.
$PAGE->set_url('/mod/ainst/index.php', array('id' => $id));

// Set the browser window title.
$PAGE->set_title(format_string($course->fullname));

// Set page heading.
$PAGE->set_heading(get_string('pt_Assignments_Reorder', 'ainst'));

// Set page context.
$PAGE->set_context(context_course::instance($course->id));

// Set the page to use the course layout.
$PAGE->set_pagelayout('course');

// Disable the original breadcrumb navigation.
$PAGE->navbar->ignore_active();

// Add course to the breadcrumb.
$PAGE->navbar->add(
    $course->shortname,
    new moodle_url('/course/view.php', array('id' => $course->id))
);

// Add Assignments Overview to the breadcrumb.
$PAGE->navbar->add(
    get_string('pt_Assignments_Overview', 'ainst'),
    new moodle_url('/mod/ainst/index.php', array('id' => $course->id))
);

// Add current page to the breadcrumb.
$PAGE->navbar->add(get_string('pt_Assignments_Reorder', 'ainst'));

// Add the assignment reorder amd module.
$PAGE->requires->js_call_amd('mod_ainst/mod_ainst_reorder', 'init', array('assignment'));

// Get the plugin's renderer mod/ainst/classes/output/renderer.php.
$output = $PAGE->get_renderer('mod_ainst');

echo $OUTPUT->header();

// Render the list of assignment to reorder.
$renderable = new \mod_ainst\output\reorderassignment_page($course);

echo $output->render($renderable);

echo $OUTPUT->footer();

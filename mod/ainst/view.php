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
 *
 * @package    mod_ainst
 * @copyright  2020 Knowledge One Inc.  {@link http://knowledgeone.ca}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');

// Course_module ID.
$id = optional_param('id', 0, PARAM_INT);

if ($id) {
    $cm             = get_coursemodule_from_id('ainst', $id, 0, false, MUST_EXIST);
    $course         = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
    $moduleinstance = $DB->get_record('ainst', array('id' => $cm->instance), '*', MUST_EXIST);
} else if ($a) {
    $moduleinstance = $DB->get_record('ainst', array('id' => $n), '*', MUST_EXIST);
    $course         = $DB->get_record('course', array('id' => $moduleinstance->course), '*', MUST_EXIST);
    $cm             = get_coursemodule_from_instance('ainst', $moduleinstance->id, $course->id, false, MUST_EXIST);
} else {
    print_error(get_string('missingidandcmid', 'mod_ainst'));
}

require_login($course, true, $cm);

$modulecontext = context_module::instance($cm->id);

$PAGE->set_context($modulecontext);
$PAGE->set_url('/mod/ainst/view.php', array('id' => $cm->id));

// Set the browser window title.
$PAGE->set_title(format_string($moduleinstance->name));

// Set page title.
$PAGE->set_heading(format_string($course->fullname));

// Set the page to use the course layout.
$PAGE->set_pagelayout('course');

// Force the edit settings menu to be displayed even if $PAGE->navbar->ignore_active(); is called below.
$PAGE->force_settings_menu(true);

// Completion and trigger events.
assignment_view($moduleinstance, $course, $cm, $modulecontext);

// Disable the original breadcrumb navigation.
$PAGE->navbar->ignore_active();

// Add course to the breadcrumb.
$PAGE->navbar->add(
    $course->shortname,
    new moodle_url('/course/view.php', array('id' => $cm->course))
);

// Add Assignments Overview to the breadcrumb.
$PAGE->navbar->add(
    get_string('pt_Assignments_Overview', 'ainst'),
    new moodle_url('/mod/ainst/index.php', array('id' => $cm->course))
);

// Add current page to the breadcrumb.
$PAGE->navbar->add($cm->name);

// Use the assignment renderer.
$output = $PAGE->get_renderer('mod_ainst');

echo $output->header();

// Render the assignment and sections.
$renderable = new \mod_ainst\output\view_page($cm);

echo $output->render($renderable);

echo $output->footer();

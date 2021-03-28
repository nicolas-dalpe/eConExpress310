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
 * Reorder assessment section page.
 *
 * @package     mod_ainst
 * @copyright   2020 Knowledge One Inc. <knowledgeone.ca>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__.'/../../config.php');

require_once(__DIR__.'/lib.php');

// Get the course module id.
$cmid = required_param('id', PARAM_INT);

if ($cmid) {
    $cm             = get_coursemodule_from_id('ainst', $cmid, 0, false, MUST_EXIST);
    $course         = get_course($cm->course);
    $moduleinstance = $DB->get_record('ainst', array('id' => $cm->instance), '*', MUST_EXIST);
} else {
    print_error(get_string('missingidandcmid', 'mod_ainst'));
}

// Checks that the current user is logged in and has the required privileges.
require_login($course, true, $cm);

$modulecontext = context_module::instance($cm->id);

// Set page URL.
$PAGE->set_url('/mod/ainst/reorder_section.php', array('id' => $cmid));

// Set the browser window title.
$PAGE->set_title(format_string($course->fullname));

// Set page title.
$PAGE->set_heading(get_string('pt_Section_Reorder', 'ainst'));

// Set page context.
$PAGE->set_context($modulecontext);

// Set the page to use the course layout.
$PAGE->set_pagelayout('course');

// Force the edit settings menu to be displayed even if $PAGE->navbar->ignore_active(); is called below.
$PAGE->force_settings_menu(true);

// Set breadcrumb navigation.
$PAGE->navbar->ignore_active();

// Add course to breadcrumb.
$PAGE->navbar->add(
    $course->shortname,
    new moodle_url('/course/view.php', array('id' => $course->id))
);

// Add Assignment overview page to breadcrumb.
$PAGE->navbar->add(
    get_string('pt_Assignments_Overview', 'ainst'),
    new moodle_url('/mod/ainst/index.php', array('id' => $course->id))
);

// Add assignment page to breadcrumb.
$PAGE->navbar->add(
    $cm->name,
    new moodle_url('/mod/ainst/view.php', array('id' => $cmid))
);

$PAGE->navbar->add(get_string('pt_Section_Reorder', 'ainst'));

// Add the assignment reorder amd module.
$PAGE->requires->js_call_amd('mod_ainst/mod_ainst_reorder', 'init', array('section'));

// Get the plugin renderer mod/ainst/classes/output/renderer.php.
$output = $PAGE->get_renderer('mod_ainst');

echo $OUTPUT->header();

// Render the Instruction and sections.
$renderable = new \mod_ainst\output\reordersection_page($cm);

echo $output->render($renderable);

echo $OUTPUT->footer();

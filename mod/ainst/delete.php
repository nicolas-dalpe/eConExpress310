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
$id = optional_param('delete', 0, PARAM_INT);

$confirm = optional_param('confirm', 0, PARAM_BOOL);

if ($id) {
    $cm             = get_coursemodule_from_id('ainst', $id, 0, false, MUST_EXIST);
    $course         = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
    $moduleinstance = $DB->get_record('ainst', array('id' => $cm->instance), '*', MUST_EXIST);
} else {
    print_error(get_string('missingidandcmid', 'mod_ainst'));
}

require_login($course, true, $cm);
$modulecontext = context_module::instance($cm->id);

$PAGE->set_context($modulecontext);
$PAGE->set_url('/mod/ainst/delete.php', array('id' => $cm->id));

// Set the browser window title.
$PAGE->set_title(format_string($moduleinstance->name));

// Set page title.
$PAGE->set_heading(format_string($course->fullname));

// Set the page to use the course layout.
$PAGE->set_pagelayout('admin');

// Force the edit settings menu to be displayed even if $PAGE->navbar->ignore_active(); is called below.
$PAGE->force_settings_menu(true);

// Set breadcrumb navigation.
$PAGE->navbar->ignore_active();
$PAGE->navbar->add(
    $course->shortname,
    new moodle_url('/course/view.php', array('id' => $cm->course))
);
$PAGE->navbar->add(
    get_string('pt_Assignments_Overview', 'ainst'),
    new moodle_url('/mod/ainst/index.php', array('id' => $cm->course))
);
$PAGE->navbar->add($cm->name);

// The URL to go to after the delete operation is done.
$return = new moodle_url('/mod/ainst/index.php', array('id' => $cm->course));

if (!empty($id)) {
    $course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);

    require_login($course, false, $cm);
    $modcontext = context_module::instance($cm->id);
    require_capability('moodle/course:manageactivities', $modcontext);


    if (!$confirm or !confirm_sesskey()) {
        $fullmodulename = new \lang_string('modulename', $cm->modname, null, 'en');

        $optionsyes = array('confirm' => 1, 'delete' => $cm->id, 'sesskey' => sesskey());

        $strdeletecheck = get_string('deletecheck', '', $fullmodulename);
        $strparams = (object)array('type' => $fullmodulename, 'name' => $cm->name);
        $strdeletechecktypename = get_string('deletechecktypename', '', $strparams);

        $PAGE->set_pagetype('mod-' . $cm->modname . '-delete');
        $PAGE->set_pagelayout('course');
        $PAGE->set_title($strdeletecheck);
        $PAGE->set_heading($course->fullname);
        $PAGE->navbar->add($strdeletecheck);

        echo $OUTPUT->header();
        echo $OUTPUT->box_start('noticebox');
        $formcontinue = new single_button(
            new moodle_url("$CFG->wwwroot/mod/ainst/delete.php", $optionsyes),
            get_string('yes')
        );
        $formcancel = new single_button($return, get_string('no'), 'get');
        echo $OUTPUT->confirm($strdeletechecktypename, $formcontinue, $formcancel);
        echo $OUTPUT->box_end();
        echo $OUTPUT->footer();

        exit;
    }

    // Delete the module.
    course_delete_module($cm->id);

    redirect($return);
}
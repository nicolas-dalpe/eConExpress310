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

// Get the course id.
$id = optional_param('id', false, PARAM_INT);
$redirect = optional_param('redirect', false, PARAM_TEXT);

// Get the course object.
$course = get_course($id);

require_course_login($course);

$coursecontext = context_course::instance($course->id);

$PAGE->set_url('/local/loginas/index.php', array('id' => $id));

// Set the browser window title.
$PAGE->set_title(get_string('title', 'local_loginas'));

// Set page title.
$PAGE->set_heading(get_string('title', 'local_loginas'));
$PAGE->set_context($coursecontext);

// Set the page to use the course layout.
// $PAGE->set_pagelayout('course');

// Trigger instances list viewed event.
// $event = \mod_ainst\event\course_module_instance_list_viewed::create(
//     array('context' => $coursecontext)
// );
// $event->add_record_snapshot('course', $course);
// $event->trigger();

// Set breadcrumb navigation.
// $PAGE->navbar->add(get_string('pt_Assignments_Overview', 'ainst'));

// Get the plugin renderer mod/ainst/classes/output/renderer.php.
// $output = $PAGE->get_renderer('mod_ainst');

echo $OUTPUT->header();

$select = $DB->sql_like('email', ':email', false);
$params = ['email' => 'moodle_test_%'];

$users = $DB->get_records_select('user', $select, $params, 'username', 'id, username');

foreach ($users as $id => $user) {

    // Only use the user who are enrolled in the course.
    if (is_enrolled($coursecontext, $id, '', false)) {
        $url = new moodle_url(
            '/course/loginas.php',
            ['id' => $course->id, 'user' => $user->id, 'sesskey' => sesskey()] // , 'redirect' => $redirect
            // ['id' => $course->id, 'user' => $user->id, 'sesskey' => sesskey(), 'redirect' => $redirect]
        );
        $html = '<p>';
        $html .= new lang_string('login_as', 'local_loginas');
        $html .= '<a href="'.$url->out().'">'.$user->username.'</a>';

        // Display the user role in the course.
        if ($roles = get_user_roles($coursecontext, $user->id)) {
            $strrole = [];
            foreach ($roles as $role) {
                $strrole[] = $role->shortname;
            }
            $html .= new lang_string('role_in_this_course', 'local_loginas', implode(', ', $strrole));
        }
        $html .= '</p>';
        echo $html;
    }
}

// Render the Instruction and sections.
// $renderable = new \mod_ainst\output\index_page($course);

// echo $output->render($renderable);

echo $OUTPUT->footer();

<?php
// This file is part of Moodle - https://moodle.org/
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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Prints an instance of mod_it.
 *
 * @package     mod_it
 * @copyright   2021 Knowledge One <nicolas.dalpe@knowledgeone.ca>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__.'/../../config.php');
require_once(__DIR__.'/lib.php');

// Course module id.
$id = optional_param('id', 0, PARAM_INT);

// Activity instance id.
// $i = optional_param('i', 0, PARAM_INT);

if ($id) {
    $cm = get_coursemodule_from_id('it', $id, 0, false, MUST_EXIST);
    $course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
    $moduleinstance = $DB->get_record('it', array('id' => $cm->instance), '*', MUST_EXIST);
} else if ($i) {
    $moduleinstance = $DB->get_record('it', array('id' => $n), '*', MUST_EXIST);
    $course = $DB->get_record('course', array('id' => $moduleinstance->course), '*', MUST_EXIST);
    $cm = get_coursemodule_from_instance('it', $moduleinstance->id, $course->id, false, MUST_EXIST);
} else {
    print_error(get_string('missingidandcmid', 'mod_it'));
}

$modulecontext = context_module::instance($cm->id);

require_course_login($course, true, $cm);
require_capability('mod/it:view', $modulecontext);

// Verify course context.
$h5pcm = get_coursemodule_from_id('hvp', 7);
if (!$h5pcm) {
    print_error('invalidcoursemodule');
}

// Set up view assets.
require('../hvp/classes/view_assets.php');
require('../hvp/locallib.php');
$view    = new \mod_hvp\view_assets($h5pcm, $course);
$content = $view->getcontent();
$view->validatecontent();

// Add H5P assets to page.
$view->addassetstopage();
// $view->logviewed();

$PAGE->set_url('/mod/it/view.php', array('id' => $cm->id));
// $PAGE->set_title(format_string($moduleinstance->name));
$PAGE->set_title('Test Interactive Transcript');
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($modulecontext);

echo $OUTPUT->header();
echo '<div class="clearer"></div>';

// Output introduction.
if (trim(strip_tags($content['intro'], '<img>'))) {
    echo $OUTPUT->box_start('mod_introbox', 'hvpintro');
    echo format_module_intro('hvp', (object) array(
        'intro'       => $content['intro'],
        'introformat' => $content['introformat'],
    ), $cm->id);
    echo $OUTPUT->box_end();
}

// Print any messages.
\mod_hvp\framework::printMessages('info', \mod_hvp\framework::messages('info'));
\mod_hvp\framework::printMessages('error', \mod_hvp\framework::messages('error'));

$view->outputview();

echo '<p>';
echo '<span id="marker_1" data-min="1" data-max="4">- Life ain\'t so great<br>- Yeah. there\'ll be dots in the mud</span>';
echo '<span id="marker_2" data-min="4" data-max="8">- And life ain\'t Hollywood<br>- for any one of us</span>';
echo '<span id="marker_3" data-min="8" data-max="12">- If ever you\'re in doubt.<br>- just get your wings out</span>';
echo '<span id="marker_4" data-min="12" data-max="16">- It\'s alright. darling.<br>- get your wriggle on</span>';
echo '</p>';

echo $OUTPUT->footer();

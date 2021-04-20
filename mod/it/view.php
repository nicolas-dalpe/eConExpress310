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
$PAGE->requires->js_call_amd('mod_it/it', 'init');

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

// Make a PHP object with the json settings from H5P.
$VideoContent = json_decode($content['filtered']);

// Get the transcript file path.
$transcriptFile = $VideoContent->interactiveVideo->video->textTracks->videoTrack[1]->track->path;

// Get the transcript file records.
$file = $DB->get_record('files', array('filearea' => 'content', 'filename' => substr($transcriptFile, 6)), 'contenthash');
$contenthash = $file->contenthash;

// Get the transcript file path in  Moodle data folder.
$transcriptPath = sprintf(
    '/var/www/md_ec_moodle310/filedir/%s/%s/%s',
    substr($contenthash, 0, 2),
    substr($contenthash, 2, 2),
    $contenthash
);

if (file_exists($transcriptPath)) {
    $lines = file($transcriptPath);
    $c = array();
    $i = 0;

    foreach ($lines as $key => $line) {

        // Remove line break from empty lines.
        $line = trim($line);

        // Skip the empty lines and the WEBVTT file title.
        if ($line == 'WEBVTT' || empty($line)) {
            continue;
        }

        // Start the subtitle index.
        if (intval(trim($line)) !== 0) {
            $i = $line;
            $c[$i]['index'] = $i;
            continue;
        }

        // Generate the timecode.
        if (strstr($line, '-->') !== false) {
            $timeline = explode('-->', $line);

            $startdate = new DateTime($timeline[0]);
            $c[$i]['startSecond'] = intval(date('s', $startdate->getTimestamp()));

            $enddate = new DateTime($timeline[1]);
            $c[$i]['endSecond'] = intval(date('s', $enddate->getTimestamp()));
            continue;
        } else {
            // Get the subtitle content.
            $c[$i]['content'] = format_text($line, FORMAT_HTML);
        }
    }
}

// Add bookmarks.
$bookmarks = $VideoContent->interactiveVideo->assets->bookmarks;

foreach ($bookmarks as $key => $value) {
    $bookmarks[$key] = (array) $value;
    $bookmarks[$key]['startSecond'] = $bookmarks[$key]['time'];
}

foreach ($c as $ckey => $line) {
    foreach ($bookmarks as $key => $bookmark) {

        if ($bookmark['time'] <= $line['startSecond']) {
            array_splice($c, $ckey-1, 0, array($bookmark));
            unset($bookmarks[$key]);
            continue;
        }
    }
}
echo '<p id="subtitle">';

foreach ($c as $key => $sub) {
    if (isset($c[$key]['content'])) {
        echo sprintf('<span data-index="%d" data-min="%d" data-max="%d">%s</span>',
            $sub['index'],
            $sub['startSecond'],
            $sub['endSecond'],
            $sub['content']
        );
    } else {
        echo sprintf('<br><br><span class="bookmark" data-min="%d">%s</span><br>',
            $sub['startSecond'],
            $sub['label']
        );
    }
    echo "\n";
}

echo '</p>';
/*
echo '<span class="bookmark">First bookmark title</span><br>';
echo '<span data-min="1" data-max="4">'.format_text('Lorem ipsum dolor sit amet, consectetur \( \sqrt[a]{b+c} \) adipiscing elit.', FORMAT_HTML).'</span>';
echo '<span data-min="4" data-max="8">Morbi a justo vehicula, venenatis sapien ut, rhoncus turpis. Pellentesque sit amet dapibus orci. Sed varius nisl ipsum, non semper massa pellentesque et.</span>';
echo '<span data-min="8" data-max="12">Sed hendrerit ut ante non fermentum.</span>';
echo '<span data-min="12" data-max="16">Sed in turpis at augue mollis sodales.</span>';
echo '<span data-min="16" data-max="19">Morbi neque libero, eleifend id tincidunt ut, dignissim at velit.</span>';
echo '<span class="bookmark">Second bookmark title</span><br>';
echo '<span data-min="19" data-max="21">Mauris tempus facilisis leo ac auctor. Integer a tempor magna.</span>';
echo '<span data-min="21" data-max="24">Pellentesque sit amet dapibus orci. Sed varius nisl ipsum, non semper massa pellentesque et.</span>';
echo '<span data-min="25" data-max="28">Sed tincidunt dapibus nisi vitae dignissim.</span>';
echo '<span data-min="28" data-max="30">Maecenas vitae lacus finibus, maximus orci eget, accumsan urna.</span>';
echo '<span data-min="30" data-max="32">Nulla condimentum eros in pellentesque laoreet.</span>';
echo '<span data-min="32" data-max="34">Lorem ipsum dolor sit amet, consectetur adipiscing elit.</span>';
echo '<span class="bookmark">Third bookmark title</span><br>';
echo '<span class="bookmark">Fourth bookmark title</span><br>';
*/

echo $OUTPUT->footer();

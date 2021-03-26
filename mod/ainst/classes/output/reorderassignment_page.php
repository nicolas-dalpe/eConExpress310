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
 * Assessment instruction
 *
 * @package mod_ainst
 * @copyright  2020 Knowledge One Inc.  {@link http://knowledgeone.ca}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_ainst\output;

defined('MOODLE_INTERNAL') || die();

// Custom function for Assessment Instruction module.
use mod_ainst\local\assignment;

use renderable;
use renderer_base;
use templatable;
use stdClass;

// Course module context class.
use context_module;

// Class for creating and manipulating urls.
use moodle_url;

// Class for managing language string.
use lang_string;

class reorderassignment_page implements renderable, templatable {

    /**
     * @var object $course The course object containing the assignments.
     */
    public $course;

    /**
     * Construct this renderable.
     *
     * @param object $course The course object
     */
    public function __construct($course) {
        // Make sure we have a course object.
        if (is_object($course)) {
            $this->course = $course;
        } else {
            throw new \Exception(new lang_string('missingidandcmid', 'ainst', null, 'en'), 1);

        }
    }

    /**
     * Export this data so it can be used as the context for a mustache template.
     *
     * @return stdClass
     */
    public function export_for_template(renderer_base $output) {

        // Data object containing the content for the template.
        $data = new stdClass();

        $assignment = new assignment();

        // Add the instruction view page URL.
        $instructions = $assignment->get_all_assignments($this->course);

        // Display message if the instruction list is empty.
        if (!$instructions) {
            $data->noinstruction = true;
            $data->noinstructionmsg = new lang_string('noinstructionmsg_student', 'ainst', null, 'en');
        } else {
            $data->isinstruction = true;

            // Make the instructions list mustache friendly.
            $data->instruction = new \ArrayIterator($instructions);

            // Get the Moodle Drag & Drop Handler.
            // MTHYMOOD-646 - As a developer, I want to set the drag & drop handler in the renderer.
            $data->dragDropHandler = $output->render_from_template('core/drag_handle', array('movetitle' => get_string('move')));
        }

        // Add the "Return to assessment" button.
        $data->goto_assignment_url = new moodle_url('/mod/ainst/index.php', array('id' => $this->course->id));
        $data->goto_assignments_link = new lang_string('goto_assignments_link', 'ainst', null, 'en');

        return $data;
    }
}
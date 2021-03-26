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

class reordersection_page implements renderable, templatable {

    /**
     * @var obj $cm The course module object.
     */
    public $cm;

    /**
     * Construct this renderable.
     *
     * @param object $cm The course module object.
     */
    public function __construct($cm) {
        // Make the cm a property of the renderer.
        if (is_object($cm)) {
            $this->cm = $cm;
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

        // Get the assignment's section.
        $sections = $assignment->get_assignment_section($this->cm->instance);

        // Display message if the instruction list is empty.
        if (!$sections) {
            $data->nosection = true;
            $data->nosectionmsg = new lang_string('nosectionmsg', 'ainst', null, 'en');
        } else {
            $data->nosection = false;

            // Make the sections list mustache friendly.
            $data->section = new \ArrayIterator($sections);

            // Get the Moodle Drag & Drop Handler.
            // MTHYMOOD-646 - As a developer, I want to set the drag & drop handler in the renderer.
            $data->dragDropHandler = $output->render_from_template('core/drag_handle', array('movetitle' => get_string('move')));
        }

        // Add the "Return to assessment" button.
        $data->goto_section_url = new moodle_url('/mod/ainst/view.php', array('id' => $this->cm->id));
        $data->goto_assignment_link = new lang_string('goto_assignment_link', 'ainst', null, 'en');

        return $data;
    }
}
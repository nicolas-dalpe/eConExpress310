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

class view_page implements renderable, templatable {

    /**
     * @var object $instruction The instruction to display.
     */
    public $instruction;

    /**
     * @var object $cm The course module object.
     */
    protected $cm;

    /**
     * Construct this renderable.
     *
     * @param object $cm The course module
     */
    public function __construct($cm) {
        $this->cm = $cm;
    }

    /**
     * Export this data so it can be used as the context for a mustache template.
     *
     * @return stdClass
     */
    public function export_for_template(renderer_base $output) {
        global $COURSE;

        $assignment = new assignment();

        $context = \context_module::instance($this->cm->id);

        // Data object containing the content for the template.
        $data = new stdClass();

        // Get the Instruction basic info.
        $data->instruction = $assignment->get_assignment($this->cm->instance);

        // Get the uploaded files from the file manager.
        $filemanager = $assignment->get_filemanager_files($this->cm->instance);
        if ($filemanager !== false) {
            $data->instruction->filemanager = new \ArrayIterator($filemanager);
        }

        // Wether we should display the Weight as a donut chart or a string.
        $data->instruction->weigthaschart = assignment::display_weight_as_chart(
            $data->instruction->weight
        );

        // Add course short name to the instruction object.
        $data->instruction->course_shortname = $COURSE->shortname;

        // Get the Instruction section.
        $data->sections = new \ArrayIterator(
            $assignment->get_assignment_section($this->cm->instance)
        );

        return $data;
    }
}
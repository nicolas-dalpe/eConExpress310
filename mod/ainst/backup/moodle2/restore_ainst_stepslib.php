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
 * Structure step to restore one assessment activity
 *
 * @package mod_ainst
 * @copyright  2020 Knowledge One Inc.  {@link http://knowledgeone.ca}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

// Custom function for Assessment Instruction module.
use mod_ainst\local\assignment as assignment;

class restore_ainst_activity_structure_step extends restore_activity_structure_step {

    /**
     * Define the structure of the XML backup file.
     */
    protected function define_structure() {

        $paths = array();

        // Describe the assessment path in the XML backup file.
        $paths[] = new restore_path_element('ainst', '/activity/ainst');

        // Describe the assessment section path in the XML backup file.
        $paths[] = new restore_path_element('ainst_section', '/activity/ainst/ainst_sections/ainst_section');

        // Return the paths wrapped into standard activity structure.
        return $this->prepare_activity_structure($paths);
    }

    /**
     * Restore an individual assessment activity.
     *
     * @param arr $data The assignement data.
     */
    protected function process_ainst($data) {
        global $DB;

        // Transform the assessment data into an object.
        $data = (object)$data;

        // Get the old assignement id.
        $oldid = $data->id;

        // Set the new course id.
        $data->course = $this->get_courseid();

        // Insert the assessment record.
        $newitemid = $DB->insert_record('ainst', $data);

        // Immediately after inserting "activity" record, call this.
        $this->apply_activity_instance($newitemid);
    }

    /**
     * Restore an individual assessment section.
     *
     * @param arr $data The section data.
     */
    protected function process_ainst_section($data) {
        global $DB;

        // Transform the section data into an object.
        $data = (object)$data;

        // Get the old section id.
        $oldid = $data->id;

        // Get the assessment id.
        $data->ainst_id = $this->get_new_parentid('ainst');

        // Create the section.
        $newitemid = $DB->insert_record('ainst_section', $data);

        // Replace the old assessment id with the new one.
        $this->set_mapping('ainst_section', $oldid, $newitemid);
    }

    /**
     * This method is executed after creating a new assessment or section.
     */
    protected function after_execute() {
        // Add assessment related files, no need to match by itemname (just internally handled context).
        $this->add_related_files('mod_ainst', 'intro', null);

        // Add the section files.
        $a = new assignment();
        $sectionfileareas = $a->section_editors_get_filearea();
        foreach ($sectionfileareas as $filearea) {
            $this->add_related_files('mod_ainst', $filearea, null);
        }
    }
}
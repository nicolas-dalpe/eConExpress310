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

defined('MOODLE_INTERNAL') || die();

// Custom function for Assignment Instruction module.
use mod_ainst\local\assignment as assignment;

/**
 * Define the complete ainst structure for backup, with file and id annotations.
 */
class backup_ainst_activity_structure_step extends backup_activity_structure_step {

    protected function define_structure() {

        // To know if we are including userinfo.
        $userinfo = $this->get_setting_value('userinfo');

        // Define each element separated.
        $assignment = new backup_nested_element(
            'ainst', array('id'), array(
                'name', 'intro', 'introformat',
                'videokey', 'videotbn', 'duedate', 'weight',
                'inst_order', 'timecreated', 'timemodified'
            )
        );

        $sections = new backup_nested_element('ainst_sections');
        $section = new backup_nested_element(
            'ainst_section', array('id'), array(
                'ainst_id', 'ainst_section_type_id', 'name',
                'intro', 'introformat', 'section_order',
                'timecreated', 'timemodified'
            )
        );

        // Build the tree.
        $assignment->add_child($sections);
        $sections->add_child($section);

        // Define sources.
        $assignment->set_source_table('ainst', array(
            'id' => backup::VAR_ACTIVITYID
        ));

        $section->set_source_sql(
            'SELECT * FROM {ainst_section} WHERE ainst_id = ?',
            array(backup::VAR_PARENTID)
        );

        // Define id annotations.

        // Define file annotations.
        $assignment->annotate_files('mod_ainst', 'intro', null, $contextid = null);

        $a = new assignment();
        $sectionfileareas = $a->section_editors_get_filearea();
        foreach ($sectionfileareas as $filearea) {
            $section->annotate_files('mod_ainst', $filearea, null, null);
        }

        // Return the root element (ainst), wrapped into standard activity structure.
        return $this->prepare_activity_structure($assignment);
    }

    /**
     * Code the transformations to perform in the activity in
     * order to get transportable (encoded) links
     */
    static public function encode_content_links($content) {
        global $CFG;

        $base = preg_quote($CFG->wwwroot, "/");

        // Link to the list of assignment.
        $search = "/(".$base."\/mod\/ainst\/index.php\?id\=)([0-9]+)/";
        $content = preg_replace($search, '$@AINSTINDEX*$2@$', $content);

        // Link to assignment view by moduleid.
        $search = "/(".$base."\/mod\/ainst\/view.php\?id\=)([0-9]+)/";
        $content = preg_replace($search, '$@AINSTVIEWBYID*$2@$', $content);

        return $content;
    }
}
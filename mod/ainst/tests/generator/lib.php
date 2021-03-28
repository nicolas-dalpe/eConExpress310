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
 * mod_ainst data generator
 *
 * @package    mod_ainst
 * @category   test
 * @copyright  2020 Knowledge One Inc.  {@link http://knowledgeone.ca}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();


/**
 * Assessment instruction module data generator class
 *
 * @package    mod_ainst
 * @category   test
 * @copyright  2020 Knowledge One Inc.  {@link http://knowledgeone.ca}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_ainst_generator extends testing_module_generator {

    public function create_instance($record = null, array $options = null) {
        global $CFG, $DB;
        require_once($CFG->dirroot . '/lib/resourcelib.php');

        $record = (object)(array)$record;

        $record->enablecompletion = 1;

        // Basic assignment information.
        if (!isset($record->name)) {
            $record->name = 'Test assignment title';
        }
        if (!isset($record->content)) {
            $record->content = 'Test assignment content';
        }
        if (!isset($record->contentformat)) {
            $record->contentformat = FORMAT_MOODLE;
        }
        if (!isset($record->duedate)) {
            $record->duedate = 'Sept 22, 2020 at 7 PM';
        }
        if (!isset($record->weight)) {
            $record->weight = '76';
        }

        // Section information.
        $sections = array('stepbystep', 'gradings', 'techreq', 'tips', 'custom');
        foreach ($sections as $key => $section) {

            // Get the section type information.
            $section_type = $DB->get_record('ainst_section_type', array('shortname'=>$section), '*');

            if (!isset($record->{$section.'_name'})) {
                $record->{$section.'_name'} = $section_type->name;
            }
            if (!isset($record->{$section.'_content'})) {
                $record->{$section.'_content'} = array(
                    'text'   => '<p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Nulla rutrum mi vel neque tristique, et dictum nisl blandit. Etiam ac est eros. Integer in dolor nec odio dapibus ornare. Suspendisse potenti. Pellentesque posuere mi facilisis diam sagittis, sit amet sagittis turpis vestibulum. Aliquam dictum eros nec efficitur pellentesque.</p>',
                    'format' => '1'
                    // 'itemid' => 216550302
                );
            }
            if (!isset($record->{$section.'_order'})) {
                $record->{$section.'_order'} = $section_type->default_order;
            }
            if (!isset($record->{$section.'_section_type_id'})) {
                $record->{$section.'_section_type_id'} = $section_type->id;
            }
        }

        return parent::create_instance($record, (array)$options);
    }
}

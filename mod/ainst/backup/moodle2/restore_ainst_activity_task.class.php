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
 * Assessment restore task that provides all the settings and
 * steps to perform one complete restore of the activity
 *
 * @package mod_ainst
 * @copyright  2020 Knowledge One Inc.  {@link http://knowledgeone.ca}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

// Custom function for Assessment Instruction module.
use mod_ainst\local\assignment as assignment;

require_once($CFG->dirroot . '/mod/ainst/backup/moodle2/restore_ainst_stepslib.php');

class restore_ainst_activity_task extends restore_activity_task {

    /**
     * Define (add) particular settings this activity can have
     */
    protected function define_my_settings() {
        // No particular settings for this activity.
    }

    /**
     * Define (add) particular steps this activity can have.
     */
    protected function define_my_steps() {
        // Choice only has one structure step.
        $this->add_step(new restore_ainst_activity_structure_step('ainst_structure', 'ainst.xml'));
    }

    /**
     * Define the contents in the activity that must be
     * processed by the link decoder.
     */
    static public function define_decode_contents() {
        $contents = array();

        // Decode the assignment overview editor text.
        $contents[] = new restore_decode_content('ainst', array('intro'), 'ainst');

        // Decode the assignment sections editor text.
        $contents[] = new restore_decode_content('ainst_section', array('intro'), 'ainst_section');

        return $contents;
    }

    /**
     * Define the decoding rules for links belonging
     * to the activity to be executed by the link decoder.
     */
    static public function define_decode_rules() {
        $rules = array();

        $rules[] = new restore_decode_rule('AINSTVIEWBYID', '/mod/ainst/view.php?id=$1', 'course_module');
        $rules[] = new restore_decode_rule('AINSTINDEX', '/mod/ainst/index.php?id=$1', 'course');

        return $rules;
    }
}
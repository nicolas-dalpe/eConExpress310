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
 * @package    mod_ainst
 * @copyright  2020 Knowledge One Inc.  {@link http://knowledgeone.ca}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

function xmldb_ainst_install() {
    global $DB;

    // Create default timestamp.
    $timecreated = $timemodified = time();

    // Add basic section type.
    $dataobjects = array(
        array(
            'shortname' => 'stepbystep',
            'name' => get_string('section_title_stepbystep', 'ainst'),
            'default_order' => 10,
            'timecreated' => $timecreated,
            'timemodified' => $timemodified
        ),
        array(
            'shortname' => 'gradings',
            'name' => get_string('section_title_gradings', 'ainst'),
            'default_order' => 20,
            'timecreated' => $timecreated,
            'timemodified' => $timemodified
        ),
        array(
            'shortname' => 'techreq',
            'name' => get_string('section_title_techreq', 'ainst'),
            'default_order' => 30,
            'timecreated' => $timecreated,
            'timemodified' => $timemodified
        ),
        array(
            'shortname' => 'tips',
            'name' => get_string('section_title_tips', 'ainst'),
            'default_order' => 40,
            'timecreated' => $timecreated,
            'timemodified' => $timemodified
        ),
        array(
            'shortname' => 'custom',
            'name' => get_string('section_title_custom', 'ainst'),
            'default_order' => 50,
            'timecreated' => $timecreated,
            'timemodified' => $timemodified
        )
    );

    $DB->insert_records('ainst_section_type', $dataobjects);
}

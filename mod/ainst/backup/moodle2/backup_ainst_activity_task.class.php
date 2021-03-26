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
 * Define all the backup steps that will be used by the backup_ainst_activity_task.
 *
 * @package mod_ainst
 * @copyright  2020 Knowledge One Inc.  {@link http://knowledgeone.ca}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/ainst/backup/moodle2/backup_ainst_stepslib.php');
require_once($CFG->dirroot . '/mod/ainst/backup/moodle2/backup_ainst_settingslib.php');

/**
 * ainst backup task that provides all the settings and steps to perform one
 * complete backup of the activity
 */
class backup_ainst_activity_task extends backup_activity_task {

    // Define (add) particular settings this activity can have.
    protected function define_my_settings() {
        // No particular settings for this activity.
    }

    // Define (add) particular steps this activity can have.
    protected function define_my_steps() {
        $this->add_step(
            new backup_ainst_activity_structure_step('ainst_structure', 'ainst.xml')
        );
    }

    /**
     * Code the transformations to perform in the activity in
     * order to get transportable (encoded) links.
     */
    static public function encode_content_links($content) {
        return $content;
    }
}
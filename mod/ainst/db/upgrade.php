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

function xmldb_ainst_upgrade($oldversion) {
    global $DB;
    if ($oldversion < 2020072801) {
        $dbman = $DB->get_manager();

        // Changing type of field weight on table ainst to text.
        $table = new xmldb_table('ainst');
        $field = new xmldb_field('weight', XMLDB_TYPE_TEXT, null, null, null, null, null, 'duedate');

        // Launch change of type for field weight.
        $dbman->change_field_type($table, $field);

        // Ainst savepoint reached.
        upgrade_mod_savepoint(true, 2020072801, 'ainst');
    }

    return true;
}
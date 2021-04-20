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
 * Assessment instruction.
 *
 * @package    mod_ainst
 * @copyright  2020 Knowledge One Inc.  {@link http://knowledgeone.ca}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/externallib.php');

class mod_ainst_external extends external_api {

    /**
     * Returns description of method parameters
     * @return external_function_parameters
     */
    public static function reorderassignment_parameters() {
        return new external_function_parameters(
            array(
                'assignment' => new external_multiple_structure(
                    new external_single_structure(
                        array(
                            'id' => new external_value(PARAM_INT, 'Assignment ID'),
                            'inst_order' => new external_value(PARAM_INT, 'The new position of the assignments'),
                        )
                    )
                )
            )
        );
    }

    /**
     * The function itself
     * @return string welcome message
     */
    public static function reorderassignment($assignment) {
        global $DB;

        // Param check.
        $params = self::validate_parameters(
            self::reorderassignment_parameters(),
            array('assignment' => $assignment)
        );

        // If an exception is thrown in the below code, all DB queries in this code will be rollback.
        $transaction = $DB->start_delegated_transaction();

        // Now security checks.
        $context = get_context_instance(CONTEXT_COURSE, 8);
        self::validate_context($context);
        require_capability('mod/ainst:addinstance', $context);

        // Update assignments order.
        foreach ($params['assignment'] as $assignment) {
            $DB->update_record('ainst', $assignment, true);
        }

        // Update database.
        $transaction->allow_commit();

        return true;
    }

    /**
     * Returns description of method result value
     * @return external_description
     */
    public static function reorderassignment_returns() {
        return new external_value(PARAM_BOOL, 'True on success');
    }

    /**
     * Returns description of method parameters
     * @return external_function_parameters
     */
    public static function reordersection_parameters() {
        return new external_function_parameters(
            array(
                'section' => new external_multiple_structure(
                    new external_single_structure(
                        array(
                            'id' => new external_value(PARAM_INT, 'Section ID'),
                            'section_order' => new external_value(PARAM_INT, 'The new position of the sections'),
                        )
                    )
                )
            )
        );
    }

    /**
     * The function itself.
     * @return string welcome message
     */
    public static function reordersection($section) {
        global $DB;

        // Param check.
        $params = self::validate_parameters(
            self::reordersection_parameters(),
            array('section' => $section)
        );

        // If an exception is thrown in the below code, all DB queries in this code will be rollback.
        $transaction = $DB->start_delegated_transaction();

        // Update assignments order.
        foreach ($params['section'] as $section) {
            $DB->update_record('ainst_section', $section);
        }

        // Update database.
        $transaction->allow_commit();

        return true;
    }

    /**
     * Returns description of method result value
     * @return external_description
     */
    public static function reordersection_returns() {
        return new external_value(PARAM_BOOL, 'True on success');
    }
}
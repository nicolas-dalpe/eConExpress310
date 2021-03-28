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

$functions = array(
    // Web service function name.
    'mod_ainst_reorderassignment' => array(
        // Class containing the external function OR namespaced class in classes/external/XXXX.php.
        'classname'   => 'mod_ainst_external',
        // External function name.
        'methodname'  => 'reorderassignment',
        // Human readable description of the web service function.
        'description' => 'Reorder Assignment.',
        // Database rights of the web service function (read, write).
        'type'        => 'write',
        // Is the service available to 'internal' ajax calls.
        'ajax' => true,
        // Capabilities required by the function.
        'capabilities' => 'mod/ainst:addinstance'
    ),
    'mod_ainst_reordersection' => array(
        'classname'   => 'mod_ainst_external',
        'methodname'  => 'reordersection',
        'description' => 'Reorder Section.',
        'type'        => 'write',
        'ajax' => true,
        'capabilities' => 'mod/ainst:addinstance'
    )
);
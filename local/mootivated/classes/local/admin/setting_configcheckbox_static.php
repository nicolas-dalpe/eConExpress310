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
 * Static checkbox config.
 *
 * @package    local_mootivated
 * @copyright  2018 Mootivation Technologies Corp.
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_mootivated\local\admin;
defined('MOODLE_INTERNAL') || die();

/**
 * Static checkbox config.
 *
 * @package    local_mootivated
 * @copyright  2018 Mootivation Technologies Corp.
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class setting_configcheckbox_static extends \admin_setting_configcheckbox {

    /**
     * Write nothing.
     *
     * @param string $data The data.
     * @return void
     */
    public function write_setting($data) {
    }

    /**
     * Returns the field.
     *
     * @param string $data The data.
     * @param string $query The search query.
     * @return string
     */
    public function output_html($data, $query='') {
        global $OUTPUT;
        $isenabled = (string) $data === $this->yes;
        $value = $isenabled ? get_string('enabled', 'local_mootivated') : get_string('disabled', 'local_mootivated');
        return format_admin_setting($this, $this->visiblename, $value, $this->description, true, '', null, $query);
    }

}

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

namespace mod_ainst\output;

defined('MOODLE_INTERNAL') || die();

use plugin_renderer_base;

class renderer extends plugin_renderer_base {
    /**
     * Defer to template.
     *
     * @param index_page $page
     *
     * @return string html for the page
     */
    public function render_index_page($page) {
        $data = $page->export_for_template($this);
        return parent::render_from_template('mod_ainst/index', $data);
    }

    /**
     * Defer to template.
     *
     * @param view_page $page
     *
     * @return string html for the page
     */
    public function render_view_page($page) {
        $data = $page->export_for_template($this);
        return parent::render_from_template('mod_ainst/view', $data);
    }

    /**
     * Defer to template.
     *
     * @param reorderassignment_page $page
     *
     * @return string html for the page
     */
    public function render_reorderassignment_page($page) {
        $data = $page->export_for_template($this);
        return parent::render_from_template('mod_ainst/reorderassignment', $data);
    }

    /**
     * Defer to template.
     *
     * @param reordersection_page $page
     *
     * @return string html for the page
     */
    public function render_reordersection_page($page) {
        $data = $page->export_for_template($this);
        return parent::render_from_template('mod_ainst/reordersection', $data);
    }
}
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
 * Helper class.
 *
 * @package    local_qrsub
 * @copyright  2021 KnowledgeOne <nicolas.dalpe@knowledgeone.ca>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_qrsub\local;

use quiz_attempt;

defined('MOODLE_INTERNAL') || die();

/**
 * Helper class.
 *
 * @copyright  2021 KnowledgeOne <nicolas.dalpe@knowledgeone.ca>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class qrsub {

    /**
     * Replace the original mod/quiz path in the URL by local/qrsub
     *
     * @param str $URL The URL to modify.
     *
     * @return str The modified URL.
     */
    public static function replace_modquiz_path($url) {

        if (!is_string($url)) {
            return $url;
        }

        if (strpos($url, 'mod/quiz') !== false) {
            $url = str_replace('mod/quiz', 'local/qrsub', $url);
        }

        return $url;
    }

    /**
     * Find the next occurence of a hybrid question in a given attempt.
     *
     * @param quiz_attempt The quiz attempt object.
     * @param int $page The page to start from.
     *
     * @return array The page and slot # of the next hybrid question.
     */
    public static function get_next_hybrid_question(quiz_attempt $attemptobj, $page) {

        // Start at the next page.
        $page = $page + 1;

        // Get the slot of the current page.
        $slot = $attemptobj->get_slots($page);

        // Get the question type of the question in the slot.
        $a = $attemptobj->get_question_type_name($slot[0]);
        if ($a != 'hybrid') {
            while ($a != 'hybrid') {
                $page++;
                $slot = $attemptobj->get_slots($page);
                $a = $attemptobj->get_question_type_name($slot[0]);
            }
        }

        return array($slot, $page);
    }

    /**
     * Find the previous occurence of a hybrid question in a given attempt.
     *
     * @param quiz_attempt The quiz attempt object.
     * @param int $page The page to start from.
     *
     * @return array The page and slot # of the previous hybrid question.
     */
    public static function get_previous_hybrid_question(quiz_attempt $attemptobj, $page) {

        // Start at the next page.
        $page = $page - 1;

        // Get the slot of the current page.
        $slot = $attemptobj->get_slots($page);

        // Get the question type of the question in the slot.
        $a = $attemptobj->get_question_type_name($slot[0]);
        if ($a != 'hybrid') {
            while ($a != 'hybrid') {
                $page--;
                $slot = $attemptobj->get_slots($page);
                $a = $attemptobj->get_question_type_name($slot[0]);
            }
        }

        return array($slot, $page);
    }

    /**
     * Find the first occurence of a hybrid question in a given attempt.
     *
     * @param quiz_attempt The quiz attempt object.
     * @param int $page The page to start from.
     *
     * @return array The page and slot # of the first hybrid question.
     */
    public static function get_first_hybrid_question(quiz_attempt $attemptobj, $page) {

        // Get the slot of the current page.
        $slot = $attemptobj->get_slots($page);

        // Get the question type of the question in the slot.
        $a = $attemptobj->get_question_type_name($slot[0]);
        if ($a != 'hybrid') {
            while ($a != 'hybrid') {
                $page++;
                $slot = $attemptobj->get_slots($page);
                $a = $attemptobj->get_question_type_name($slot[0]);
            }
        }

        return array($slot, $page);
    }

    /**
     * Find if the current question is the last occurence of a hybrid question in a given attempt.
     *
     * @param quiz_attempt The quiz attempt object.
     * @param int $page The page to start from.
     *
     * @return array The page and slot # of the first hybrid question.
     */
    public static function is_last_hybrid_question(quiz_attempt $attemptobj, $currentslot) {

        // Get all slot in the current attempt.
        $allslots = $attemptobj->get_slots('all');

        // Cycle the remanining slots in the attempt to find an hybrid question.
        for ($i= $currentslot[0]; $i<count($allslots); $i++) {
            $slot = $attemptobj->get_slots($i);
            $qtypename = $attemptobj->get_question_type_name($slot[0]);
            if ($qtypename == 'hybrid') {
                return false;
            }
        }

        return true;
    }
}

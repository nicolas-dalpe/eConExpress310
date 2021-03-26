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

// Custom function for Assessment Instruction module.
use mod_ainst\local\assignment;

use renderable;
use renderer_base;
use templatable;
use stdClass;

// Course module context class.
use context_module;

// Class for creating and manipulating urls.
use moodle_url;

class index_page extends \plugin_renderer_base implements renderable, templatable {

    /**
     * @var object $course The course object.
     */
    public $course;

    /**
     * Construct this renderable.
     *
     * @param object $course The course object
     */
    public function __construct($course) {
        $this->course = $course;
    }

    /**
     * Export this data so it can be used as the context for a mustache template.
     *
     * @return stdClass
     */
    public function export_for_template(renderer_base $output) {

        $assignment = new assignment();

        // Get the course context class.
        $context = \context_course::instance($this->course->id);

        // Data object containing the content for the template.
        $data = new stdClass();

        // True if not student.
        $capaddcomponent = has_capability('mod/ainst:addinstance', $context);

        // Whether or not to display the action links in the tpl.
        $data->action_links = $capaddcomponent;

        // Get the assessment timeline download link from course custom field.
        $coursemetadata = $assignment->get_course_metadata($this->course->id);
        if (isset($coursemetadata['assignment_timeline'])) {

            // Assignment timeline field id.
            $itemid = $coursemetadata['assignment_timeline']['itemid'];

            // Strip empty <p> or <br />.
            $value = strip_tags($coursemetadata['assignment_timeline']['value'], 'a');

            if (strlen($value) > 0) {

                // Get the assessment timeline download URL.
                $rawfieldvalue = file_rewrite_pluginfile_urls(
                    $coursemetadata['assignment_timeline']['value'],
                    'pluginfile.php',
                    $context->id,
                    'customfield_textarea',
                    'value',
                    $coursemetadata['assignment_timeline']['itemid']
                );

                // Whether or not to display the assessment timeline download link in the tpl.
                $data->assignment_timeline = true;

                // Assessment timeline download link.
                $data->assignment_timeline_link_url = strip_tags($rawfieldvalue, 'a');
            }
        }

        // Get all instructions.
        $instructions = $assignment->get_all_assignments($this->course);

        // Display a message if their are no instruction to display.
        if (!$instructions) {
            $data->noinstruction = true;
            if (!$capaddcomponent) {
                $data->noinstructionmsg = new \lang_string('noinstructionmsg_student', 'ainst', null, 'en');
            } else {
                $data->noinstructionmsg = new \lang_string('noinstructionmsg_teacher', 'ainst', null, 'en');
            }
        } else {

            // Instruction template block.
            $data->isinstruction = true;

            // Get the course module info.
            $modinfo = get_fast_modinfo($this->course->id);

            foreach ($instructions as $id => $instruction) {

                // Get the cm id for the current instruction.
                $cmid = $modinfo->instances['ainst'][$id]->id;

                // Whether or not the instruction is visible to the student.
                $uservisible = $modinfo->instances['ainst'][$id]->uservisible;

                // Contain the restriction information.
                $availableinfo = $modinfo->instances['ainst'][$id]->availableinfo;

                // Do not render the instruction if it is restricted and its a student.
                if (!$uservisible && empty($availableinfo) && !$capaddcomponent) {
                    unset($instructions[$id]);
                    continue;
                }

                // Only display the link if the instruction is available to the user.
                if ($uservisible) {

                    // View instruction details link URL.
                    $instructionurl = $modinfo->instances['ainst'][$instruction->id]->url;
                    $instructions[$id]->instruction_detail_url = $instructionurl->out();
                }

                // Display the restriction message.
                if (!empty($availableinfo)) {
                    $instructions[$id]->restricted = true;

                    if (is_object($availableinfo)) {
                        // Get the core availability renderer.
                        $availabilitymessages = $output->page->get_renderer('core_availability');

                        // Render the multiple restriction messages.
                        $restrictionmessages = $availabilitymessages->render_core_availability_multiple_messages(
                            $availableinfo
                        );
                    } else {

                        // If there is only one restriction message,
                        // Moodle already rendered it.
                        $restrictionmessages = $availableinfo;
                    }

                    // Add the restriction messages to the template.
                    $instructions[$id]->availableinfo = $restrictionmessages;
                }

                // Wether we should display the Weight as a donut chart or a string.
                $instructions[$id]->weigthaschart = assignment::display_weight_as_chart(
                    $instruction->weight
                );

                if ($capaddcomponent) {

                    // Delete assessment link URL.
                    $instructions[$id]->instruction_delete_url = new moodle_url(
                        '/mod/ainst/delete.php', array('delete' => $cmid)
                    );

                    // Update assessment link URL.
                    $instructions[$id]->instruction_update_url = new moodle_url(
                        '/course/modedit.php', array('update' => $cmid, 'return' => 1)
                    );

                    // Reorder instruction section link URL.
                    $instructions[$id]->instruction_reordersection_url = new moodle_url(
                        '/mod/ainst/reorder_section.php', array('id' => $cmid)
                    );
                }
            }

            // Make the instructions list mustache friendly.
            $data->instruction = new \ArrayIterator($instructions);
        }

        if ($capaddcomponent) {
            // Add the "Add instruction" button.
            $data->add_instruction_url = new moodle_url(
                '/course/modedit.php',
                array('add' => 'ainst', 'course' => $this->course->id, 'section' => '0', 'return' => '0', 'sr' => '0')
            );
            $data->add_instruction_link = new \lang_string('add_instruction', 'ainst', null, 'en');

            // Add the "Order instruction" button if there is more than one instruction.
            if (isset($data->instruction)) {
                if ($data->instruction->count() > 1) {
                    $data->order_instruction_url = new moodle_url(
                        '/mod/ainst/reorder_assignment.php',
                        array('id' => $this->course->id)
                    );
                    $data->order_instruction_link = new \lang_string(
                        'order_instruction', 'ainst', null, 'en'
                    );
                }
            }

            // Copy link to this page.
            // MTHYMOOD-605 - As a teacher, I want a link to a course assessment list.
            $data->copy_link_url = new moodle_url(
                '/mod/ainst/index.php',
                array('course' => $this->course->shortname)
            );

            $data->copy_link_link = new \lang_string(
                'copy_link_to_assignment', 'ainst', null, 'en'
            );
        }

        return $data;
    }
}
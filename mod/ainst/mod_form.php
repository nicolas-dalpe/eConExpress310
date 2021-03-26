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
 * Settings form for the Assessment Instruction module.
 *
 * @package    mod_ainst
 * @copyright  2020 Knowledge One Inc.  {@link http://knowledgeone.ca}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/course/moodleform_mod.php');
require_once($CFG->dirroot . '/mod/ainst/lib.php');

// Custom function for Assessment Instruction module.
use mod_ainst\local\assignment;

class mod_ainst_mod_form extends moodleform_mod {

    public function definition() {
        global $CFG, $DB;

        $mform = $this->_form;

        // Adding the "general" fieldset, where all the common settings are shown.
        $mform->addElement('header', 'general', get_string('general', 'form'));

        // Adding the standard "name" field.
        $mform->addElement('text', 'name', get_string('instructionname', 'mod_ainst'), array('size' => '64'));
        if (!empty($CFG->formatstringstriptags)) {
            $mform->setType('name', PARAM_TEXT);
        } else {
            $mform->setType('name', PARAM_CLEANHTML);
        }

        $mform->addRule('name', null, 'required', null, 'client');
        $mform->addRule('name', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');

        // Adding the standard "intro" and "introformat" fields.
        $this->standard_intro_elements(get_string('introduction', 'ainst'));

        // File attachment.
        $fieldname = get_string('attachments', 'ainst');
        $options = assignment::instruction_filemanager_option();
        $mform->addElement('filemanager', 'attachments_filemanager', $fieldname, null, $options);

        // Due date.
        $mform->addElement('text', 'duedate', get_string('duedate', 'ainst'), array('size' => '64'));
        $mform->setType('duedate', PARAM_TEXT);
        $mform->addRule('duedate', null, 'required', null, 'client');

        // Weight.
        $mform->addElement('text', 'weight', get_string('weight', 'ainst'), array('size' => '64'));
        $mform->setType('weight', PARAM_TEXT);

        // Make the weight field required.
        $mform->addRule('weight', null, 'required', null, 'client');

        // Add weight field instruction.
        $mform->addHelpButton('weight', 'weight', 'ainst');

        // Defines section types and there default order.
        $sectiontypes = $DB->get_records('ainst_section_type', null, 'default_order');

        // Creates all the section forms.
        foreach ($sectiontypes as $sectiontype) {

            // Section header (makes the collapse).
            $mform->addElement('header', $sectiontype->shortname, get_string('section_title_'.$sectiontype->shortname, 'ainst'));

            // Section title.
            $mform->addElement('text', $sectiontype->shortname.'_name', get_string('section_name', 'ainst'));
            $mform->setType($sectiontype->shortname.'_name', PARAM_TEXT);
            $mform->setDefault($sectiontype->shortname.'_name', get_string('section_title_'.$sectiontype->shortname, 'ainst'));
            if ($sectiontype->shortname != 'custom') {
                $mform->hardFreeze($sectiontype->shortname.'_name');
            }

            // Content editor name.
            $fieldname = $sectiontype->shortname.'_content';

            // Content editor label.
            $fieldlabel = get_string('section_intro', 'ainst');

            // Content editor options.
            $fieldoptions = assignment::section_editors_options($this->context);

            // Content editor.
            $mform->addElement('editor', $fieldname, $fieldlabel, null, $fieldoptions);
            $mform->setType($fieldname, PARAM_RAW);

            // Order.
            $mform->addElement('hidden', $sectiontype->shortname.'_order', $sectiontype->default_order);
            $mform->setType($sectiontype->shortname.'_order', PARAM_INT);

            // Section type id (mdl_ainst_section.ainst_section_type_id).
            $mform->addElement('hidden', $sectiontype->shortname.'_section_type_id', $sectiontype->id);
            $mform->setType($sectiontype->shortname.'_section_type_id', PARAM_INT);

            // Section id.
            $mform->addElement('hidden', $sectiontype->shortname.'_id', 0);
            $mform->setType($sectiontype->shortname.'_id', PARAM_INT);
        }

        // Add standard elements.
        $this->standard_coursemodule_elements();

        $this->add_action_buttons();
    }

    public function data_preprocessing(&$data) {
        global $DB;

        // Edit mode if we have an instance.
        if ($this->current->instance) {

            $assignment = new assignment();

            // Shortcut to the module/component name.
            $component = assignment::MODULE_NAME;

            // Get the file manager options.
            $options = assignment::instruction_filemanager_option();

            // Indentifies the file area.
            $filearea = $assignment->section_editors_make_filearea('attachments');

            $itemid = file_get_submitted_draft_itemid('attachments');

            // Load any preexisting files into the draftarea
            // For some reason file_prepare_standard_filemanager expect $data to be an object
            // and Moodle is passing $data as an array. Hence the double convertion. To be opt.
            $data = file_prepare_standard_filemanager(
                (object) $data, 'attachments', $options, $this->context, $component, $filearea, $itemid
            );
            $data = (array) $data;

            // Get section types (step-by-step, tips for success, etc).
            $sectiontypes = $assignment->get_section_types();

            // Remove the &nbsp; from the due date.
            $data['duedate'] = assignment::duedatefilters($data['duedate'], true);

            foreach ($sectiontypes as $sectiontype) {

                // Make the editor name as set in defined().
                $fieldname = $sectiontype->shortname.'_content';

                // Get the section content.
                $sectioneditor = $DB->get_record(
                    'ainst_section', array('ainst_id' => $data['id'], 'ainst_section_type_id' => $sectiontype->id)
                );

                // If the section exists.
                if ($sectioneditor !== false) {

                    // Returns draft area item id for a given element.
                    $draftitemid = file_get_submitted_draft_itemid($fieldname);

                    // Helps identify the file area to save to.
                    $contextid = $this->context->id;

                    // Plugin name.
                    $component = 'mod_ainst';

                    // Indentifies the file area.
                    $filearea = $assignment->section_editors_make_filearea($sectiontype->shortname);

                    // Helps identifies the file area (in our case it's always 0).
                    $itemid  = 0;

                    // File area options.
                    $options = assignment::section_editors_options($this->context);

                    // Text to rewrite the URL from.
                    $text = $sectioneditor->intro;

                    // Save editors files from draft area to their proper file area.
                    $data[$fieldname]['text'] = file_prepare_draft_area(
                        $draftitemid, $contextid, $component, $filearea, $itemid, $options, $text
                    );

                    $data[$fieldname]['format'] = $sectioneditor->introformat;
                    $data[$fieldname]['itemid'] = $draftitemid;

                    // Set section order field.
                    $data[$sectiontype->shortname.'_order'] = $sectioneditor->section_order;

                    // Set the custom title in the custom section.
                    if ($sectiontype->shortname == 'custom') {
                        $data['custom_name'] = $sectioneditor->name;
                    }
                }
            }
        }
    }
}
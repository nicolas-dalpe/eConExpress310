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

// Custom function for Assessment Instruction module.
use mod_ainst\local\assignment;

/**
 * Given an object containing all the necessary data,
 * (defined by the form in mod_form.php) this function
 * will create a new instance and return the id number
 * of the new instance.
 *
 * @param object $ainst the data that came from the form.
 * @return mixed the id of the new instance on success,
 *          false or a string error message on failure.
 */
function ainst_add_instance($formdata) {
    global $DB;

    $assignment = new assignment();

    // Define the module context.
    $context = context_module::instance(
        $formdata->coursemodule
    );

    // Plugin name.
    $component = assignment::MODULE_NAME;

    // Helps identifies the file area (in our case it's always 0).
    $itemid  = 0;

    // Start saving assessment.
    $formdata->visibleoncoursepage = 0;

    // Apply due date filter.
    $formdata->duedate = assignment::duedatefilters($formdata->duedate);

    // Add timecreated col.
    $formdata->timecreated = $formdata->timemodified = time();

    // Set default assignment order.
    $formdata->inst_order = 0;

    // Store the assessment in the database.
    $formdata->id = $DB->insert_record('ainst', $formdata);

    // Returns draft area item id for a given editor.
    $draftitemid = file_get_submitted_draft_itemid('attachments_filemanager');

    // Indentifies the file area.
    $filearea = $assignment->section_editors_make_filearea('attachments');

    // File manager options.
    $options = assignment::instruction_filemanager_option();

    // Save file manager files from draft area to the proper file area.
    file_save_draft_area_files(
        $draftitemid, $context->id, $component, $filearea, $itemid, $options
    );

    // Defines section types and there default order.
    $sectiontypes = $assignment->get_section_types();


    // Create section rows.
    foreach ($sectiontypes as $sectiontype) {

        // Insert the section if the content box is not empty.
        if (!empty($formdata->{$sectiontype->shortname.'_content'}['text'])) {
            $section = new stdClass();
            $section->ainst_id = $formdata->id;
            $section->ainst_section_type_id = $formdata->{$sectiontype->shortname.'_section_type_id'};
            $section->name = $formdata->{$sectiontype->shortname.'_name'};

            // Returns draft area item id for a given editor.
            $draftitemid = file_get_submitted_draft_itemid($sectiontype->shortname.'_content');

            // Helps identify the file area to save to.
            $contextid = $context->id;

            // Indentifies the file area.
            $filearea = $assignment->section_editors_make_filearea($sectiontype->shortname);

            // File area options.
            $options = assignment::section_editors_options($context);

            // Text to rewrite the URL from.
            $text = $formdata->{$sectiontype->shortname.'_content'}['text'];

            // Save editors files from draft area to their proper file area.
            $section->intro = file_save_draft_area_files(
                $draftitemid, $contextid, $component, $filearea, $itemid, $options, $text
            );

            $section->introformat = $formdata->{$sectiontype->shortname.'_content'}['format'];
            $section->section_order = $formdata->{$sectiontype->shortname.'_order'};
            $section->timemodified = $section->timecreated = time();

            $section->id = $DB->insert_record('ainst_section', $section);
        }
    }

    return $formdata->id;
}

function ainst_update_instance($data, $mform) {
    global $DB;

    $context = context_module::instance($mform->get_coursemodule()->id);

    $assignment = new assignment();

    // Plugin name.
    $component = assignment::MODULE_NAME;

    // Update assignment.
    $instruction = new stdClass();
    $instruction->id = $data->instance;
    $instruction->course = $data->course;
    $instruction->name = $data->name;
    $instruction->intro = $data->intro;
    $instruction->introformat = $data->introformat;
    // Apply due date filter.
    $instruction->duedate = assignment::duedatefilters($data->duedate);
    $instruction->weight = $data->weight;
    $instruction->timemodified = time();

    $DB->update_record('ainst', $instruction);

    // Returns draft area item id for a given editor.
    $draftitemid = file_get_submitted_draft_itemid('attachments_filemanager');

    // Indentifies the file area.
    $filearea = $assignment->section_editors_make_filearea('attachments');

    // File manager options.
    $options = assignment::instruction_filemanager_option();

    $itemid = 0;

    // Save file manager files from draft area to the proper file area.
    file_save_draft_area_files(
        $draftitemid, $context->id, $component, $filearea, $itemid, $options
    );

    // Remove all the section before inserting them again.
    $assignment->delete_sections($instruction->id);

    // Defines section types and there default order.
    $sectiontypes = $assignment->get_section_types();

    // Create section rows.
    foreach ($sectiontypes as $sectiontype) {

        /* Strip tag from the content field before checking if it contains data
           So we don't confuse <br> or <p></p> for content. */
        $sectioncontent = strip_tags($data->{$sectiontype->shortname.'_content'}['text']);
        if (!empty($sectioncontent)) {

            $section = new stdClass();
            $section->ainst_id = $instruction->id;
            $section->ainst_section_type_id = $data->{$sectiontype->shortname.'_section_type_id'};
            $section->name = $data->{$sectiontype->shortname.'_name'};

            // Returns draft area item id for a given editor.
            $draftitemid = file_get_submitted_draft_itemid($sectiontype->shortname.'_content');

            // Make the file area.
            $filearea = $assignment->section_editors_make_filearea($sectiontype->shortname);

            // Define editor options.
            $options = assignment::section_editors_options($context);

            // Editor content.
            $content = $data->{$sectiontype->shortname.'_content'}['text'];

            // Save the file(s) from the draft area to the module file area.
            $section->intro = file_save_draft_area_files(
                $draftitemid, $context->id, $component, $filearea, 0, $options, $content
            );

            // Set the editor format.
            $section->introformat = $data->{$sectiontype->shortname.'_content'}['format'];

            // Define the section order.
            $section->section_order = $data->{$sectiontype->shortname.'_order'};
            $section->timemodified = time();

            // Check if the section exists.
            $conditions = array('ainst_id' => $instruction->id, 'ainst_section_type_id' => $section->ainst_section_type_id);
            if ($DB->record_exists('ainst_section', $conditions)) {

                // Get the section id and use it to update the section.
                $existingsection = $DB->get_record('ainst_section', $conditions);
                $section->id = $existingsection->id;
                $DB->update_record('ainst_section', $section);
            } else {
                // Add created time if it's a new section.
                $section->timecreated = time();
                $DB->insert_record('ainst_section', $section);
            }
        }
    }

    return $instruction->id;
}

/**
 * Delete an assignment and it's subsection.
 *
 * @param int $id The assignment id
 * @return true
 */
function ainst_delete_instance($id) {
    global $DB;

    // Delete Assignment.
    if ($DB->record_exists('ainst', array('id' => $id))) {
        $DB->delete_records('ainst', array('id' => $id));

        // Delete sections.
        $DB->delete_records('ainst_section', array('ainst_id' => $id));
    }

    return true;
}

/**
 * Indicates API features that the assignment supports.
 *
 * @uses FEATURE_BACKUP_MOODLE2
 * @param string $feature
 * @return mixed True if yes (some features may use other values)
 */
function ainst_supports($feature) {
    switch($feature) {
        case FEATURE_BACKUP_MOODLE2:
            return true;
        default:
            return null;
    }
}

/**
 * Mark the activity completed (if required) and trigger the course_module_viewed event.
 *
 * @param  stdClass $ainst   Assignment object
 * @param  stdClass $course  course object
 * @param  stdClass $cm      course module object
 * @param  stdClass $context context object
 */
function assignment_view($ainst, $course, $cm, $context) {

    // Trigger course_module_viewed event.
    $params = array(
        'context' => $context,
        'objectid' => $ainst->id
    );

    $event = \mod_ainst\event\course_module_viewed::create($params);
    $event->add_record_snapshot('course_modules', $cm);
    $event->add_record_snapshot('course', $course);
    $event->add_record_snapshot('ainst', $ainst);
    $event->trigger();

    // Completion.
    $completion = new completion_info($course);
    $completion->set_module_viewed($cm);
}

/**
 * Add reorder section link to navigation settings.
 *
 * @param settings_navigation $settings
 * @param navigation_node $navref
 * @return void
 */
function ainst_extend_settings_navigation(settings_navigation $settings, navigation_node $navref) {
    global $PAGE;

    // We want to add these new nodes after the Edit settings node, and before the
    // Locally assigned roles node. Of course, both of those are controlled by capabilities.
    $keys = $navref->get_children_key_list();
    $beforekey = null;
    $i = array_search('modedit', $keys);
    if ($i === false and array_key_exists(0, $keys)) {
        $beforekey = $keys[0];
    } else if (array_key_exists($i + 1, $keys)) {
        $beforekey = $keys[$i + 1];
    }

    $cm = $PAGE->cm;
    if (!$cm) {
        return;
    }

    $course = $PAGE->course;
    if (!$course) {
        return;
    }

    $activityid = $PAGE->cm->instance;
    if (!$activityid) {
        return;
    }

    if (has_capability('mod/ainst:addinstance', $PAGE->cm->context)) {

        // Link to reorder the sections.
        $url = new moodle_url('/mod/ainst/reorder_section.php', array('id' => $PAGE->cm->id));

        // Link title in the settings navigation.
        $title = get_string('reorder_section_settings_link', 'ainst');

        // Navigation node type.
        $type = navigation_node::TYPE_SETTING;

        // Create and add the node to the settings navigation.
        $node = navigation_node::create($title, $url, $type, null, 'mod_ainst');
        $navref->add_node($node, $beforekey);
    }
}

/**
 * Serve the files from the AINST_FILEAREA file areas
 *
 * @param stdClass $course the course object
 * @param stdClass $cm the course module object
 * @param stdClass $context the context
 * @param string $filearea the name of the file area
 * @param array $args extra arguments (itemid, path)
 * @param bool $forcedownload whether or not force download
 * @param array $options additional options affecting the file serving
 * @return bool false if the file not found, just send the file otherwise and do not return anything
 */
function ainst_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, array $options=array()) {

    // Make sure the user is logged in and has access to the module.
    require_login($course, true, $cm);

    $assignment = new assignment();

    // Plugin name.
    $component = assignment::MODULE_NAME;

    // Check the contextlevel is as expected - if your plugin is a block, this becomes CONTEXT_BLOCK, etc.
    if ($context->contextlevel != CONTEXT_MODULE) {
        return false;
    }

    // Make sure the filearea is one of those used by the plugin.
    if (!in_array($filearea, $assignment->section_editors_get_filearea())) {
        return false;
    }

    // Check the relevant capabilities - these may vary depending on the filearea being accessed.
    if (!has_capability('mod/ainst:view', $context)) {
        return false;
    }

    // Leave this line out if you set the itemid to null in make_pluginfile_url (set $itemid to 0 instead).
    $itemid = 0;

    // Extract the filename / filepath from the $args array.
    $filename = array_pop($args);
    if (!$args) {
        // ... $args is empty => the path is '/'.
        $filepath = '/';
    } else {
        // ... $args contains elements of the filepath.
        $filepath = '/'.implode('/', $args).'/';
    }

    // Retrieve the file from the File Storage API.
    $fs = get_file_storage();
    $file = $fs->get_file($context->id, $component, $filearea, $itemid, $filepath, $filename);
    if (!$file) {
        return false; // The file does not exist.
    }

    // We can now send the file back to the browser - in this case with a cache lifetime of 1 day and no filtering.
    send_stored_file($file, null, 0, $forcedownload, $options);
}
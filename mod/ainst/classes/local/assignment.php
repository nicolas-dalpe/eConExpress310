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
 * Library of internal functions to manage Assessment.
 *
 * @package    mod_ainst
 * @copyright  2020 Knowledge One Inc. {@link http://knowledgeone.ca}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_ainst\local;

// Course module context class.
use context_module;

use Exception;

// Class for creating and manipulating urls.
use moodle_url;

// Class for managing language string.
use lang_string;

class assignment {

    /**
     * @var str AINST_FILEAREA The file area prefix for the section editor.
     */
    const AINST_FILEAREA = 'ainst_attachment';

    /**
     * @var str MODULE_NAME Shortcut the module name.
     */
    const MODULE_NAME = 'mod_ainst';
    const SHORT_MODULE_NAME = 'ainst';

    /**
     * @var object $DB Moodle's database object.
     */
    protected $db;

    public function __construct() {
        global $DB;

        // Shortcut the Moodle database object.
        $this->db = $DB;
    }

    /**
     * Throw a new coding exception.
     *
     * @param str $message The message to display.
     * @param var $var Any object to display along with the message.
     *
     * @return void
     */
    public function throw_exception($message = null, $var = null) {
        global $CFG;

        // Terminate the function if all is null.
        if (is_null($message) && is_null($var)) {
            return;
        }

        // Display the message if debug is set to developer level.
        if ($CFG->debugdeveloper) {
            throw new \coding_exception($message, var_export($var, true));
        }

        return;
    }


    /**
     * Get the course object based on course shortname.
     *
     * @param int $shortname The course shortname (ie: MTHY 603).
     *
     * @return obj The course object.
     */
    public function get_course_from_shortname($shortname = false) {

        // Make sure we have a course shortname.
        if (!$shortname || empty($shortname)) {
            return false;
        }

        return $this->db->get_record('course', array('shortname' => urldecode($shortname)));
    }

    /**
     * Get the module context.
     *
     * @param int $assignmentid The assignment id.
     *
     * @return obj The context object.
     */
    public function get_contextmodule($assignmentid) {
        global $COURSE;

        $cm = get_coursemodule_from_instance('ainst', $assignmentid, $COURSE->id, false, MUST_EXIST);
        $context = context_module::instance($cm->id);

        return $context;
    }

    /**
     * Get all the custom field added to the course.
     *
     * @param int $course The course id.
     *
     * @return array Each custom field along with their value.
     */
    public function get_course_metadata($courseid) {
        $handler = \core_customfield\handler::get_handler('core_course', 'course');

        // Returns the custom field values for an individual course.
        $datas = $handler->get_instance_data($courseid, true);

        $metadata = [];
        foreach ($datas as $data) {
            // Skip if the field is blank.
            if (empty($data->get_value())) {
                continue;
            }

            $metadata[$data->get_field()->get('shortname')] = array(
                'itemid' => $data->get('id'),
                'value' => $data->get_value()
            );
        }
        return $metadata;
    }

    /**
     * Get all assessments in a given course.
     *
     * @param int $course The course object.
     *
     * @return array The assessment(s) record.
     */
    public function get_all_assignments($course) {

        // Find the course id.
        if (is_object($course)) {
            $courseid = $course->id;
        } else if (is_array($course)) {
            $courseid = $course['id'];
        } else if (is_numeric($course)) {
            $courseid = (int) $course;
        } else {
            // Show debug message to developer.
            $this->throw_exception(
                new lang_string('missingcourseid', self::SHORT_MODULE_NAME, null, 'en'),
                $course
            );

            // Return false so we can instruct the user there is no assessment yet.
            return false;
        }

        $assignments = $this->db->get_records('ainst', array('course' => $courseid), 'inst_order');
        if (!$assignments) {
            return false;
        }

        foreach ($assignments as $assignment) {

            // Get the context_module from course_module.
            $context = $this->get_contextmodule($assignment->id);

            $assignments[$assignment->id]->intro = file_rewrite_pluginfile_urls(
                $assignments[$assignment->id]->intro, 'pluginfile.php', $context->id,
                'mod_ainst', 'intro', null
            );

            // Apply default text filter.
            $assignments[$assignment->id]->intro = format_text($assignment->intro, FORMAT_HTML);
        }

        return $assignments;
    }

    /**
     * Get an assessment data.
     *
     * @param int $assignmentid The assessment's id.
     *
     * @return object The assessment record.
     */
    public function get_assignment($assignmentid) {
        if (is_numeric($assignmentid)) {

            // Make sure the assessment exists.
            if ($this->db->record_exists('ainst', array('id' => $assignmentid))) {

                // Get the specified assignment.
                $assignment = $this->db->get_record('ainst', array('id' => $assignmentid));

                // Get the context_module from course_module.
                $context = $this->get_contextmodule($assignment->id);

                // Rewrite file token into proper URL.
                $assignment->intro = file_rewrite_pluginfile_urls(
                    $assignment->intro, 'pluginfile.php', $context->id,
                    'mod_ainst', 'intro', null
                );

                // Apply default text filter.
                $assignment->intro = format_text($assignment->intro, FORMAT_HTML);

                return $assignment;
            }
        }

        return false;
    }

    /**
     * Get all the files uploaded though the file manager.
     *
     * @param int $assignmentid The assessment's id.
     *
     * @return array The file list.
     */
    public function get_filemanager_files($assignmentid) {
        global $CFG;

        // Return false if the assignment id is not a number.
        if (!is_numeric($assignmentid)) {
            $this->throw_exception(
                new lang_string('missingassignmentid', self::SHORT_MODULE_NAME, null, 'en'),
                $assignmentid
            );
            return false;
        }

        // Get the context_module from course_module.
        $context = $this->get_contextmodule($assignmentid);

        // Get the file manager file area.
        $filearea = $this->section_editors_make_filearea('attachments');

        // All files are returned if itemid is false.
        $itemid = false;

        // Get the uploaded files from the file manager.
        $fs = get_file_storage();
        $filemanagerfiles = $fs->get_area_files(
            $context->id, self::MODULE_NAME, $filearea, $itemid, $sort = "filename"
        );

        // Return false if there is no uploaded files.
        if (count($filemanagerfiles) === 0) {
            return false;
        }

        // Exclude Moodle . and .. database entry.
        $filestoexclude = array('.', '..');

        foreach ($filemanagerfiles as $i => $file) {

            $filename = $file->get_filename();

            if (!in_array($filename, $filestoexclude)) {

                // Build the fileurl.
                $fileurl = moodle_url::make_pluginfile_url(
                    $file->get_contextid(),
                    $file->get_component(),
                    $file->get_filearea(),
                    // Set itemid to null to remove the /0/ from the file URL.
                    null,
                    $file->get_filepath(),
                    $filename
                );

                $return[$i] = array(
                    'filename' => $filename,
                    'fileurl' => $fileurl->out()
                );
            }
        }

        return $return;
    }

    /**
     * Get assessment's section data.
     *
     * @param int $assignment_id The assessment's id.
     *
     * @return object The list of assessment section.
     */
    public function get_assignment_section($assignmentid) {
        global $COURSE;

        // Assessment ID must be numeric.
        if (!is_numeric($assignmentid)) {
            return false;
        }

        // Check if assessment exists.
        if (!$this->db->record_exists('ainst', array('id' => $assignmentid))) {
            return false;
        }

        // Check if it has section, otherwise return an empty array.
        if (!$this->db->record_exists('ainst_section', array('ainst_id' => $assignmentid))) {
            return array();
        }

        // Get the context_module from course_module.
        $context = $this->get_contextmodule($assignmentid);

        // Return the sections content.
        $sections = $this->db->get_records('ainst_section', array('ainst_id' => $assignmentid), 'section_order');

        // Add section type name and shortname.
        $sectiontypes = $this->get_section_types();
        foreach ($sections as $sectionid => $sectiondata) {
            $sections[$sectionid]->type_shortname = $sectiontypes[$sectiondata->ainst_section_type_id]->shortname;
            $sections[$sectionid]->type_name = $sectiontypes[$sectiondata->ainst_section_type_id]->name;

            $sections[$sectionid]->intro = file_rewrite_pluginfile_urls(
                $sections[$sectionid]->intro, 'pluginfile.php', $context->id,
                'mod_ainst',
                $this->section_editors_make_filearea($sectiontypes[$sectiondata->ainst_section_type_id]->shortname),
                null
            );

            // Apply default text filter.
            $sections[$sectionid]->intro = format_text($sections[$sectionid]->intro, FORMAT_HTML);
        }

        return $sections;
    }

    /**
     * Returns the section types contained in mdl_ainst_section
     *
     * @param str $defaultorder The default order by clause.
     *
     * @return object The section types.
     */
    public function get_section_types($defaultorder = 'default_order') {
        return $this->db->get_records('ainst_section_type', null, $defaultorder);
    }

    /**
     * Delete all the section of a given assessment.
     *
     * @param int $assignmentid The assessment's id.
     *
     * @return bool
     */
    public function delete_sections($assignmentid) {
        if (is_numeric($assignmentid)) {
            if ($this->db->record_exists('ainst_section', array('ainst_id' => $assignmentid))) {
                return $this->db->delete_records('ainst_section', array('ainst_id' => $assignmentid));
            }
        }

        return false;
    }

    /**
     * Returns an array of options for the file manager.
     *
     * @param array $newoptions The options to overwrite the default options.
     *
     * @return array The file manager options.
     */
    public static function instruction_filemanager_option($newoptions = '') {
        global $PAGE, $CFG, $COURSE;

        // Get the max upload file size for the user.
        $maxbytes = get_user_max_upload_file_size($PAGE->context, $CFG->maxbytes, $COURSE->maxbytes);

        // Default option.
        $defaultoptions = array(
            // Are subdirectories allowed? (true or false) (Default 1).
            'subdirs' => 1,
            // Restricts the size of each individual file (Default 0).
            'maxbytes' => $maxbytes,
            // Restricts the total size of all the files (Default 0).
            'areamaxbytes' => FILE_AREA_MAX_BYTES_UNLIMITED,
            // Restricts the total number of files (Default -1).
            'maxfiles' => EDITOR_UNLIMITED_FILES,
            // You can specify what file types are accepted by filemanager.
            // All current file types are listed in lib/classes/filetypes.php.
            'accepted_types' => array('audio', 'document', 'image', 'video'),
            'return_types'=> FILE_INTERNAL | FILE_EXTERNAL
        );

        // Merge the new options from the function arg or return the default.
        if (is_array($newoptions)) {
            $return = array_merge($defaultoptions, $newoptions);
        } else {
            $return = $defaultoptions;
        }

        return $return;
    }

    /**
     * Returns an array of options for the editors that are used for assessment section.
     *
     * @param stdClass $context
     *
     * @return array
     */
    public static function section_editors_options($context) {
        return array(
            'autosave' => false,
            'changeformat' => 1,
            'context' => $context,
            'maxbytes' => 0,
            'maxfiles' => -1,
            'noclean' => 1,
            'subdirs' => 1,
            'trusttext' => 0
        );
    }

    /**
     * Return all the file area for section editor.
     *
     * @return array File areas
     */
    public function section_editors_get_filearea() {
        // Contains all the file areas available.
        $fileareas = array();

        // Create all the file areas from the section short name.
        foreach ($this->get_section_types() as $section) {
            $fileareas[] = $this->section_editors_make_filearea($section->shortname);
        }

        // Add file manager file area.
        $fileareas[] = $this->section_editors_make_filearea('attachments');

        return $fileareas;
    }

    /**
     * Make a file area name for the given section.
     *
     * @param str $section The section shortname.
     *
     * @return str File area
     */
    public function section_editors_make_filearea($section = false) {
        // Make sure we have a section sufix (section short name).
        if (!$section || empty($section)) {
            return false;
        }

        return sprintf("%s_%s", self::AINST_FILEAREA, $section);
    }

    /**
     * Apply custom filter to the assessment due date filed.
     *
     * @param str $txt The text to apply the filter to.
     *
     * @return str $txt The filtered text.
     */
    public static function duedatefilters($txt, $reverse = false) {

        $find = array(' AM', ' am', ' PM', ' pm');

        $replace = array('&nbsp;AM', '&nbsp;am', '&nbsp;PM', '&nbsp;pm');

        if ($reverse) {
            $strfind = $replace;
            $strreplace = $find;
        } else {
            $strfind = $find;
            $strreplace = $replace;
        }

        // Add &nbsp; before AM and PM.
        $txt = str_replace($strfind, $strreplace, $txt);

        return $txt;
    }

    /**
     * Wether we should display the Weight as a donut chart or a string.
     *
     * @param str $weight The text to apply the filter to.
     *
     * @return bool $weigthaschart
     */
    public static function display_weight_as_chart($weight) {

        // If weight is not a number, display it as text.
        if (!is_numeric($weight)) {
            return false;
        }

        // Wether we should display the Weight as a donut chart or a string.
        if (intval($weight) >= 1 && intval($weight) <= 100) {
            $weigthaschart = true;
        } else {
            $weigthaschart = false;
        }

        return $weigthaschart;
    }
}
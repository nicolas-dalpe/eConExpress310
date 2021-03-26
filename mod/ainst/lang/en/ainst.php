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

/**************************************/
/********** Module settings ***********/
/**************************************/
$string['pluginname'] = "ainst";
$string['pluginadministration'] = 'Assignment Instruction';
$string['modulename'] = 'Assignments';
$string['moduleshortname'] = 'Assignment';
$string['modulenameplural'] = 'Assignment instructions';
$string['modulename_help'] = 'Instruction for an Assignment.';
$string['missingidandcmid'] = "Missing course module";
$string['missingcourseid'] = "Missing course id.";
$string['missingassignmentid'] = "Missing assignment id.";


/**************************************/
/************ Navigation *************/
/*************************************/
$string['ainst_main_nav_link_title'] = 'Assignments';


/**********************************/
/********** Page titles ***********/
/**********************************/
$string['pt_edit_inst_settings'] = 'Editing instruction settings';
$string['pt_Assignments_Overview'] = 'Assignments Overview';
$string['pt_Assignments_Reorder'] = 'Assignments Reorder';
$string['pt_Section_Reorder'] = 'Reorder Sections';


/**********************************/
/******* Assignment form *********/
/**********************************/
$string['instructionname'] = 'Assignment’s title';
$string['introduction'] = 'Overview';
$string['attachments'] = 'Attachments';
$string['videokey'] = 'Video key';
$string['videotbn'] = 'Video thumbnail';
$string['duedate'] = 'Due';
$string['weight'] = 'Weight of final grade';
$string['weight_help'] = 'If the weight is a number between 1 and 100, the weight will be displayed as a doughnut chart otherwise it is displayed as is.';


/**********************************/
/********* Section Name ***********/
/**********************************/
$string['section_title_stepbystep'] = "Step-by-Step";
$string['section_title_gradings'] = "Grading and Feedback";
$string['section_title_techreq'] = "Technical Requirements";
$string['section_title_tips'] = "Tips for Success";
$string['section_title_custom'] = "Custom Section";


/**********************************/
/********* Section form ***********/
/**********************************/
$string['section_title'] = 'Section';
$string['section_type'] = 'Type';
$string['section_name'] = 'Title';
$string['section_intro'] = 'Content';


/***********************************************/
/********* Assignments Overview Page ***********/
/***********************************************/
$string['add_instruction'] = 'Add an Assignment';
$string['order_instruction'] = 'Reorder Assignments';
$string['copy_link_to_assignment'] = 'Copy the link to this page';
$string['assignment_timeline_link_title'] = 'View the Assignment Timeline';
$string['table_col_assignment'] = 'Assignment';
$string['table_col_duedate'] = 'Due';
$string['table_col_weight'] = 'Weight';
$string['table_col_action'] = 'Actions';
$string['percent'] = '%';
$string['noinstructionmsg_student'] = 'There is no assignment for this course yet.';
$string['noinstructionmsg_teacher'] = 'There is no assignment for this course yet. Use the "Add an Assignment" button above to add a new assignment.';
$string['copy_success'] = 'Link successfully copied to clipboard.';
$string['edit_link_title'] = 'Edit this assignment';
$string['edit_link_reorder'] = 'Reorder section in this assignment';
$string['delete_link_title'] = 'Delete this assignment';


/***********************************************/
/********* Assignments Reorder Page ************/
/***********************************************/
$string['reorder_assignment_success'] = 'The assignments have been successfully reordered.';
$string['reorder_assignment_error'] = 'An error happened. Please, refresh the page (CTRL+F5) and try again.';
$string['goto_assignments_link'] = 'Return to  Assignments Overview';


/********************************************/
/********* Sections Reorder Page ************/
/********************************************/
$string['reorder_section_success'] = 'The sections have been successfully reordered.';
$string['reorder_section_error'] = 'An error happened. Please, refresh the page (CTRL+F5) and try again.';
$string['reorder_section_settings_link'] = 'Reorder sections';
$string['goto_assignment_link'] = 'Return to assignment';
$string['nosectionmsg'] = 'There is no section yet. Click on Return to assignment then Settings then Edit settings to add a section.';
$string['save_order'] = 'Save Order';


/**********************************/
/********* Capabilities ***********/
/**********************************/
$string['ainst:addinstance'] = 'Add new assignment';
$string['ainst:view'] = 'View assignments';

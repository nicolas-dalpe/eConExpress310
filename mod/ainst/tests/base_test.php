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
 * @copyright  2020 Knowledge One Inc. {@link http://knowledgeone.ca}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_ainst;

// Custom function for Assessment Instruction module.
use mod_ainst\local\assignment;

class mod_ainst_base_testcase extends \advanced_testcase {

    protected function setUp() {
        global $DB;

        $this->a = new assignment();
        $this->db = $DB;
    }

    /**
     * Get the custom field generator.
     * Use to test assignment.get_course_metadata() in test_get_course_metadata().
     *
     * @return core_customfield_generator
     */
    protected function get_cf_generator(): \core_customfield_generator {
        return $this->getDataGenerator()->get_plugin_generator('core_customfield');
    }

    /**
     * Test add_instance.
     * @group lib
     */
    public function test_ainst_add_instance() {
        global $DB;

        $this->resetAfterTest(true);

        // Make sure we have no assignment in the test database.
        $this->assertEquals(0, $DB->count_records('ainst'));
        $this->assertEquals(0, $DB->count_records('ainst_section'));

        // Generate a user and log the user in.
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);

        // Generate the course to ass the instance in.
        $course = $this->getDataGenerator()->create_course();
        $generator = $this->getDataGenerator()->get_plugin_generator('mod_ainst');
        $generator->create_instance(array('course'=>$course->id));

        $this->assertEquals(1, $DB->count_records('ainst'));
        $this->assertEquals(5, $DB->count_records('ainst_section'));
    }

    /**
     * Test get_course_from_shortname().
     *
     * @group assignment
     */
    public function test_get_course_from_shortname() {

        $this->resetAfterTest(true);

        $generatedcategory = $this->getDataGenerator()->create_category();

        $generatedcourse = $this->getDataGenerator()->create_course(array(
            'name' => 'Generated Course',
            'shortname' =>'Fake Labs',
            'category' => $generatedcategory->id
        ));

        // The method should return false when passed false.
        $this->assertFalse(
            $this->a->get_course_from_shortname(false)
        );

        // The method should return false when passed an empty string.
        $this->assertFalse(
            $this->a->get_course_from_shortname('')
        );

        // The method should return false when passed an non-existing shortname.
        $this->assertFalse(
            $this->a->get_course_from_shortname('ABCD-1234')
        );

        // Get the course object from the shortname.
        $testcourse = $this->a->get_course_from_shortname('Fake%20Labs');

        $this->assertEquals($generatedcourse->id, $testcourse->id);
    }

    /**
     * Test get_course_metadata().
     *
     * @group assignment
     */
    public function test_get_course_metadata() {
        global $DB;

        $this->resetAfterTest(true);

        $custom_field_value = '<p><a href="@@PLUGINFILE@@/CATS691_AssignmentsTimeline_Fall2020.pdf" target="_blank">@@PLUGINFILE@@/CATS691_AssignmentsTimeline_Fall2020.pdf</a><br></p>';

        // Use the custom field plugin data generator.
        $cfgenerator = $this->get_cf_generator();

        $category = $cfgenerator->create_category();
        $course   = $this->getDataGenerator()->create_course();

        // Make sure we have no custom field created.
        $fields = $DB->get_records(\core_customfield\field::TABLE, ['categoryid' => $category->get('id')]);
        $this->assertCount(0, $fields);

        // Create the field and make sure it exists.
        $field = $cfgenerator->create_field([
            'categoryid' => $category->get('id'),
            'name' => 'Assignment Timeline',
            'shortname' => 'assignment_timeline',
            'type' => 'textarea'
        ]);
        $this->assertTrue(\core_customfield\field::record_exists($field->get('id')));

        // Populate the custom field.
        $cfgenerator->add_instance_data(
            $field, $course->id, ['text' => $custom_field_value, 'format' => FORMAT_HTML]
        );

        // Call the method to test.
        $coursemetadata = $this->a->get_course_metadata($course->id);

        // Make sure the custom is retrieve by the method.
        $this->assertTrue(isset($coursemetadata['assignment_timeline']));

        // Make sure the value is being properly fetched.
        $this->assertEquals($custom_field_value, $coursemetadata['assignment_timeline']['value']);

        // Delete the custom field and make sure the method returns an empty array.
        $this->assertTrue($field->delete());
        $fields = $DB->get_records(\core_customfield\field::TABLE, ['categoryid' => $category->get('id')]);
        $this->assertCount(0, $fields);
        $this->assertFalse(\core_customfield\field::record_exists($field->get('id')));

        // Call the method to test with no custom field.
        $coursemetadata = $this->a->get_course_metadata($course->id);
        $this->assertFalse(isset($coursemetadata['assignment_timeline']));
        $this->assertCount(0, $coursemetadata);
    }

    /**
     * Test get_all_assignments($course).
     * @group assignment
     */
    public function test_get_all_assignments() {
        global $PAGE, $CFG;

        $this->resetAfterTest(true);

        // Generate a user and log the user in.
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);

        $generatedcategory = $this->getDataGenerator()->create_category();

        $generatedcourse = $this->getDataGenerator()->create_course(array(
            'category' => $generatedcategory->id,
            'name' => 'Generated Course'
        ));

        // Set the generated course so we can use global $COURSE; in assignment.get_contextmodule();
        $PAGE->set_course($generatedcourse);

        // Number of assessments to create.
        $numberassessments = 3;

        for ($i=1; $i <= $numberassessments; $i++) {
            $page = $this->getDataGenerator()->create_module('ainst', array(
                'course' => $generatedcourse->id,
                'name' => 'Assessment Instruction '.$i
            ));
        }

        $testcourse = $this->a->get_all_assignments($generatedcourse);
        $this->assertEquals($numberassessments, count($testcourse));

        // Pass the course object as an array.
        $testcourse = $this->a->get_all_assignments((array) $generatedcourse);
        $this->assertEquals($numberassessments, count($testcourse));

        // Pass the course id as int.
        $testcourse = $this->a->get_all_assignments($generatedcourse->id);
        $this->assertEquals($numberassessments, count($testcourse));

        // Pass the course id as string.
        $testcourse = $this->a->get_all_assignments((string) $generatedcourse->id);
        $this->assertEquals($numberassessments, count($testcourse));

        // Debug seems to be always on. Make sure we have a codeing exception if
        // course id is not valid.
        $this->expectException('coding_exception');
        $testcourse = $this->a->get_all_assignments('abc');
    }

    /**
     * Test dueDateFilter().
     * @group assignment
     */
    public function test_dueDateFilter() {

        $this->assertEquals('Monday, 1&nbsp;PM 2020', $this->a->duedatefilters('Monday, 1 PM 2020'));
        $this->assertEquals('Monday, 2&nbsp;AM 2020', $this->a->duedatefilters('Monday, 2 AM 2020'));
        $this->assertEquals('Monday, 3&nbsp;pm 2020', $this->a->duedatefilters('Monday, 3 pm 2020'));
        $this->assertEquals('Monday, 4&nbsp;am 2020', $this->a->duedatefilters('Monday, 4 am 2020'));

        $this->assertEquals('Monday, 1 PM 2020', $this->a->duedatefilters('Monday, 1&nbsp;PM 2020', true));
        $this->assertEquals('Monday, 2 AM 2020', $this->a->duedatefilters('Monday, 2&nbsp;AM 2020', true));
        $this->assertEquals('Monday, 3 pm 2020', $this->a->duedatefilters('Monday, 3&nbsp;pm 2020', true));
        $this->assertEquals('Monday, 4 am 2020', $this->a->duedatefilters('Monday, 4&nbsp;am 2020', true));
    }

    /**
     * Test display_weight_as_chart().
     * @group assignment
     */
    public function test_display_weight_as_chart() {

        // Number between 1 and 100 should be displayed as pie chart.
        $this->assertEquals(true, $this->a->display_weight_as_chart('45'));

        // Number oustide of the 1 to 100 range should be displayed as is.
        $this->assertEquals(false, $this->a->display_weight_as_chart('0'));
        $this->assertEquals(false, $this->a->display_weight_as_chart('125'));

        // Values that aren't a number should be displayed as is.
        $this->assertEquals(false, $this->a->display_weight_as_chart('Potato'));
    }
}
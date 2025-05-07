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
 * Privacy provider tests for double marking.
 *
 * @package    local_doublemarking
 * @copyright  2025 Your Name <your@email.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\writer;
use core_privacy\local\request\deletion_criteria;
use core_privacy\local\request\approved_userlist;
use core_privacy\tests\provider_testcase;
use local_doublemarking\privacy\provider;

/**
 * Privacy provider test for double marking.
 *
 * @package    local_doublemarking
 * @copyright  2025 Your Name <your@email.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class local_doublemarking_privacy_testcase extends provider_testcase {

    /**
     * Test for provider::get_metadata()
     */
    public function test_get_metadata() {
        $collection = new collection('local_doublemarking');
        $metadata = provider::get_metadata($collection);
        
        $this->assertSame($collection, $metadata);
        $itemcollection = $collection->get_collection();
        
        $this->assertCount(1, $itemcollection);
        
        $table = reset($itemcollection);
        $this->assertEquals('local_doublemarking_alloc', $table->get_name());
        
        $privacyfields = $table->get_privacy_fields();
        $this->assertArrayHasKey('userid', $privacyfields);
        $this->assertArrayHasKey('marker1', $privacyfields);
        $this->assertArrayHasKey('marker2', $privacyfields);
        $this->assertArrayHasKey('ratifier', $privacyfields);
        $this->assertArrayHasKey('marker1grade', $privacyfields);
        $this->assertArrayHasKey('marker2grade', $privacyfields);
        $this->assertArrayHasKey('marker1feedback', $privacyfields);
        $this->assertArrayHasKey('marker2feedback', $privacyfields);
        $this->assertArrayHasKey('finalgrade', $privacyfields);
        $this->assertArrayHasKey('ratificationcomment', $privacyfields);
        
        $this->assertEquals('privacy:metadata:local_doublemarking_alloc', $table->get_summary());
    }

    /**
     * Set up test data.
     */
    protected function setUp(): void {
        $this->resetAfterTest();
        $this->setAdminUser();
        
        // Create a course with an assignment.
        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $assigngen = $generator->get_plugin_generator('mod_assign');
        
        $assign = $assigngen->create_instance([
            'course' => $course->id,
            'name' => 'Test Assignment',
            'grade' => 100
        ]);
        
        // Create users.
        $student1 = $generator->create_user();
        $student2 = $generator->create_user();
        $marker1 = $generator->create_user();
        $marker2 = $generator->create_user();
        $ratifier = $generator->create_user();
        
        // Enroll users.
        $studentrole = $generator->create_role();
        $teacherrole = $generator->create_role();
        $managerrole = $generator->create_role();
        
        $generator->enrol_user($student1->id, $course->id, $studentrole);
        $generator->enrol_user($student2->id, $course->id, $studentrole);
        $generator->enrol_user($marker1->id, $course->id, $teacherrole);
        $generator->enrol_user($marker2->id, $course->id, $teacherrole);
        $generator->enrol_user($ratifier->id, $course->id, $managerrole);
        
        // Create marker allocations.
        $this->create_allocation($assign->id, $student1->id, $marker1->id, $marker2->id, $ratifier->id);
        $this->create_allocation($assign->id, $student2->id, $marker1->id, $marker2->id, $ratifier->id);
        
        // Store for later use.
        $this->course = $course;
        $this->assign = $assign;
        $this->student1 = $student1;
        $this->student2 = $student2;
        $this->marker1 = $marker1;
        $this->marker2 = $marker2;
        $this->ratifier = $ratifier;
    }
    
    /**
     * Create test allocation.
     *
     * @param int $assignid The assignment ID
     * @param int $studentid The student ID
     * @param int $marker1id First marker ID
     * @param int $marker2id Second marker ID
     * @param int $ratifierid Ratifier ID
     * @return stdClass The allocation record
     */
    protected function create_allocation($assignid, $studentid, $marker1id, $marker2id, $ratifierid) {
        global $DB;
        
        $allocation = new stdClass();
        $allocation->assignmentid = $assignid;
        $allocation->userid = $studentid;
        $allocation->marker1 = $marker1id;
        $allocation->marker2 = $marker2id;
        $allocation->ratifier = $ratifierid;
        $allocation->marker1grade = 75.0;
        $allocation->marker2grade = 85.0;
        $allocation->marker1feedback = 'Feedback from first marker';
        $allocation->marker2feedback = 'Feedback from second marker';
        $allocation->finalgrade = 80.0;
        $allocation->ratificationcomment = 'Final ratification comment';
        $allocation->timecreated = time();
        $allocation->timemodified = time();
        
        $id = $DB->insert_record('local_doublemarking_alloc', $allocation);
        return $DB->get_record('local_doublemarking_alloc', ['id' => $id]);
    }

    /**
     * Test for provider::get_contexts_for_userid()
     */
    public function test_get_contexts_for_userid() {
        $cm = get_coursemodule_from_instance('assign', $this->assign->id);
        $context = context_module::instance($cm->id);
        
        // Test student context.
        $contextlist = provider::get_contexts_for_userid($this->student1->id);
        $this->assertCount(1, $contextlist);
        $this->assertEquals($context->id, $contextlist->current()->id);
        
        // Test marker1 context.
        $contextlist = provider::get_contexts_for_userid($this->marker1->id);
        $this->assertCount(1, $contextlist);
        $this->assertEquals($context->id, $contextlist->current()->id);
        
        // Test marker2 context.
        $contextlist = provider::get_contexts_for_userid($this->marker2->id);
        $this->assertCount(1, $contextlist);
        $this->assertEquals($context->id, $contextlist->current()->id);
        
        // Test ratifier context.
        $contextlist = provider::get_contexts_for_userid($this->ratifier->id);
        $this->assertCount(1, $contextlist);
        $this->assertEquals($context->id, $contextlist->current()->id);
    }

    /**
     * Test for provider::export_user_data()
     */
    public function test_export_user_data() {
        $cm = get_coursemodule_from_instance('assign', $this->assign->id);
        $context = context_module::instance($cm->id);
        
        // Export student1 data.
        $this->export_context_data_for_user($this->student1->id, $context, 'local_doublemarking');
        $writer = writer::with_context($context);
        $this->assertTrue($writer->has_any_data());
        
        $data = $writer->get_data(['Double Marking', 'Allocations']);
        $this->assertNotEmpty($data);
        $this->assertEquals(75.0, $data->marker1grade);
        $this->assertEquals(85.0, $data->marker2grade);
        $this->assertEquals(80.0, $data->finalgrade);
        $this->assertEquals('Feedback from first marker', $data->marker1feedback);
        $this->assertEquals('Feedback from second marker', $data->marker2feedback);
        $this->assertEquals('Final ratification comment', $data->ratificationcomment);
        
        // Export marker1 data.
        $this->export_context_data_for_user($this->marker1->id, $context, 'local_doublemarking');
        $writer = writer::with_context($context);
        $this->assertTrue($writer->has_any_data());
        
        $data = $writer->get_data(['Double Marking', 'Marker Allocations']);
        $this->assertNotEmpty($data);
        $this->assertCount(2, $data->allocations); // Should have 2 allocations as marker1
    }

    /**
     * Test for provider::delete_data_for_all_users_in_context()
     */
    public function test_delete_data_for_all_users_in_context() {
        global $DB;
        
        $cm = get_coursemodule_from_instance('assign', $this->assign->id);
        $context = context_module::instance($cm->id);
        
        // Confirm we have allocations.
        $count = $DB->count_records('local_doublemarking_alloc', ['assignmentid' => $this->assign->id]);
        $this->assertEquals(2, $count);
        
        // Delete all data for the context.
        provider::delete_data_for_all_users_in_context($context);
        
        // Confirm all allocations are deleted.
        $count = $DB->count_records('local_doublemarking_alloc', ['assignmentid' => $this->assign->id]);
        $this->assertEquals(0, $count);
    }

    /**
     * Test for provider::delete_data_for_user()
     */
    public function test_delete_data_for_user() {
        global $DB;
        
        $cm = get_coursemodule_from_instance('assign', $this->assign->id);
        $context = context_module::instance($cm->id);
        
        // Confirm we have allocations.
        $count = $DB->count_records('local_doublemarking_alloc', [
            'assignmentid' => $this->assign->id,
            'userid' => $this->student1->id
        ]);
        $this->assertEquals(1, $count);
        
        // Delete student1 data.
        $contextlist = new approved_contextlist(
            $this->student1,
            'local_doublemarking',
            [$context->id]
        );
        provider::delete_data_for_user($contextlist);
        
        // Confirm student1 allocations are deleted.
        $count = $DB->count_records('local_doublemarking_alloc', [
            'assignmentid' => $this->assign->id,
            'userid' => $this->student1->id
        ]);
        $this->assertEquals(0, $count);
        
        // Confirm student2 allocations still exist.
        $count = $DB->count_records('local_doublemarking_alloc', [
            'assignmentid' => $this->assign->id,
            'userid' => $this->student2->id
        ]);
        $this->assertEquals(1, $count);
    }

    /**
     * Test for provider::get_users_in_context()
     */
    public function test_get_users_in_context() {
        $cm = get_coursemodule_from_instance('assign', $this->assign->id);
        $context = context_module::instance($cm->id);
        
        $userlist = new \core_privacy\local\request\userlist($context, 'local_doublemarking');
        provider::get_users_in_context($userlist);
        
        $users = $userlist->get_userids();
        $this->assertCount(5, $users);
        $this->assertContains($this->student1->id, $users);
        $this->assertContains($this->student2->id, $users);
        $this->assertContains($this->marker1->id, $users);
        $this->assertContains($this->marker2->id, $users);
        $this->assertContains($this->ratifier->id, $users);
    }

    /**
     * Test for provider::delete_data_for_users()
     */
    public function test_delete_data_for_users() {
        global $DB;
        
        $cm = get_coursemodule_from_instance('assign', $this->assign->id);
        $context = context_module::instance($cm->id);
        
        // Confirm we have allocations.
        $count = $DB->count_records('local_doublemarking_alloc', ['assignmentid' => $this->assign->id]);
        $this->assertEquals(2, $count);
        
        // Create a userlist with student1 and student2.
        $userlist = new \core_privacy\local\request\approved_userlist($context, 'local_doublemarking', [
            $this->student1->id, 
            $this->student2->id
        ]);
        
        // Delete data for the userlist.
        provider::delete_data_for_users($userlist);
        
        // Confirm student allocations are deleted.
        $count = $DB->count_records('local_doublemarking_alloc', [
            'assignmentid' => $this->assign->id,
            'userid' => $this->student1->id
        ]);
        $this->assertEquals(0, $count);
        
        $count = $DB->count_records('local_doublemarking_alloc', [
            'assignmentid' => $this->assign->id,
            'userid' => $this->student2->id
        ]);
        $this->assertEquals(0, $count);
    }
    
    /**
     * Test deletion of marker data.
     */
    public function test_delete_marker_data() {
        global $DB;
        
        $cm = get_coursemodule_from_instance('assign', $this->assign->id);
        $context = context_module::instance($cm->id);
        
        // Confirm we have marker1 as a marker in both allocations.
        $count = $DB->count_records('local_doublemarking_alloc', [
            'assignmentid' => $this->assign->id,
            'marker1' => $this->marker1->id
        ]);
        $this->assertEquals(2, $count);
        
        // Delete marker1 data.
        $contextlist = new approved_contextlist(
            $this->marker1,
            'local_doublemarking',
            [$context->id]
        );
        provider::delete_data_for_user($contextlist);
        
        // Confirm marker1 is anonymized in allocations but allocations still exist.
        $allocations = $DB->get_records('local_doublemarking_alloc', [
            'assignmentid' => $this->assign->id
        ]);
        $this->assertCount(2, $allocations);
        
        foreach ($allocations as $allocation) {
            $this->assertNotEquals($this->marker1->id, $allocation->marker1);
            
            // The marker fields should be set to 0 to anonymize the marker.
            if ($allocation->marker1 == 0) {
                // Marker's feedback should be anonymized as well.
                $this->assertEquals('', $allocation->marker1feedback);
            }
        }
    }
    
    /**
     * Test deletion of ratifier data.
     */
    public function test_delete_ratifier_data() {
        global $DB;
        
        $cm = get_coursemodule_from_instance('assign', $this->assign->id);
        $context = context_module::instance($cm->id);
        
        // Confirm we have the ratifier in both allocations.
        $count = $DB->count_records('local_doublemarking_alloc', [
            'assignmentid' => $this->assign->id,
            'ratifier' => $this->ratifier->id
        ]);
        $this->assertEquals(2, $count);
        
        // Delete ratifier data.
        $contextlist = new approved_contextlist(
            $this->ratifier,
            'local_doublemarking',
            [$context->id]
        );
        provider::delete_data_for_user($contextlist);
        
        // Confirm ratifier is anonymized in allocations but allocations still exist.
        $allocations = $DB->get_records('local_doublemarking_alloc', [
            'assignmentid' => $this->assign->id
        ]);
        $this->assertCount(2, $allocations);
        
        foreach ($allocations as $allocation) {
            $this->assertNotEquals($this->ratifier->id, $allocation->ratifier);
            
            // The ratifier field should be set to 0 to anonymize the ratifier.
            if ($allocation->ratifier == 0) {
                // Ratification comment should be anonymized as well.
                $this->assertEquals('', $allocation->ratificationcomment);
            }
        }
    }
    
    /**
     * Test verification of privacy data export format.
     */
    public function test_privacy_data_export_format() {
        $cm = get_coursemodule_from_instance('assign', $this->assign->id);
        $context = context_module::instance($cm->id);
        
        // Export student1 data.
        $this->export_context_data_for_user($this->student1->id, $context, 'local_doublemarking');
        $writer = writer::with_context($context);
        
        // Verify student data structure.
        $studentdata = $writer->get_data(['Double Marking', 'Allocations']);
        $this->assertObjectHasAttribute('marker1grade', $studentdata);
        $this->assertObjectHasAttribute('marker2grade', $studentdata);
        $this->assertObjectHasAttribute('finalgrade', $studentdata);
        $this->assertObjectHasAttribute('marker1feedback', $studentdata);
        $this->assertObjectHasAttribute('marker2feedback', $studentdata);
        $this->assertObjectHasAttribute('ratificationcomment', $studentdata);
        
        // Export marker1 data.
        $this->export_context_data_for_user($this->marker1->id, $context, 'local_doublemarking');
        $writer = writer::with_context($context);
        
        // Verify marker data structure.
        $markerdata = $writer->get_data(['Double Marking', 'Marker Allocations']);
        $this->assertObjectHasAttribute('allocations', $markerdata);
        $this->assertIsArray($markerdata->allocations);
        $this->assertCount(2, $markerdata->allocations);
        
        $allocation = reset($markerdata->allocations);
        $this->assertObjectHasAttribute('student', $allocation);
        $this->assertObjectHasAttribute('grade', $allocation);
        $this->assertObjectHasAttribute('feedback', $allocation);
        
        // Export ratifier data.
        $this->export_context_data_for_user($this->ratifier->id, $context, 'local_doublemarking');
        $writer = writer::with_context($context);
        
        // Verify ratifier data structure.
        $ratifierdata = $writer->get_data(['Double Marking', 'Ratified Allocations']);
        $this->assertObjectHasAttribute('allocations', $ratifierdata);
        $this->assertIsArray($ratifierdata->allocations);
        $this->assertCount(2, $ratifierdata->allocations);
        
        $allocation = reset($ratifierdata->allocations);
        $this->assertObjectHasAttribute('student', $allocation);
        $this->assertObjectHasAttribute('finalgrade', $allocation);
        $this->assertObjectHasAttribute('ratificationcomment', $allocation);
    }
    
    /**
     * Test blind marking scenarios for privacy.
     */
    public function test_blind_marking_privacy() {
        global $DB;
        
        // Create a new assignment with blind marking enabled.
        $generator = $this->getDataGenerator();
        $assigngen = $generator->get_plugin_generator('mod_assign');
        
        $blindassign = $assigngen->create_instance([
            'course' => $this->course->id,
            'name' => 'Blind Marking Assignment',
            'grade' => 100,
            'blindmarking' => 1
        ]);
        
        // Set up double marking with blind marking.
        $blindalloc = new stdClass();
        $blindalloc->assignmentid = $blindassign->id;
        $blindalloc->userid = 0; // Assignment level settings.
        $blindalloc->blindsetting = 1; // Blind marking.
        $blindalloc->markshidden = 1; // Marks hidden.
        $blindalloc->timecreated = time();
        $blindalloc->timemodified = time();
        
        $DB->insert_record('local_doublemarking_alloc', $blindalloc);
        
        // Create allocations.
        $this->create_allocation($blindassign->id, $this->student1->id, $this->marker1->id, $this->marker2->id, $this->ratifier->id);
        
        $cm = get_coursemodule_from_instance('assign', $blindassign->id);
        $context = context_module::instance($cm->id);
        
        // Export marker1 data.
        $this->export_context_data_for_user($this->marker1->id, $context, 'local_doublemarking');
        $writer = writer::with_context($context);
        
        // In blind marking, marker data should still be exported but with anonymized student information.
        $markerdata = $writer->get_data(['Double Marking', 'Marker Allocations']);
        $this->assertObjectHasAttribute('allocations', $markerdata);
        $this->assertIsArray($markerdata->allocations);
        $this->assertCount(1, $markerdata->allocations);
        
        $allocation = reset($markerdata->allocations);
        
        // Student information should be anonymized.
        $this->assertObjectHasAttribute('student', $allocation);
        $this->assertStringContainsString('Anonymous', $allocation->student);
        
        // Export marker2 data.
        $this->export_context_data_for_user($this->marker2->id, $context, 'local_doublemarking');
        $writer = writer::with_context($context);
        
        // In blind marking, marker2 should not see marker1's identity.
        $markerdata = $writer->get_data(['Double Marking', 'Marker Allocations']);
        $allocation = reset($markerdata->allocations);
        $this->assertObjectNotHasAttribute('othermarker', $allocation);
    }
    
    /**
     * Test helper method for verifying allocation data.
     *
     * @param stdClass $allocation The allocation record to verify
     * @param array $expected Expected values for key fields
     */
    protected function verify_allocation($allocation, $expected) {
        foreach ($expected as $field => $value) {
            $this->assertEquals($value, $allocation->$field, "Field $field has unexpected value");
        }
    }
}


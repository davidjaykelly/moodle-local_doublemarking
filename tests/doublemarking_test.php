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
 * Unit tests for double marking functionality.
 *
 * @package    local_doublemarking
 * @copyright  2025 Your Name <your@email.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/mod/assign/tests/generator.php');
require_once($CFG->dirroot . '/local/doublemarking/classes/external.php');

/**
 * Class local_doublemarking_testcase
 *
 * @package    local_doublemarking
 * @copyright  2025 Your Name <your@email.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class local_doublemarking_testcase extends advanced_testcase {

    /**
     * Set up for each test.
     */
    public function setUp(): void {
        $this->resetAfterTest();
        $this->setAdminUser();
    }

    /**
     * Create an assignment for testing with double marking enabled.
     *
     * @param int $blindsetting Blind marking setting (0=none, 1=blind, 2=double-blind)
     * @param bool $markshidden Whether marks are hidden until both markers complete
     * @return array The assignment, course module, and course
     */
    protected function create_assignment_with_double_marking($blindsetting = 0, $markshidden = false) {
        global $DB;

        // Create course and assignment
        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $assigngen = $generator->get_plugin_generator('mod_assign');
        
        // Create assignment instance
        $assigninstance = $assigngen->create_instance([
            'course' => $course->id,
            'name' => 'Test Assignment',
            'grade' => 100
        ]);
        
        // Get assignment course module
        $cm = get_coursemodule_from_instance('assign', $assigninstance->id);
        
        // Create double marking allocation for assignment
        $allocation = new stdClass();
        $allocation->assignmentid = $assigninstance->id;
        $allocation->userid = 0; // 0 for assignment-level settings
        $allocation->blindsetting = $blindsetting;
        $allocation->markshidden = $markshidden ? 1 : 0;
        $allocation->timecreated = time();
        $allocation->timemodified = time();
        
        $DB->insert_record('local_doublemarking_alloc', $allocation);
        
        return [
            'assignment' => $assigninstance,
            'cm' => $cm,
            'course' => $course
        ];
    }
    
    /**
     * Create users for testing double marking.
     *
     * @return array Array of users (student, marker1, marker2, ratifier)
     */
    protected function create_users() {
        $generator = $this->getDataGenerator();
        
        // Create student
        $student = $generator->create_user(['firstname' => 'Student', 'lastname' => 'User']);
        
        // Create first marker
        $marker1 = $generator->create_user(['firstname' => 'First', 'lastname' => 'Marker']);
        
        // Create second marker
        $marker2 = $generator->create_user(['firstname' => 'Second', 'lastname' => 'Marker']);
        
        // Create ratifier
        $ratifier = $generator->create_user(['firstname' => 'Ratifier', 'lastname' => 'User']);
        
        return [
            'student' => $student,
            'marker1' => $marker1,
            'marker2' => $marker2,
            'ratifier' => $ratifier
        ];
    }
    
    /**
     * Assign roles and enroll users for testing.
     *
     * @param array $users Array of users
     * @param array $assignment Assignment data
     */
    protected function assign_roles_and_enroll($users, $assignment) {
        global $DB;
        
        $generator = $this->getDataGenerator();
        
        // Enroll users in course
        $studentrole = $DB->get_record('role', ['shortname' => 'student']);
        $teacherrole = $DB->get_record('role', ['shortname' => 'teacher']);
        $managerrole = $DB->get_record('role', ['shortname' => 'manager']);
        
        $generator->enrol_user($users['student']->id, $assignment['course']->id, $studentrole->id);
        $generator->enrol_user($users['marker1']->id, $assignment['course']->id, $teacherrole->id);
        $generator->enrol_user($users['marker2']->id, $assignment['course']->id, $teacherrole->id);
        $generator->enrol_user($users['ratifier']->id, $assignment['course']->id, $managerrole->id);
        
        // Assign double marking capabilities
        $context = context_module::instance($assignment['cm']->id);
        
        assign_capability('local/doublemarking:mark1', CAP_ALLOW, $teacherrole->id, $context);
        assign_capability('local/doublemarking:mark2', CAP_ALLOW, $teacherrole->id, $context);
        assign_capability('local/doublemarking:ratify', CAP_ALLOW, $managerrole->id, $context);
        assign_capability('local/doublemarking:viewall', CAP_ALLOW, $managerrole->id, $context);
        
        // Assign specific marker capabilities
        role_assign('teacher', $users['marker1']->id, $context);
        role_assign('teacher', $users['marker2']->id, $context);
        role_assign('manager', $users['ratifier']->id, $context);
    }
    
    /**
     * Create marker allocation for a student.
     *
     * @param array $users Array of users
     * @param array $assignment Assignment data
     * @return stdClass The allocation record
     */
    protected function create_marker_allocation($users, $assignment) {
        global $DB;
        
        $allocation = new stdClass();
        $allocation->assignmentid = $assignment['assignment']->id;
        $allocation->userid = $users['student']->id;
        $allocation->marker1 = $users['marker1']->id;
        $allocation->marker2 = $users['marker2']->id;
        $allocation->timecreated = time();
        $allocation->timemodified = time();
        
        $allocationid = $DB->insert_record('local_doublemarking_alloc', $allocation);
        
        return $DB->get_record('local_doublemarking_alloc', ['id' => $allocationid]);
    }
    
    /**
     * Add grades for student from markers.
     *
     * @param array $users Array of users
     * @param array $assignment Assignment data
     * @param float $grade1 Grade from first marker
     * @param float $grade2 Grade from second marker
     */
    protected function add_grades($users, $assignment, $grade1, $grade2) {
        global $DB;
        
        $cm = $assignment['cm'];
        $assigninstance = $assignment['assignment'];
        
        // Create assign instance
        $context = context_module::instance($cm->id);
        $assign = new assign($context, $cm, $assignment['course']);
        
        // Add grade from first marker
        $this->setUser($users['marker1']);
        $grade = $assign->get_user_grade($users['student']->id, true);
        $grade->grade = $grade1;
        $assign->update_grade($grade);
        
        // Add grade from second marker
        $this->setUser($users['marker2']);
        $grade = $assign->get_user_grade($users['student']->id, true);
        $grade->grade = $grade2;
        $assign->update_grade($grade);
    }
    
    /**
     * Test grade calculation and difference detection.
     */
    public function test_grade_calculation_and_difference() {
        global $DB;
        
        // Set grade difference threshold
        set_config('grade_difference_threshold', 10, 'local_doublemarking');
        
        // Create assignment with double marking
        $assignment = $this->create_assignment_with_double_marking();
        
        // Create users and roles
        $users = $this->create_users();
        $this->assign_roles_and_enroll($users, $assignment);
        
        // Create marker allocation
        $allocation = $this->create_marker_allocation($users, $assignment);
        
        // Test case 1: Small grade difference (below threshold)
        $this->add_grades($users, $assignment, 70, 75);
        
        $this->setUser($users['ratifier']);
        $result = local_doublemarking_external::get_allocation($assignment['assignment']->id, $users['student']->id);
        
        $this->assertEquals(5, $result['gradedifference']);
        $this->assertFalse($result['thresholdexceeded']);
        
        // Test case 2: Large grade difference (above threshold)
        $this->add_grades($users, $assignment, 60, 80);
        
        $result = local_doublemarking_external::get_allocation($assignment['assignment']->id, $users['student']->id);
        
        $this->assertEquals(20, $result['gradedifference']);
        $this->assertTrue($result['thresholdexceeded']);
    }
    
    /**
     * Test visibility rules for blind marking.
     */
    public function test_blind_marking_visibility() {
        global $DB;
        
        // Create assignment with blind marking
        $assignment = $this->create_assignment_with_double_marking(1, true);
        
        // Create users and roles
        $users = $this->create_users();
        $this->assign_roles_and_enroll($users, $assignment);
        
        // Create marker allocation
        $allocation = $this->create_marker_allocation($users, $assignment);
        
        // Add grades
        $this->add_grades($users, $assignment, 70, 80);
        
        // Test visibility for marker1
        $this->setUser($users['marker1']);
        $result = local_doublemarking_external::get_allocation($assignment['assignment']->id, $users['student']->id);
        
        // Marker1 should see their own grade but not marker2's grade
        $this->assertArrayHasKey('marker1grade', $result);
        $this->assertArrayNotHasKey('marker2grade', $result);
        
        // Test visibility for marker2
        $this->setUser($users['marker2']);
        $result = local_doublemarking_external::get_allocation($assignment['assignment']->id, $users['student']->id);
        
        // Marker2 should see their own grade but not marker1's grade
        $this->assertArrayHasKey('marker2grade', $result);
        $this->assertArrayNotHasKey('marker1grade', $result);
        
        // Test visibility for ratifier
        $this->setUser($users['ratifier']);
        $result = local_doublemarking_external::get_allocation($assignment['assignment']->id, $users['student']->id);
        
        // Ratifier with viewall capability should see both grades
        $this->assertArrayHasKey('marker1grade', $result);
        $this->assertArrayHasKey('marker2grade', $result);
    }
    
    /**
     * Test capability checks for double marking.
     */
    public function test_capability_checks() {
        global $DB;
        
        // Create assignment with double marking
        $assignment = $this->create_assignment_with_double_marking();
        
        // Create users and roles
        $users = $this->create_users();
        $this->assign_roles_and_enroll($users, $assignment);
        
        // Create marker allocation
        $allocation = $this->create_marker_allocation($users, $assignment);
        
        // Test if marker1 has mark1 capability
        $this->setUser($users['marker1']);
        $context = context_module::instance($assignment['cm']->id);
        $this->assertTrue(has_capability('local/doublemarking:mark1', $context));
        
        // Test if marker2 has mark2 capability
        $this->setUser($users['marker2']);
        $this->assertTrue(has_capability('local/doublemarking:mark2', $context));
        
        // Test if ratifier has ratify capability
        $this->setUser($users['ratifier']);
        $this->assertTrue(has_capability('local/doublemarking:ratify', $context));
        $this->assertTrue(has_capability('local/doublemarking:viewall', $context));
        
        // Test if student doesn't have marking capabilities
        $this->setUser($users['student']);
        $this->assertFalse(has_capability('local/doublemarking:mark1', $context));
        $this->assertFalse(has_capability('local/doublemarking:mark2', $context));
        $this->assertFalse(has_capability('local/doublemarking:ratify', $context));
    }
    
    /**
     * Test ratification workflow validation.
     */
    public function test_ratification_workflow() {
        global $DB;
        
        // Set grade difference threshold
        set_config('grade_difference_threshold', 10, 'local_doublemarking');
        
        // Create assignment with double marking
        $assignment = $this->create_assignment_with_double_marking();
        
        // Create users and roles
        $users = $this->create_users();
        $this->assign_roles_and_enroll($users, $assignment);
        
        // Create marker allocation
        $allocation = $this->create_marker_allocation($users, $assignment);
        
        // Add grades with significant difference
        $this->add_grades($users, $assignment, 60, 85);
        
        // Check that difference is detected and exceeds threshold
        $this->setUser($users['ratifier']);
        $result = local_doublemarking_external::get_allocation($assignment['assignment']->id, $users['student']->id);
        
        $this->assertEquals(25, $result['gradedifference']);
        $this->assertTrue($result['thresholdexceeded']);
        
        // Test ratification with custom grade
        $result = local_doublemarking_external::save_ratification(
            $assignment['assignment']->id,
            $users['student']->id,
            'custom',
            75.5,
            'Ratified with custom grade'
        );
        
        $this->assertTrue($result['status']);
        
        // Check that final grade is correctly saved
        $updated = $DB->get_record('local_doublemarking_alloc', ['id' => $allocation->id]);
        $this->assertEquals(75.5, $updated->finalgrade);
        $this->assertEquals('Ratified with custom grade', $updated->ratificationcomment);
        $this->assertEquals($users['ratifier']->id, $updated->ratifier);
    }
    
    /**
     * Test ratification with different grade choices.
     */
    public function test_ratification_grade_choices() {
        global $DB;
        
        // Create assignment with double marking
        $assignment = $this->create_assignment_with_double_marking();
        
        // Create users and roles
        $users = $this->create_users();
        $this->assign_roles_and_enroll($users, $assignment);
        
        // Create marker allocation
        $allocation = $this->create_marker_allocation($users, $assignment);
        
        // Add grades
        $this->add_grades($users, $assignment, 70, 80);
        
        $this->setUser($users['ratifier']);
        
        // Test ratification with marker1's grade
        $result = local_doublemarking_external::save_ratification(
            $assignment['assignment']->id,
            $users['student']->id,
            'marker1',
            0, // This value should be ignored when using marker1 choice
            'Using marker 1 grade'
        );
        
        $this->assertTrue($result['status']);
        
        // Check that marker1's grade is used
        $updated = $DB->get_record('local_doublemarking_alloc', ['id' => $allocation->id]);
        $this->assertEquals(70, $updated->finalgrade);
        
        // Test ratification with marker2's grade
        $result = local_doublemarking_external::save_ratification(
            $assignment['assignment']->id,
            $users['student']->id,
            'marker2',
            0, // This value should be ignored when using marker2 choice
            'Using marker 2 grade'
        );
        
        $this->assertTrue($result['status']);
        
        // Check that marker2's grade is used
        $updated = $DB->get_record('local_doublemarking_alloc', ['id' => $allocation->id]);
        $this->assertEquals(80, $updated->finalgrade);
        
        // Test ratification with average grade
        $result = local_doublemarking_external::save_ratification(
            $assignment['assignment']->id,
            $users['student']->id,
            'average',
            0, // This value should be ignored when using average choice
            'Using average grade'
        );
        
        $this->assertTrue($result['status']);
        
        // Check that average grade is used (70 + 80) / 2 = 75
        $updated = $DB->get_record('local_doublemarking_alloc', ['id' => $allocation->id]);
        $this->assertEquals(75, $updated->finalgrade);
    }
    
    /**
     * Test error handling for invalid ratification attempts.
     */
    public function test_invalid_ratification_attempts() {
        global $DB;
        
        // Create assignment with double marking
        $assignment = $this->create_assignment_with_double_marking();
        
        // Create users and roles
        $users = $this->create_users();
        $this->assign_roles_and_enroll($users, $assignment);
        
        // Create marker allocation
        $allocation = $this->create_marker_allocation($users, $assignment);
        
        // Add grades
        $this->add_grades($users, $assignment, 70, 80);
        
        // Test: Non-ratifier cannot ratify
        $this->setUser($users['marker1']);
        
        try {
            $result = local_doublemarking_external::save_ratification(
                $assignment['assignment']->id,
                $users['student']->id,
                'custom',
                75,
                'This should fail'
            );
            $this->fail('Exception expected when non-ratifier tries to ratify');
        } catch (Exception $e) {
            $this->assertInstanceOf('required_capability_exception', $e);
        }
        
        // Test: Cannot ratify with invalid grade choice
        $this->setUser($users['ratifier']);
        
        try {
            $result = local_doublemarking_external::save_ratification(
                $assignment['assignment']->id,
                $users['student']->id,
                'invalid_choice',
                75,
                'This should fail'
            );
            $this->fail('Exception expected when using invalid grade choice');
        } catch (Exception $e) {
            $this->assertInstanceOf('invalid_parameter_exception', $e);
        }
        
        // Test: Cannot ratify with invalid assignment ID
        try {
            $result = local_doublemarking_external::save_ratification(
                9999, // Non-existent assignment ID
                $users['student']->id,
                'custom',
                75,
                'This should fail'
            );
            $this->fail('Exception expected when using invalid assignment ID');
        } catch (Exception $e) {
            $this->assertStringContainsString('does not exist', $e->getMessage());
        }
    }
    
    /**
     * Test grade visibility after ratification.
     */
    public function test_grade_visibility_after_ratification() {
        global $DB;
        
        // Create assignment with blind marking and hidden marks
        $assignment = $this->create_assignment_with_double_marking(1, true);
        
        // Create users and roles
        $users = $this->create_users();
        $this->assign_roles_and_enroll($users, $assignment);
        
        // Create marker allocation
        $allocation = $this->create_marker_allocation($users, $assignment);
        
        // Add grades
        $this->add_grades($users, $assignment, 70, 80);
        
        // Ratify the grades
        $this->setUser($users['ratifier']);
        $result = local_doublemarking_external::save_ratification(
            $assignment['assignment']->id,
            $users['student']->id,
            'custom',
            75,
            'Ratified with custom grade'
        );
        
        // Test visibility for marker1 after ratification
        $this->setUser($users['marker1']);
        $result = local_doublemarking_external::get_allocation($assignment['assignment']->id, $users['student']->id);
        
        // Marker1 should see final grade after ratification
        $this->assertArrayHasKey('finalgrade', $result);
        $this->assertEquals(75, $result['finalgrade']);
        
        // Test visibility for marker2 after ratification
        $this->setUser($users['marker2']);
        $result = local_doublemarking_external::get_allocation($assignment['assignment']->id, $users['student']->id);
        
        // Marker2 should see final grade after ratification
        $this->assertArrayHasKey('finalgrade', $result);
        $this->assertEquals(75, $result['finalgrade']);
        
        // Test visibility for student after ratification
        $this->setUser($users['student']);
        
        // We'd typically test a student view API here, but for this test suite we're focusing on marker views
        // In a real implementation, there would be student-facing methods to test
    }
    
    /**
     * Helper method to perform ratification.
     *
     * @param array $users Array of users
     * @param array $assignment Assignment data
     * @param stdClass $allocation Marker allocation record
     * @param string $gradeChoice Grade choice (marker1, marker2, average, custom)
     * @param float $customGrade Custom grade value (used only when gradeChoice is 'custom')
     * @param string $comment Ratification comment
     * @return array Result of ratification
     */
    protected function perform_ratification($users, $assignment, $allocation, $gradeChoice, $customGrade = 0, $comment = '') {
        $this->setUser($users['ratifier']);
        
        return local_doublemarking_external::save_ratification(
            $assignment['assignment']->id,
            $users['student']->id,
            $gradeChoice,
            $customGrade,
            $comment
        );
    }
    
    /**
     * Helper method to check ratification results.
     *
     * @param int $allocationId Allocation ID to check
     * @param float $expectedGrade Expected final grade
     * @param int $expectedRatifier Expected ratifier user ID
     * @return void
     */
    protected function assert_ratification_result($allocationId, $expectedGrade, $expectedRatifier) {
        global $DB;
        
        $allocation = $DB->get_record('local_doublemarking_alloc', ['id' => $allocationId]);
        
        $this->assertEquals($expectedGrade, $allocation->finalgrade);
        $this->assertEquals($expectedRatifier, $allocation->ratifier);
    }
}


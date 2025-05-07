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
 * CLI script to create test data for double marking.
 *
 * @package    local_doublemarking
 * @copyright  2025 Your Name <your@email.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('CLI_SCRIPT', true);

require(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/clilib.php');
require_once($CFG->dirroot . '/mod/assign/locallib.php');

// Get cli options.
list($options, $unrecognized) = cli_get_params(
    [
        'help' => false,
        'course' => null,
        'assignment' => null,
        'blindsetting' => 0,
        'markshidden' => 0,
    ],
    [
        'h' => 'help',
        'c' => 'course',
        'a' => 'assignment',
        'b' => 'blindsetting',
        'm' => 'markshidden',
    ]
);

// Display help if requested or if required parameters are missing.
if ($options['help'] || !$options['course'] || !$options['assignment']) {
    echo "
CLI script to create test data for double marking.

Usage:
    php create_test_data.php --course=<course_id> --assignment=<assignment_id> [options]

Options:
    -h, --help              Print this help
    -c, --course            Course ID (required)
    -a, --assignment        Assignment ID (required)
    -b, --blindsetting      Blind marking setting (0=none, 1=blind, 2=double-blind, default: 0)
    -m, --markshidden       Hide marks until both markers complete (0=no, 1=yes, default: 0)

Example:
    php create_test_data.php --course=2 --assignment=1
    php create_test_data.php --course=2 --assignment=1 --blindsetting=1 --markshidden=1
";
    exit(0);
}

// Get parameters.
$courseid = $options['course'];
$assignmentid = $options['assignment'];
$blindsetting = $options['blindsetting'];
$markshidden = $options['markshidden'];

// Validate parameters.
$course = $DB->get_record('course', ['id' => $courseid], '*', MUST_EXIST);
$assignment = $DB->get_record('assign', ['id' => $assignmentid, 'course' => $courseid], '*', MUST_EXIST);
$cm = get_coursemodule_from_instance('assign', $assignment->id, $course->id, false, MUST_EXIST);
$context = context_module::instance($cm->id);

cli_heading("Creating test data for double marking in assignment '{$assignment->name}' in course '{$course->fullname}'");

// Create assignment-level settings.
cli_heading("Setting up assignment-level settings");
$assignsettings = new stdClass();
$assignsettings->assignmentid = $assignment->id;
$assignsettings->userid = 0; // 0 indicates assignment-level settings
$assignsettings->blindsetting = $blindsetting;
$assignsettings->markshidden = $markshidden;
$assignsettings->timecreated = time();
$assignsettings->timemodified = time();

// Delete existing assignment settings.
$DB->delete_records('local_doublemarking_alloc', ['assignmentid' => $assignment->id, 'userid' => 0]);
$settingsid = $DB->insert_record('local_doublemarking_alloc', $assignsettings);
echo "Assignment settings created with ID $settingsid\n";

// Get users in the course.
$students = get_enrolled_users($context, 'mod/assign:submit');
$teachers = get_enrolled_users($context, 'mod/assign:grade');

if (count($students) < 6) {
    cli_problem("Not enough students in the course (need at least 6 for all scenarios)");
    exit(1);
}

if (count($teachers) < 3) {
    cli_problem("Not enough teachers in the course (need at least 3)");
    exit(1);
}

// Select random teachers as markers and ratifier.
shuffle($teachers);
$marker1 = array_shift($teachers);
$marker2 = array_shift($teachers);
$ratifier = array_shift($teachers);

echo "Selected marker 1: {$marker1->firstname} {$marker1->lastname} (ID: {$marker1->id})\n";
echo "Selected marker 2: {$marker2->firstname} {$marker2->lastname} (ID: {$marker2->id})\n";
echo "Selected ratifier: {$ratifier->firstname} {$ratifier->lastname} (ID: {$ratifier->id})\n";

// Assign capabilities.
cli_heading("Assigning capabilities");

// Check if marker1 role exists or create it
$marker1role = $DB->get_record('role', ['shortname' => 'doublemarker1']);
if (!$marker1role) {
    $marker1role = create_role('Double Marking - Marker 1', 'doublemarker1', 'Can act as first marker in double marking');
} else {
    $marker1role = $marker1role->id;
}
assign_capability('local/doublemarking:mark1', CAP_ALLOW, $marker1role, $context->id);
role_assign($marker1role, $marker1->id, $context->id);
echo "Assigned marker1 capability to {$marker1->firstname} {$marker1->lastname}\n";

// Check if marker2 role exists or create it
$marker2role = $DB->get_record('role', ['shortname' => 'doublemarker2']);
if (!$marker2role) {
    $marker2role = create_role('Double Marking - Marker 2', 'doublemarker2', 'Can act as second marker in double marking');
} else {
    $marker2role = $marker2role->id;
}
assign_capability('local/doublemarking:mark2', CAP_ALLOW, $marker2role, $context->id);
role_assign($marker2role, $marker2->id, $context->id);
echo "Assigned marker2 capability to {$marker2->firstname} {$marker2->lastname}\n";

// Check if ratifier role exists or create it
$ratifierrole = $DB->get_record('role', ['shortname' => 'doubleratifier']);
if (!$ratifierrole) {
    $ratifierrole = create_role('Double Marking - Ratifier', 'doubleratifier', 'Can ratify grades in double marking');
} else {
    $ratifierrole = $ratifierrole->id;
}
assign_capability('local/doublemarking:ratify', CAP_ALLOW, $ratifierrole, $context->id);
assign_capability('local/doublemarking:viewgrades', CAP_ALLOW, $ratifierrole, $context->id);
assign_capability('local/doublemarking:viewmarkers', CAP_ALLOW, $ratifierrole, $context->id);
assign_capability('local/doublemarking:viewdifferences', CAP_ALLOW, $ratifierrole, $context->id);
role_assign($ratifierrole, $ratifier->id, $context->id);
echo "Assigned ratifier capability to {$ratifier->firstname} {$ratifier->lastname}\n";

// Create an assign instance for grade manipulation.
$assign = new assign($context, $cm, $course);

// Create different test scenarios.
cli_heading("Creating test scenarios");

// Scenario 1: Both markers graded, matching grades.
$student1 = array_shift($students);
$allocation1 = create_marker_allocation($assignment->id, $student1->id, $marker1->id, $marker2->id);
create_grade($assign, $student1->id, $marker1->id, 75.0, 'Grade from first marker - scenario 1');
create_grade($assign, $student1->id, $marker2->id, 75.0, 'Grade from second marker - scenario 1');
echo "Scenario 1 created: Both markers graded, matching grades for student {$student1->firstname} {$student1->lastname}\n";

// Scenario 2: Both markers graded, small difference within threshold.
$student2 = array_shift($students);
$allocation2 = create_marker_allocation($assignment->id, $student2->id, $marker1->id, $marker2->id);
create_grade($assign, $student2->id, $marker1->id, 70.0, 'Grade from first marker - scenario 2');
create_grade($assign, $student2->id, $marker2->id, 75.0, 'Grade from second marker - scenario 2');
echo "Scenario 2 created: Both markers graded, small difference for student {$student2->firstname} {$student2->lastname}\n";

// Scenario 3: Both markers graded, large difference exceeding threshold.
$student3 = array_shift($students);
$allocation3 = create_marker_allocation($assignment->id, $student3->id, $marker1->id, $marker2->id);
create_grade($assign, $student3->id, $marker1->id, 60.0, 'Grade from first marker - scenario 3');
create_grade($assign, $student3->id, $marker2->id, 85.0, 'Grade from second marker - scenario 3');
echo "Scenario 3 created: Both markers graded, large difference for student {$student3->firstname} {$student3->lastname}\n";

// Scenario 4: Only first marker has graded.
$student4 = array_shift($students);
$allocation4 = create_marker_allocation($assignment->id, $student4->id, $marker1->id, $marker2->id);
create_grade($assign, $student4->id, $marker1->id, 80.0, 'Grade from first marker - scenario 4');
echo "Scenario 4 created: Only first marker has graded for student {$student4->firstname} {$student4->lastname}\n";

// Scenario 5: No markers have graded yet.
$student5 = array_shift($students);
$allocation5 = create_marker_allocation($assignment->id, $student5->id, $marker1->id, $marker2->id);
echo "Scenario 5 created: No markers have graded yet for student {$student5->firstname} {$student5->lastname}\n";

// Scenario 6: Grade ratified after disagreement.
if (!empty($students)) {
    $student6 = array_shift($students);
    $allocation6 = create_marker_allocation($assignment->id, $student6->id, $marker1->id, $marker2->id);
    create_grade($assign, $student6->id, $marker1->id, 65.0, 'Grade from first marker - scenario 6');
    create_grade($assign, $student6->id, $marker2->id, 85.0, 'Grade from second marker - scenario 6');
    ratify_grade($assignment->id, $student6->id, $ratifier->id, 75.0, 'Ratified grade - compromise between markers');
    echo "Scenario 6 created: Grade ratified after disagreement for student {$student6->firstname} {$student6->lastname}\n";
} else {
    cli_problem("Skipping scenario 6: Not enough students");
}

cli_heading("Test data creation completed successfully");

/**
 * Helper function to create a marker allocation.
 *
 * @param int $assignmentid The assignment ID
 * @param int $studentid The student ID
 * @param int $marker1id First marker ID
 * @param int $marker2id Second marker ID
 * @return stdClass The allocation record
 */
function create_marker_allocation($assignmentid, $studentid, $marker1id, $marker2id) {
    global $DB;
    
    // Delete any existing allocations for this student in this assignment.
    $DB->delete_records('local_doublemarking_alloc', ['assignmentid' => $assignmentid, 'userid' => $studentid]);
    
    $allocation = new stdClass();
    $allocation->assignmentid = $assignmentid;
    $allocation->userid = $studentid;
    $allocation->marker1 = $marker1id;
    $allocation->marker2 = $marker2id;
    $allocation->timecreated = time();
    $allocation->timemodified = time();
    
    $id = $DB->insert_record('local_doublemarking_alloc', $allocation);
    return $DB->get_record('local_doublemarking_alloc', ['id' => $id]);
}

/**
 * Helper function to create a grade.
 *
 * @param assign $assign The assignment instance
 * @param int $studentid The student ID
 * @param int $markerid The marker ID
 * @param float $grade The grade value
 * @param string $feedback The feedback
 */
function create_grade($assign, $studentid, $markerid, $grade, $feedback) {
    global $DB;
    
    // Get any existing grade record or create a new one.
    $grade_obj = $assign->get_user_grade($studentid, true);
    $grade_obj->grader = $markerid;
    $grade_obj->grade = $grade;
    $grade_obj->feedback = $feedback;
    $grade_obj->timemodified = time();
    
    $assign->update_grade($grade_obj);
    
    // Update the corresponding marker grade in the doublemarking_alloc table.
    $allocation = $DB->get_record('local_doublemarking_alloc', 
        ['assignmentid' => $assign->get_instance()->id, 'userid' => $studentid]);
    
    if ($allocation) {
        if ($allocation->marker1 == $markerid) {
            $allocation->marker1grade = $grade;
            $allocation->marker1feedback = $feedback;
        } else if ($allocation->marker2 == $markerid) {
            $allocation->marker2grade = $grade;
            $allocation->marker2feedback = $feedback;
        }
        
        $allocation->timemodified = time();
        $DB->update_record('local_doublemarking_alloc', $allocation);
    }
}

/**
 * Helper function to ratify a grade.
 *
 * @param int $assignmentid The assignment ID
 * @param int $studentid The student ID
 * @param int $ratifierid The ratifier ID
 * @param float $finalgrade The final grade
 * @param string $comment The ratification comment
 */
function ratify_grade($assignmentid, $studentid, $ratifierid, $finalgrade, $comment) {
    global $DB;
    
    $allocation = $DB->get_record('local_doublemarking_alloc', [
        'assignmentid' => $assignmentid,
        'userid' => $studentid
    ]);
    
    if ($allocation) {
        $allocation->ratifier = $ratifierid;
        $allocation->finalgrade = $finalgrade;
        $allocation->ratificationcomment = $comment;
        $allocation->timemodified = time();
        
        $DB->update_record('local_doublemarking_alloc', $allocation);
        
        // Also update the final grade in the assignment gradebook
        $cm = get_coursemodule_from_instance('assign', $assignmentid);
        $context = context_module::instance($cm->id);
        $course = get_course($cm->course);
        $assign = new assign($context, $cm, $course);
        
        $gradeobj = $assign->get_user_grade($studentid, true);
        $gradeobj->grade = $finalgrade;
        $gradeobj->grader = $ratifierid;
        $assign->update_grade($gradeobj);
    }
}


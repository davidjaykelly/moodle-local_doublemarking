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
 * CLI script to create test users for double marking
 *
 * @package    local_doublemarking
 * @copyright  2025 Your Name <your@email.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('CLI_SCRIPT', true);

require(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/clilib.php');
require_once($CFG->dirroot . '/user/lib.php');
require_once($CFG->libdir . '/enrollib.php');
require_once($CFG->dirroot . '/course/lib.php');

// Now get cli options.
list($options, $unrecognized) = cli_get_params(
    [
        'courseid' => false,
        'students' => 10,
        'teachers' => 4,
        'password' => 'Test1234!',
        'verbose' => false,
        'help' => false,
    ],
    [
        'c' => 'courseid',
        's' => 'students',
        't' => 'teachers',
        'p' => 'password',
        'v' => 'verbose',
        'h' => 'help',
    ]
);

if ($unrecognized) {
    $unrecognized = implode("\n  ", $unrecognized);
    cli_error(get_string('cliunknowoption', 'admin', $unrecognized));
}

if ($options['help'] || !$options['courseid']) {
    $help = "Create test users for double marking and enroll them in a course.

Options:
-c, --courseid      Course ID where users will be enrolled (required)
-s, --students      Number of students to create (default: 10)
-t, --teachers      Number of teachers to create (default: 4)
-p, --password      Password for all created users (default: 'Test1234!')
-v, --verbose       Show more output
-h, --help          Print this help

Example:
\$ php create_test_users.php --courseid=2 --students=15 --teachers=5

";

    echo $help;
    exit(0);
}

$courseid = $options['courseid'];
$numstudents = $options['students'];
$numteachers = $options['teachers'];
$password = $options['password'];
$verbose = $options['verbose'];

// Validate course exists
$course = $DB->get_record('course', array('id' => $courseid), '*', MUST_EXIST);
echo "Creating test users for course: {$course->fullname} (ID: {$courseid})\n";

// Get required roles
$studentrole = $DB->get_record('role', array('shortname' => 'student'), '*', MUST_EXIST);
$teacherrole = $DB->get_record('role', array('shortname' => 'editingteacher'), '*', MUST_EXIST);
$context = context_course::instance($courseid);

// Create manual enrollment instance if it doesn't exist
$enrol = enrol_get_plugin('manual');
$instances = enrol_get_instances($courseid, true);
$manualinstance = null;
foreach ($instances as $instance) {
    if ($instance->enrol === 'manual') {
        $manualinstance = $instance;
        break;
    }
}

if (empty($manualinstance)) {
    $instanceid = $enrol->add_default_instance($course);
    if ($instanceid === false) {
        $instanceid = $enrol->add_instance($course);
    }
    $manualinstance = $DB->get_record('enrol', array('id' => $instanceid));
}

// Get capabilities for double marking
$dm_mark1_cap = $DB->get_record('capabilities', array('name' => 'local/doublemarking:mark1'), '*', MUST_EXIST);
$dm_mark2_cap = $DB->get_record('capabilities', array('name' => 'local/doublemarking:mark2'), '*', MUST_EXIST);
$dm_allocate_cap = $DB->get_record('capabilities', array('name' => 'local/doublemarking:allocate'), '*', MUST_EXIST);

echo "\nCreating $numstudents students...\n";
$students = [];
for ($i = 1; $i <= $numstudents; $i++) {
    // Create a user
    $username = 'teststudent' . $i;
    $email = $username . '@example.com';
    
    // Check if user already exists
    $user = $DB->get_record('user', array('username' => $username));
    if (!$user) {
        $user = create_user_record($username, $password);
        $user->firstname = 'Student';
        $user->lastname = $i;
        $user->email = $email;
        $DB->update_record('user', $user);
        if ($verbose) {
            echo "  Created user: $username\n";
        }
    } else {
        if ($verbose) {
            echo "  User $username already exists\n";
        }
    }
    
    // Enroll user in course
    $enrol->enrol_user($manualinstance, $user->id, $studentrole->id);
    if ($verbose) {
        echo "  Enrolled $username in course {$course->shortname}\n";
    }
    
    $students[] = $user;
}

echo "\nCreating $numteachers teachers...\n";
$teachers = [];
for ($i = 1; $i <= $numteachers; $i++) {
    // Create a user
    $username = 'testteacher' . $i;
    $email = $username . '@example.com';
    
    // Check if user already exists
    $user = $DB->get_record('user', array('username' => $username));
    if (!$user) {
        $user = create_user_record($username, $password);
        $user->firstname = 'Teacher';
        $user->lastname = $i;
        $user->email = $email;
        $DB->update_record('user', $user);
        if ($verbose) {
            echo "  Created user: $username\n";
        }
    } else {
        if ($verbose) {
            echo "  User $username already exists\n";
        }
    }
    
    // Enroll user in course
    $enrol->enrol_user($manualinstance, $user->id, $teacherrole->id);
    if ($verbose) {
        echo "  Enrolled $username in course {$course->shortname}\n";
    }
    
    // Assign double marking capabilities
    role_assign($teacherrole->id, $user->id, $context->id);
    assign_capability('local/doublemarking:mark1', CAP_ALLOW, $teacherrole->id, $context->id);
    assign_capability('local/doublemarking:mark2', CAP_ALLOW, $teacherrole->id, $context->id);
    
    // Make the first teacher also able to allocate markers
    if ($i == 1) {
        assign_capability('local/doublemarking:allocate', CAP_ALLOW, $teacherrole->id, $context->id);
        if ($verbose) {
            echo "  Gave $username marker allocation capability\n";
        }
    }
    
    $teachers[] = $user;
}

// Summary
echo "\nCreated " . count($students) . " students and " . count($teachers) . " teachers in course '{$course->fullname}'.\n";
echo "All users have password: $password\n";

// First teacher as allocator
echo "\nFor testing, log in as " . $teachers[0]->username . " to allocate markers.\n";
echo "Visit: " . $CFG->wwwroot . "/local/doublemarking/allocate.php?id=[assignment-cmid]\n";

exit(0);


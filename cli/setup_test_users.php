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
 * CLI script to create test users for double marking.
 *
 * @package    local_doublemarking
 * @copyright  2025 Your Name <your@email.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('CLI_SCRIPT', true);

require(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/clilib.php');
require_once($CFG->dirroot . '/user/lib.php');

$courseid = 2; // "Double Marking Test" course
$course = $DB->get_record('course', ['id' => $courseid]);
if (!$course) {
    cli_error("Course not found");
}

$context = context_course::instance($courseid);

// Create student role if it doesn't exist
$studentrole = $DB->get_record('role', ['shortname' => 'student']);
if (!$studentrole) {
    cli_error("Student role not found");
}

// Create and enroll 6 students
for ($i = 1; $i <= 6; $i++) {
    $username = "teststudent$i";
    
    // Check if user already exists
    $user = $DB->get_record('user', ['username' => $username]);
    if (!$user) {
        $user = new stdClass();
        $user->username = $username;
        $user->firstname = "Test Student";
        $user->lastname = "$i";
        $user->email = "teststudent$i@example.com";
        $user->password = "Test123!";
        $user->confirmed = 1;
        $user->mnethostid = $CFG->mnet_localhost_id;
        $user->auth = 'manual';
        $user->lang = 'en';
        
        $user->id = user_create_user($user);
        echo "Created user: $username\n";
    } else {
        echo "User already exists: $username\n";
    }
    
    // Enroll user in course
    if (!is_enrolled($context, $user)) {
        $enrol = enrol_get_plugin('manual');
        $instances = enrol_get_instances($course->id, true);
        $manualinstance = null;
        foreach ($instances as $instance) {
            if ($instance->enrol === 'manual') {
                $manualinstance = $instance;
                break;
            }
        }
        if ($manualinstance) {
            $enrol->enrol_user($manualinstance, $user->id, $studentrole->id);
            echo "Enrolled user: $username\n";
        } else {
            echo "No manual enrollment method found for the course\n";
        }
    } else {
        echo "User already enrolled: $username\n";
    }
}

echo "Setup completed\n";


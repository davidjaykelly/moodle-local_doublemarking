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
 * Marker allocation page for double marking plugin
 * 
 * @package    local_doublemarking
 * @copyright  2025 Your Name <your@email.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->dirroot . '/mod/assign/locallib.php');

// Get parameters
$cmid = required_param('id', PARAM_INT); // Course module ID (from the URL)
$action = optional_param('action', '', PARAM_ALPHA); // Action to perform
$userid = optional_param('userid', 0, PARAM_INT); // User ID for allocation
$marker1 = optional_param('marker1', 0, PARAM_INT); // First marker
$marker2 = optional_param('marker2', 0, PARAM_INT); // Second marker

// Get course module info and context
$cm = get_coursemodule_from_id('assign', $cmid, 0, false, MUST_EXIST);
$course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
$context = context_module::instance($cm->id);

// Set up page parameters - set context first to avoid warnings
$PAGE->set_context($context);
$PAGE->set_url('/local/doublemarking/allocate.php', array('id' => $cmid));
$PAGE->set_title(get_string('pluginname', 'local_doublemarking') . ': ' . format_string($course->fullname));
$PAGE->set_heading(format_string($course->fullname));

// Check permissions
require_login($course, false, $cm);
require_capability('local/doublemarking:allocate', $context);

// Initialize output
$output = $PAGE->get_renderer('core');

// Get assignment instance
$assign = new assign($context, $cm, $course);

// Handle actions (basic placeholder for now)
$message = '';
if ($action === 'allocate' && confirm_sesskey()) {
    // Basic allocation action - would be expanded in a real implementation
    $record = new stdClass();
    $record->assignmentid = $cm->instance;
    $record->userid = $userid;
    $record->marker1 = $marker1;
    $record->marker2 = $marker2;
    $record->timecreated = time();
    $record->timemodified = time();
    
    // Check if allocation already exists
    $existing = $DB->get_record('local_doublemarking_alloc', 
        array('assignmentid' => $cm->instance, 'userid' => $userid));
    
    if ($existing) {
        // Update existing allocation
        $record->id = $existing->id;
        $DB->update_record('local_doublemarking_alloc', $record);
        $message = get_string('allocationupdated', 'local_doublemarking');
    } else {
        // Create new allocation
        $DB->insert_record('local_doublemarking_alloc', $record);
        $message = get_string('allocationcreated', 'local_doublemarking');
    }
}

// Start output
echo $output->header();
echo $output->heading(get_string('allocatemarkers', 'local_doublemarking'));

// Show message (if any)
if (!empty($message)) {
    echo $output->notification($message, 'notifysuccess');
}

// Show assignment information
echo html_writer::tag('p', '<strong>' . format_string($cm->name) . '</strong>', ['class' => 'h5']);

// Basic content - placeholder for now
echo html_writer::tag('div', get_string('allocatemarkersdescription', 'local_doublemarking', $cm->name), 
    array('class' => 'alert alert-info'));

// Get all enrolled students with submit capability
$students = get_enrolled_users($context, 'mod/assign:submit', 0, 'u.*', null, 0, 0, true);
if (!empty($students)) {
    $table = new html_table();
    $table->head = array(
        get_string('student', 'local_doublemarking'),
        get_string('marker1', 'local_doublemarking'),
        get_string('marker2', 'local_doublemarking'),
        get_string('actions', 'local_doublemarking')
    );
    $table->data = array();

    // Get all teachers (people who can mark)
    $teachers = get_enrolled_users($context, 'local/doublemarking:mark1');
    $teacheroptions = array(0 => get_string('selectmarker', 'local_doublemarking'));
    foreach ($teachers as $teacher) {
        $teacheroptions[$teacher->id] = fullname($teacher);
    }

    // Get existing allocations
    $allocations = $DB->get_records('local_doublemarking_alloc', 
        array('assignmentid' => $cm->instance), '', 'userid, marker1, marker2');

    foreach ($students as $student) {
        $row = array();
        $row[] = fullname($student);
        
        // Get current allocation if it exists
        $marker1value = isset($allocations[$student->id]) ? $allocations[$student->id]->marker1 : 0;
        $marker2value = isset($allocations[$student->id]) ? $allocations[$student->id]->marker2 : 0;
        
        // Create allocation form with inline layout
        $formurl = new moodle_url('/local/doublemarking/allocate.php', 
            array('id' => $cmid, 'action' => 'allocate', 'userid' => $student->id));
        $formattrs = array('method' => 'post', 'class' => 'form-inline');
        
        // Put the form elements in the marker cells
        $form_hidden = html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()));
        
        // First marker dropdown
        $marker1select = html_writer::select($teacheroptions, 'marker1', $marker1value, false, ['class' => 'form-control mr-2']);
        $row[] = $marker1select;
        
        // Second marker dropdown
        $marker2select = html_writer::select($teacheroptions, 'marker2', $marker2value, false, ['class' => 'form-control mr-2']);
        $row[] = $marker2select;
        
        // Action cell with submit button and hidden input
        $form = html_writer::start_tag('form', $formattrs + array('action' => $formurl));
        $form .= $form_hidden;
        $form .= html_writer::empty_tag('input', array(
            'type' => 'hidden', 
            'name' => 'marker1', 
            'class' => 'marker1-hidden-input',
            'value' => $marker1value
        ));
        $form .= html_writer::empty_tag('input', array(
            'type' => 'hidden', 
            'name' => 'marker2', 
            'class' => 'marker2-hidden-input',
            'value' => $marker2value
        ));
        $form .= html_writer::empty_tag('input', array(
            'type' => 'submit', 
            'value' => get_string('save', 'core'), 
            'class' => 'btn btn-primary btn-sm'
        ));
        $form .= html_writer::end_tag('form');
        $row[] = $form;
        
        $table->data[] = $row;
    }

    echo html_writer::table($table);
    
    // Add JavaScript to sync dropdown values to hidden inputs on change
    $js = "
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var marker1Selects = document.querySelectorAll('select[name=\"marker1\"]');
            var marker2Selects = document.querySelectorAll('select[name=\"marker2\"]');
            
            marker1Selects.forEach(function(select, index) {
                select.addEventListener('change', function() {
                    document.querySelectorAll('.marker1-hidden-input')[index].value = this.value;
                });
            });
            
            marker2Selects.forEach(function(select, index) {
                select.addEventListener('change', function() {
                    document.querySelectorAll('.marker2-hidden-input')[index].value = this.value;
                });
            });
        });
    </script>
    ";
    echo $js;
    
} else {
    echo $output->notification(get_string('nostudents', 'local_doublemarking'), 'notifyinfo');
}

// Complete output
echo $output->footer();


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

namespace local_doublemarking\hook;

defined('MOODLE_INTERNAL') || die();

/**
 * Hook handler for assignment module integration.
 *
 * @package    local_doublemarking
 * @copyright  2025 Your Name <your@email.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_assign {

    /**
     * Handle submission form viewed event.
     *
     * @param \mod_assign\hook\submission_form_viewed $hook
     */
    public static function submission_form_viewed(\mod_assign\hook\submission_form_viewed $hook) {
        global $PAGE;
        
        // Add any necessary UI elements or modify the submission form
        $context = $hook->get_context();
        if (!has_capability('local/doublemarking:mark1', $context) &&
            !has_capability('local/doublemarking:mark2', $context)) {
            return;
        }
        
        // Add necessary JavaScript and CSS
        $PAGE->requires->js_call_amd('local_doublemarking/form', 'init');
    }

    /**
     * Handle grading form viewed event.
     *
     * @param \mod_assign\hook\grading_form_viewed $hook
     */
    public static function grading_form_viewed(\mod_assign\hook\grading_form_viewed $hook) {
        global $PAGE, $DB;
        
        $context = $hook->get_context();
        $assignment = $hook->get_assignment();
        $userid = $hook->get_user_id();
        
        // Check if this assignment has double marking enabled
        $allocation = $DB->get_record('local_doublemarking_alloc', [
            'assignmentid' => $assignment->get_instance()->id,
            'userid' => $userid
        ]);
        
        if (!$allocation) {
            return;
        }
        
        // Add double marking interface elements
        $PAGE->requires->js_call_amd('local_doublemarking/grading', 'init', [
            'allocation' => $allocation
        ]);
    }

    /**
     * Handle before save grade event.
     *
     * @param \mod_assign\hook\before_save_grade $hook
     */
    public static function before_save_grade(\mod_assign\hook\before_save_grade $hook) {
        global $USER, $DB;
        
        $data = $hook->get_gradingdata();
        $assignment = $hook->get_assignment();
        
        // Check if this is a double-marked assignment
        $allocation = $DB->get_record('local_doublemarking_alloc', [
            'assignmentid' => $assignment->get_instance()->id,
            'userid' => $data->userid
        ]);
        
        if (!$allocation) {
            return;
        }
        
        // Determine if current user is marker1 or marker2
        if ($USER->id === $allocation->marker1) {
            $DB->set_field('local_doublemarking_alloc', 'marker1grade', $data->grade, ['id' => $allocation->id]);
        } else if ($USER->id === $allocation->marker2) {
            $DB->set_field('local_doublemarking_alloc', 'marker2grade', $data->grade, ['id' => $allocation->id]);
        }
    }

    /**
     * Handle after save grade event.
     *
     * @param \mod_assign\hook\after_save_grade $hook
     */
    public static function after_save_grade(\mod_assign\hook\after_save_grade $hook) {
        global $DB;
        
        $assignment = $hook->get_assignment();
        $userid = $hook->get_user_id();
        
        // Check if both markers have completed
        $allocation = $DB->get_record('local_doublemarking_alloc', [
            'assignmentid' => $assignment->get_instance()->id,
            'userid' => $userid
        ]);
        
        if (!$allocation || !$allocation->marker1grade || !$allocation->marker2grade) {
            return;
        }
        
        // Trigger grade comparison and notification if needed
        self::compare_grades($allocation);
    }

    /**
     * Handle before HTTP headers event.
     *
     * @param \core\hook\output\before_http_headers $hook
     */
    public static function before_http_headers(\core\hook\output\before_http_headers $hook) {
        global $PAGE, $COURSE;
        
        // Add any necessary headers or early page modifications
        if ($PAGE->cm && $PAGE->cm->modname === 'assign') {
            // Add specific headers or early modifications for assignment pages
        }
    }

    /**
     * Compare grades and handle any significant differences.
     *
     * @param \stdClass $allocation The marking allocation record
     */
    private static function compare_grades($allocation) {
        global $DB;
        
        // Calculate grade difference and handle accordingly
        $difference = abs($allocation->marker1grade - $allocation->marker2grade);
        $threshold = get_config('local_doublemarking', 'grade_difference_threshold');
        
        if ($difference > $threshold) {
            // Notify relevant users of grade discrepancy
            self::notify_grade_difference($allocation);
        }
    }

    /**
     * Notify relevant users of grade differences.
     *
     * @param \stdClass $allocation The marking allocation record
     */
    private static function notify_grade_difference($allocation) {
        global $DB;
        
        // Implementation for notification system
        // This would typically create notifications for ratifiers
        // and possibly the markers themselves
    }
}

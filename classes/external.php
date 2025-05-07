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
 * External services for double marking plugin.
 *
 * @package    local_doublemarking
 * @copyright  2025 Your Name <your@email.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/externallib.php');
require_once($CFG->dirroot . '/mod/assign/locallib.php');

/**
 * Double marking external services class.
 *
 * @package    local_doublemarking
 * @copyright  2025 Your Name <your@email.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class local_doublemarking_external extends external_api {

    /**
     * Returns description of get_settings parameters.
     *
     * @return external_function_parameters
     */
    public static function get_settings_parameters() {
        return new external_function_parameters([
            'assignmentid' => new external_value(PARAM_INT, 'The assignment ID')
        ]);
    }

    /**
     * Returns description of get_settings return values.
     *
     * @return external_single_structure
     */
    public static function get_settings_returns() {
        return new external_single_structure([
            'gradedifferencethreshold' => new external_value(PARAM_FLOAT, 'Grade difference threshold'),
            'blindsetting' => new external_value(PARAM_INT, 'Blind marking setting (0=none, 1=blind, 2=double-blind)'),
            'markshidden' => new external_value(PARAM_INT, 'Whether marks are hidden until both markers complete')
        ]);
    }

    /**
     * Get double marking settings for an assignment.
     *
     * @param int $assignmentid The assignment ID
     * @return array The settings
     */
    public static function get_settings($assignmentid) {
        global $DB;
        
        // Parameter validation
        $params = self::validate_parameters(self::get_settings_parameters(), [
            'assignmentid' => $assignmentid
        ]);
        
        // Get course and context
        $assignment = $DB->get_record('assign', ['id' => $params['assignmentid']], '*', MUST_EXIST);
        $cm = get_coursemodule_from_instance('assign', $assignment->id, $assignment->course);
        $context = context_module::instance($cm->id);
        
        // Capability check
        require_capability('mod/assign:view', $context);
        
        // Get settings from DB
        $settings = $DB->get_record('local_doublemarking_alloc', [
            'assignmentid' => $params['assignmentid'],
            'userid' => 0  // Assignment-level settings
        ]);
        
        if (!$settings) {
            // Use default settings if none found
            $gradedifferencethreshold = get_config('local_doublemarking', 'grade_difference_threshold');
            $blindsetting = get_config('local_doublemarking', 'default_blind_setting');
            $markshidden = get_config('local_doublemarking', 'default_marks_hidden');
        } else {
            $gradedifferencethreshold = get_config('local_doublemarking', 'grade_difference_threshold');
            $blindsetting = $settings->blindsetting;
            $markshidden = $settings->markshidden;
        }
        
        return [
            'gradedifferencethreshold' => (float) $gradedifferencethreshold,
            'blindsetting' => (int) $blindsetting,
            'markshidden' => (int) $markshidden
        ];
    }

    /**
     * Returns description of get_allocation parameters.
     *
     * @return external_function_parameters
     */
    public static function get_allocation_parameters() {
        return new external_function_parameters([
            'assignmentid' => new external_value(PARAM_INT, 'The assignment ID'),
            'userid' => new external_value(PARAM_INT, 'The user ID')
        ]);
    }

    /**
     * Returns description of get_allocation return values.
     *
     * @return external_single_structure
     */
    public static function get_allocation_returns() {
        return new external_single_structure([
            'marker1' => new external_value(PARAM_INT, 'First marker ID', VALUE_OPTIONAL),
            'marker2' => new external_value(PARAM_INT, 'Second marker ID', VALUE_OPTIONAL),
            'marker1grade' => new external_value(PARAM_FLOAT, 'First marker grade', VALUE_OPTIONAL),
            'marker2grade' => new external_value(PARAM_FLOAT, 'Second marker grade', VALUE_OPTIONAL),
            'marker1timemodified' => new external_value(PARAM_INT, 'First marker grading time', VALUE_OPTIONAL),
            'marker2timemodified' => new external_value(PARAM_INT, 'Second marker grading time', VALUE_OPTIONAL),
            'finalgrade' => new external_value(PARAM_FLOAT, 'Final grade', VALUE_OPTIONAL),
            'ratificationcomment' => new external_value(PARAM_TEXT, 'Ratification comment', VALUE_OPTIONAL),
            'gradedifference' => new external_value(PARAM_FLOAT, 'Grade difference', VALUE_OPTIONAL),
            'thresholdexceeded' => new external_value(PARAM_BOOL, 'Whether threshold is exceeded', VALUE_OPTIONAL),
            'ismarker1' => new external_value(PARAM_BOOL, 'Whether current user is first marker'),
            'ismarker2' => new external_value(PARAM_BOOL, 'Whether current user is second marker'),
            'isratifier' => new external_value(PARAM_BOOL, 'Whether current user is a ratifier'),
            'canviewmarkers' => new external_value(PARAM_BOOL, 'Whether current user can view marker identities'),
            'canviewgrades' => new external_value(PARAM_BOOL, 'Whether current user can view grades')
        ]);
    }

    /**
     * Get marker allocation information for a user.
     *
     * @param int $assignmentid The assignment ID
     * @param int $userid The user ID
     * @return array The allocation information
     */
    public static function get_allocation($assignmentid, $userid) {
        global $DB, $USER;
        
        // Parameter validation
        $params = self::validate_parameters(self::get_allocation_parameters(), [
            'assignmentid' => $assignmentid,
            'userid' => $userid
        ]);
        
        // Get course and context
        $assignment = $DB->get_record('assign', ['id' => $params['assignmentid']], '*', MUST_EXIST);
        $cm = get_coursemodule_from_instance('assign', $assignment->id, $assignment->course);
        $context = context_module::instance($cm->id);
        
        // Capability check
        require_capability('mod/assign:view', $context);
        
        // Get assignment settings
        $settings = $DB->get_record('local_doublemarking_alloc', [
            'assignmentid' => $params['assignmentid'],
            'userid' => 0  // Assignment-level settings
        ]);
        
        if (!$settings) {
            $blindsetting = get_config('local_doublemarking', 'default_blind_setting');
            $markshidden = get_config('local_doublemarking', 'default_marks_hidden');
        } else {
            $blindsetting = $settings->blindsetting;
            $markshidden = $settings->markshidden;
        }
        
        // Get user allocation
        $allocation = $DB->get_record('local_doublemarking_alloc', [
            'assignmentid' => $params['assignmentid'],
            'userid' => $params['userid']
        ]);
        
        // Default return data
        $result = [
            'ismarker1' => false,
            'ismarker2' => false,
            'isratifier' => has_capability('local/doublemarking:ratify', $context),
            'canviewmarkers' => has_capability('local/doublemarking:viewmarkers', $context) || has_capability('local/doublemarking:viewall', $context),
            'canviewgrades' => has_capability('local/doublemarking:viewallgrades', $context) || has_capability('local/doublemarking:viewall', $context)
        ];
        
        // If user allocation exists, populate the result with allocation data
        if ($allocation) {
            $result['marker1'] = $allocation->marker1;
            $result['marker2'] = $allocation->marker2;
            
            // Check if current user is marker1 or marker2
            $result['ismarker1'] = ($allocation->marker1 == $USER->id);
            $result['ismarker2'] = ($allocation->marker2 == $USER->id);
            
            // Get marker grades from the assign_grades table
            $grades = $DB->get_records_sql(
                "SELECT u.id as userid, g.grader, g.grade, g.timemodified
                 FROM {user} u
                 LEFT JOIN {assign_grades} g ON u.id = g.userid AND g.assignment = :assignmentid
                 WHERE u.id = :userid
                 ORDER BY g.timemodified DESC",
                ['assignmentid' => $params['assignmentid'], 'userid' => $params['userid']]
            );
            
            // Process grades
            foreach ($grades as $grade) {
                if ($grade->grader == $allocation->marker1) {
                    $result['marker1grade'] = $grade->grade;
                    $result['marker1timemodified'] = $grade->timemodified;
                } else if ($grade->grader == $allocation->marker2) {
                    $result['marker2grade'] = $grade->grade;
                    $result['marker2timemodified'] = $grade->timemodified;
                }
            }
            
            // Get final ratified grade if it exists
            if (!empty($allocation->finalgrade)) {
                $result['finalgrade'] = $allocation->finalgrade;
                $result['ratificationcomment'] = $allocation->ratificationcomment;
            }
            
            // Calculate grade difference and check threshold (if both markers have graded)
            if (isset($result['marker1grade']) && isset($result['marker2grade'])) {
                $gradediff = abs($result['marker1grade'] - $result['marker2grade']);
                $result['gradedifference'] = $gradediff;
                
                // Get threshold from config
                $threshold = get_config('local_doublemarking', 'grade_difference_threshold');
                $result['thresholdexceeded'] = ($gradediff > $threshold);
            }
            
            // Apply blind marking and mark visibility settings
            if (!$result['canviewmarkers'] && $blindsetting > 0) {
                // Hide marker identities except for the markers themselves and users with viewall capability
                if (!$result['ismarker1'] && !$result['ismarker2'] && !has_capability('local/doublemarking:viewall', $context)) {
                    unset($result['marker1']);
                    unset($result['marker2']);
                }
            }
            
            // Apply marks hidden setting if enabled
            if ($markshidden && !$result['canviewgrades']) {
                // Hide marker grades except to the markers themselves (for their own grades) and users with viewall capability
                if ($result['ismarker1']) {
                    // Marker1 can see their own grade but not marker2's grade
                    unset($result['marker2grade']);
                    unset($result['marker2timemodified']);
                } else if ($result['ismarker2']) {
                    // Marker2 can see their own grade but not marker1's grade
                    unset($result['marker1grade']);
                    unset($result['marker1timemodified']);
                } else if (!has_capability('local/doublemarking:viewall', $context)) {
                    // Non-markers can't see any grades until both are complete
                    unset($result['marker1grade']);
                    unset($result['marker1timemodified']);
                    unset($result['marker2grade']);
                    unset($result['marker2timemodified']);
                }
                
                // Hide grade difference information for users without proper capabilities
                if (!has_capability('local/doublemarking:viewdifferences', $context) && 
                    !has_capability('local/doublemarking:viewall', $context)) {
                    unset($result['gradedifference']);
                    unset($result['thresholdexceeded']);
                }
            }
        }
        
        return $result;
    }
    
    /**
     * Returns description of save_ratification parameters.
     *
     * @return external_function_parameters
     */
    public static function save_ratification_parameters() {
        return new external_function_parameters([
            'assignmentid' => new external_value(PARAM_INT, 'The assignment ID'),
            'studentid' => new external_value(PARAM_INT, 'The student ID'),
            'gradechoice' => new external_value(PARAM_ALPHA, 'Grade choice (marker1, marker2, average, custom)'),
            'finalgrade' => new external_value(PARAM_FLOAT, 'Final grade value'),
            'ratificationcomment' => new external_value(PARAM_TEXT, 'Ratification comment', VALUE_DEFAULT, '')
        ]);
    }
    
    /**
     * Returns description of save_ratification return values.
     *
     * @return external_single_structure
     */
    public static function save_ratification_returns() {
        return new external_single_structure([
            'status' => new external_value(PARAM_BOOL, 'Status of the operation'),
            'message' => new external_value(PARAM_TEXT, 'Status message')
        ]);
    }
    
    /**
     * Save ratification data.
     *
     * @param int $assignmentid The assignment ID
     * @param int $studentid The student ID
     * @param string $gradechoice Grade choice (marker1, marker2, average, custom)
     * @param float $finalgrade Final grade value
     * @param string $ratificationcomment Ratification comment
     * @return array Operation status
     */
    public static function save_ratification($assignmentid, $studentid, $gradechoice, $finalgrade, $ratificationcomment = '') {
        global $DB, $USER;
        
        // Parameter validation
        $params = self::validate_parameters(self::save_ratification_parameters(), [
            'assignmentid' => $assignmentid,
            'studentid' => $studentid,
            'gradechoice' => $gradechoice,
            'finalgrade' => $finalgrade,
            'ratificationcomment' => $ratificationcomment
        ]);
        
        // Get course and context
        $assignment = $DB->get_record('assign', ['id' => $params['assignmentid']], '*', MUST_EXIST);
        $cm = get_coursemodule_from_instance('assign', $assignment->id, $assignment->course);
        $context = context_module::instance($cm->id);
        
        // Capability check
        require_capability('local/doublemarking:ratify', $context);
        
        // Get user allocation
        $allocation = $DB->get_record('local_doublemarking_alloc', [
            'assignmentid' => $params['assignmentid'],
            'userid' => $params['studentid']
        ]);
        
        if (!$allocation) {
            throw new moodle_exception('missingallocation', 'local_doublemarking');
        }
        
        // Get marker grades from assign_grades if we're using one of them
        if ($params['gradechoice'] == 'marker1' || $params['gradechoice'] == 'marker2' || $params['gradechoice'] == 'average') {
            $grades = $DB->get_records_sql(
                "SELECT u.id as userid, g.grader, g.grade
                 FROM {user} u
                 LEFT JOIN {assign_grades} g ON u.id = g.userid AND g.assignment = :assignmentid
                 WHERE u.id = :userid
                 ORDER BY g.timemodified DESC",
                ['assignmentid' => $params['assignmentid'], 'userid' => $params['studentid']]
            );
            
            $marker1grade = null;
            $marker2grade = null;
            
            foreach ($grades as $grade) {
                if ($grade->grader == $allocation->marker1) {
                    $marker1grade = $grade->grade;
                } else if ($grade->grader == $allocation->marker2) {
                    $marker2grade = $grade->grade;
                }
            }
            
            // Calculate final grade based on choice
            if ($params['gradechoice'] == 'marker1' && !is_null($marker1grade)) {
                $finalgrade = $marker1grade;
            } else if ($params['gradechoice'] == 'marker2' && !is_null($marker2grade)) {
                $finalgrade = $marker2grade;
            } else if ($params['gradechoice'] == 'average' && !is_null($marker1grade) && !is_null($marker2grade)) {
                $finalgrade = ($marker1grade + $marker2grade) / 2;
            } else {
                // Use provided finalgrade if we can't calculate based on choice
                $finalgrade = $params['finalgrade'];
            }
        } else {
            // Use custom grade
            $finalgrade = $params['finalgrade'];
        }
        
        // Update allocation record with final grade and ratification data
        $allocation->finalgrade = $finalgrade;
        $allocation->ratifier = $USER->id;
        $allocation->ratificationcomment = $params['ratificationcomment'];
        $allocation->timemodified = time();
        
        $DB->update_record('local_doublemarking_alloc', $allocation);
        
        // Update the assignment grade if integrated with Moodle gradebook
        if ($assignment->grade > 0) {
            $assignobj = new assign($context, $cm, $assignment->course);
            $grade = $assignobj->get_user_grade($params['studentid'], true);
            $grade->grade = $finalgrade;
            $grade->grader = $USER->id;
            
            if ($assignobj->update_grade($grade)) {
                return [
                    'status' => true,
                    'message' => get_string('gradesaved', 'local_doublemarking')
                ];
            } else {
                return [
                    'status' => false,
                    'message' => get_string('gradenotsaved', 'local_doublemarking')
                ];
            }
        }
        
        return [
            'status' => true,
            'message' => get_string('ratificationsaved', 'local_doublemarking')
        ];
    }
}


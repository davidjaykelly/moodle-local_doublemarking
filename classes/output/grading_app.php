        
        // Get all marker allocations for this assignment.
        $allocations = $DB->get_records('local_doublemarking_alloc', [
            'assignmentid' => $assignmentid
        ], '', 'userid, marker1, marker2');
        
        if ($allocations) {
            foreach ($allocations as $allocation) {
                if ($allocation->userid > 0) {  // Skip the global assignment settings record.
                    $this->markerallocations[$allocation->userid] = $allocation;
                }
            }
        }
    }

    /**
     * Get marker information for a specific user.
     *
     * @param int $userid The user ID to get markers for
     * @return stdClass Object containing marker information
     */
    private function get_marker_info($userid) {
        global $DB;
        
        $marker = new stdClass();
        $marker->hasmarkers = false;
        $marker->canviewmarkers = true;
        
        // If blind marking is enabled and the current user doesn't have permission to see markers,
        // return empty marker information.
        if ($this->blindsetting > 0 && !has_capability('local/doublemarking:viewmarkers', $this->assignment->get_context())) {
            $marker->canviewmarkers = false;
            return $marker;
        }
        
        // Check if this user has markers allocated.
        if (isset($this->markerallocations[$userid])) {
            $allocation = $this->markerallocations[$userid];
            $marker->hasmarkers = true;
            
            // Get marker 1 details.
            if (!empty($allocation->marker1)) {
                $marker1user = $DB->get_record('user', ['id' => $allocation->marker1], 'id, firstname, lastname');
                if ($marker1user) {
                    $marker->marker1id = $marker1user->id;
                    $marker->marker1name = fullname($marker1user);
                }
            }
            
            // Get marker 2 details.
            if (!empty($allocation->marker2)) {
                $marker2user = $DB->get_record('user', ['id' => $allocation->marker2], 'id, firstname, lastname');
                if ($marker2user) {
                    $marker->marker2id = $marker2user->id;
                    $marker->marker2name = fullname($marker2user);
                }
            }
            
            // Check if the current user is one of the markers.
            $marker->ismarker = false;
            $currentuserid = $this->assignment->get_user_id();
            if (($currentuserid && $allocation->marker1 == $currentuserid) || 
                ($currentuserid && $allocation->marker2 == $currentuserid)) {
                $marker->ismarker = true;
            }
            
            // Add any additional marker information.
            $this->add_grade_information($marker, $userid);
        }
        
        return $marker;
    }
    
    /**
     * Add grade information to marker data.
     *
     * @param stdClass $marker The marker object to add grade information to
     * @param int $userid The user ID to get grades for
     */
    private function add_grade_information(&$marker, $userid) {
        global $DB;
        
        $assignmentid = $this->assignment->get_instance()->id;
        
        // Get grades for this user.
        $grades = $DB->get_records('assign_grades', [
            'assignment' => $assignmentid,
            'userid' => $userid
        ], 'id DESC', 'id, grader, grade, timemodified');
        
        // If no grades yet, return.
        if (empty($grades)) {
            return;
        }
        
        $marker->hasgrades = false;
        
        // Check for grades from each marker.
        foreach ($grades as $grade) {
            if (!empty($marker->marker1id) && $grade->grader == $marker->marker1id) {
                $marker->marker1grade = $grade->grade;
                $marker->marker1timemodified = $grade->timemodified;
                $marker->hasgrades = true;
            } else if (!empty($marker->marker2id) && $grade->grader == $marker->marker2id) {
                $marker->marker2grade = $grade->grade;
                $marker->marker2timemodified = $grade->timemodified;
                $marker->hasgrades = true;
            }
        }
        
        // Calculate grade difference if both markers have graded.
        if (isset($marker->marker1grade) && isset($marker->marker2grade)) {
            $marker->gradedifference = abs($marker->marker1grade - $marker->marker2grade);
            
            // Check if difference exceeds threshold (if set).
            $thresholdrecord = $DB->get_record('config_plugins', [
                'plugin' => 'local_doublemarking',
                'name' => 'gradedifferencethreshold'
            ]);
            
            if ($thresholdrecord && $thresholdrecord->value > 0) {
                $marker->thresholdexceeded = ($marker->gradedifference > $thresholdrecord->value);
            }
        }
        
        // Hide marker grades from each other if markshidden is enabled.
        if ($this->markshidden) {
            $currentuserid = $this->assignment->get_user_id();
            
            // If current user is marker1, hide marker2's grade.
            if ($currentuserid && $currentuserid == $marker->marker1id && !has_capability('local/doublemarking:viewallgrades', $this->assignment->get_context())) {
                unset($marker->marker2grade);
                unset($marker->marker2timemodified);
                unset($marker->gradedifference);
                unset($marker->thresholdexceeded);
            }
            
            // If current user is marker2, hide marker1's grade.
            if ($currentuserid && $currentuserid == $marker->marker2id && !has_capability('local/doublemarking:viewallgrades', $this->assignment->get_context())) {
                unset($marker->marker1grade);
                unset($marker->marker1timemodified);
                unset($marker->gradedifference);
                unset($marker->thresholdexceeded);
            }
        }
    }

    /**
     * Export this class data as a flat list for rendering in a template.
     * Extends the parent method to add double marking information.
     *
     * @param renderer_base $output The current page renderer.
     * @return stdClass - Flat list of exported data.
     */
    public function export_for_template(renderer_base $output) {
        $export = parent::export_for_template($output);
        
        // Add double marking settings to the exported data.
        $export->doublemarking = new stdClass();
        $export->doublemarking->enabled = true;
        $export->doublemarking->blindsetting = $this->blindsetting;
        $export->doublemarking->markshidden = $this->markshidden;
        
        // Add marker information to each participant.
        foreach ($export->participants as $idx => $participant) {
            $markerinfo = $this->get_marker_info($participant->id);
            $export->participants[$idx]->markerinfo = $markerinfo;
        }
        
        // Add capabilities to determine what the user can see/do.
        $export->doublemarking->canviewmarkers = has_capability('local/doublemarking:viewmarkers', $this->assignment->get_context());
        $export->doublemarking->canviewallgrades = has_capability('local/doublemarking:viewallgrades', $this->assignment->get_context());
        $export->doublemarking->canviewdifferences = has_capability('local/doublemarking:viewdifferences', $this->assignment->get_context());
        
        return $export;
    }
}

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
 * Custom grading app renderer for double marking.
 *
 * @package    local_doublemarking
 * @copyright  2025 Your Name <your@email.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_doublemarking\output;

defined('MOODLE_INTERNAL') || die();

use renderer_base;
use stdClass;
use mod_assign\output\grading_app as mod_assign_grading_app;
use context_user;

/**
 * Custom grading app renderer class that adds double marking functionality
 *
 * @package    local_doublemarking
 * @copyright  2025 Your Name <your@email.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class grading_app extends mod_assign_grading_app {
    
    /**
     * Fetch all marker allocations for this assignment.
     * 
     * @return array Array of marker allocations indexed by userid
     */
    private function get_marker_allocations() {
        global $DB;
        
        $allocations = $DB->get_records('local_doublemarking_alloc', 
            ['assignmentid' => $this->assignment->get_instance()->id], 
            '', 'userid, marker1, marker2, marker1grade, marker2grade, finalgrade, blindsetting, markshidden');
        
        return $allocations;
    }
    
    /**
     * Get user details for a marker by ID.
     * 
     * @param int $markerid The marker's user ID
     * @param int $blindsetting The blind marking setting for this assignment
     * @return stdClass User details with appropriate fields for display
     */
    private function get_marker_details($markerid, $blindsetting) {
        global $DB, $USER;
        
        if (empty($markerid)) {
            return null;
        }
        
        $isblind = ($blindsetting > 0);
        $isdoubleblind = ($blindsetting == 2);
        
        // If double-blind and not the current user, return anonymous information
        if ($isdoubleblind && $markerid != $USER->id) {
            $marker = new stdClass();
            $marker->id = $markerid;
            $marker->fullname = get_string('anonymousmarker', 'local_doublemarking');
            return $marker;
        }
        
        // Get the actual marker details
        $marker = $DB->get_record('user', ['id' => $markerid], 'id, firstname, lastname, firstnamephonetic, lastnamephonetic, middlename, alternatename, picture, imagealt');
        
        if ($marker) {
            $marker->fullname = fullname($marker);
            
            // Add profile picture URL if not blind marking
            if (!$isblind) {
                $userpicture = new \user_picture($marker);
                $userpicture->size = 1; // Size f1
                $marker->profileimageurl = $userpicture->get_url($this->assignment->get_context())->out(false);
            }
        }
        
        return $marker;
    }

    /**
     * Export data for rendering in a template
     *
     * @param renderer_base $output The renderer
     * @return stdClass Data for use in a template
     */
    public function export_for_template(renderer_base $output) {
        global $USER;
        
        // Get the standard data from the parent class
        $data = parent::export_for_template($output);
        
        // Get assignment settings for double marking
        $assignid = $this->assignment->get_instance()->id;
        $dmconfig = \core_component::get_component_directory('local_doublemarking') !== null;
        
        if (!$dmconfig) {
            return $data;
        }
        
        // Get assignment-level settings
        $doublesettings = $this->get_double_marking_settings($assignid);
        $data->hasblindmarking = !empty($doublesettings->blindsetting);
        $data->hasdoubleblind = $doublesettings->blindsetting == 2;
        $data->hasmarkshidden = !empty($doublesettings->markshidden);
        
        // Get all marker allocations for this assignment
        $allocations = $this->get_marker_allocations();
        
        // Add marker information to each participant
        foreach ($data->participants as $key => $participant) {
            $studentid = $participant->id;
            $allocation = isset($allocations[$studentid]) ? $allocations[$studentid] : null;
            
            // Initialize marker information
            $participant->hasallocation = !empty($allocation);
            $participant->marker1 = null;
            $participant->marker2 = null;
            $participant->marker1grade = null;
            $participant->marker2grade = null;
            $participant->hasdifference = false;
            
            if (!empty($allocation)) {
                // Add marker details
                $participant->marker1 = $this->get_marker_details($allocation->marker1, $doublesettings->blindsetting);
                $participant->marker2 = $this->get_marker_details($allocation->marker2, $doublesettings->blindsetting);
                
                // Add grade information if the user is allowed to see it
                $canseegrades = !$doublesettings->markshidden || 
                    has_capability('local/doublemarking:viewall', $this->assignment->get_context()) ||
                    $USER->id == $allocation->marker1 || 
                    $USER->id == $allocation->marker2;
                
                if ($canseegrades) {
                    $participant->marker1grade = isset($allocation->marker1grade) ? $allocation->marker1grade : null;
                    $participant->marker2grade = isset($allocation->marker2grade) ? $allocation->marker2grade : null;
                    $participant->finalgrade = isset($allocation->finalgrade) ? $allocation->finalgrade : null;
                    
                    // Check for grade difference
                    if (!is_null($participant->marker1grade) && !is_null($participant->marker2grade)) {
                        $threshold = get_config('local_doublemarking', 'grade_difference_threshold');
                        $difference = abs($participant->marker1grade - $participant->marker2grade);
                        $participant->hasdifference = ($difference > $threshold);
                        $participant->gradedifference = $difference;
                    }
                }
                
                // Determine if current user is one of the markers
                $participant->ismarker1 = ($USER->id == $allocation->marker1);
                $participant->ismarker2 = ($USER->id == $allocation->marker2);
                $participant->ismarker = $participant->ismarker1 || $participant->ismarker2;
            }
            
            // Update the participant data
            $data->participants[$key] = $participant;
        }
        
        // Add capabilities
        $context = $this->assignment->get_context();
        $data->canallocate = has_capability('local/doublemarking:allocate', $context);
        $data->canviewall = has_capability('local/doublemarking:viewall', $context);
        
        // Override sort order to prioritize assigned students for markers
        // (Only if user is a marker and not an admin/manager)
        $ismarkeronly = has_capability('local/doublemarking:mark1', $context) || 
                       has_capability('local/doublemarking:mark2', $context);
        $isadmin = has_capability('moodle/site:config', $context);
        
        if ($ismarkeronly && !$isadmin) {
            // Sort participants to show assigned ones first
            usort($data->participants, function($a, $b) use ($USER) {
                $a_is_assigned = !empty($a->ismarker);
                $b_is_assigned = !empty($b->ismarker);
                
                if ($a_is_assigned && !$b_is_assigned) {
                    return -1;
                } else if (!$a_is_assigned && $b_is_assigned) {
                    return 1;
                }
                
                // If both or neither are assigned, use original order
                return 0;
            });
        }
        
        return $data;
    }
    
    /**
     * Gets the double marking settings for an assignment.
     *
     * @param int $assignid The assignment ID
     * @return stdClass The double marking settings
     */
    private function get_double_marking_settings($assignid) {
        global $DB;
        
        $settings = $DB->get_record('local_doublemarking_alloc', 
            ['assignmentid' => $assignid, 'userid' => 0]);
        
        if (!$settings) {
            // Use defaults if no settings found
            $settings = new stdClass();
            $settings->blindsetting = get_config('local_doublemarking', 'default_blind_setting');
            $settings->markshidden = get_config('local_doublemarking', 'default_marks_hidden');
        }
        
        return $settings;
    }
}


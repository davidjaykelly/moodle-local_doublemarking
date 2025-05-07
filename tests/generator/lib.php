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
 * Generator for the double marking plugin.
 *
 * @package    local_doublemarking
 * @copyright  2025 Your Name <your@email.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Double marking generator class.
 *
 * @package    local_doublemarking
 * @copyright  2025 Your Name <your@email.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class local_doublemarking_generator extends component_generator_base {
    
    /**
     * Create an assignment-level double marking configuration.
     *
     * @param array $record Forcing values for assignment settings
     * @return stdClass Created record
     */
    public function create_assignment_config($record) {
        global $DB;
        
        $record = (object)$record;
        
        // Required fields
        if (empty($record->assignmentid)) {
            throw new coding_exception('assignmentid must be specified when creating assignment configuration');
        }
        
        // Default values
        if (!isset($record->blindsetting)) {
            $record->blindsetting = 0;
        }
        
        if (!isset($record->markshidden)) {
            $record->markshidden = 0;
        }
        
        $record->userid = 0; // 0 indicates assignment-level settings
        $record->timecreated = time();
        $record->timemodified = time();
        
        $id = $DB->insert_record('local_doublemarking_alloc', $record);
        return $DB->get_record('local_doublemarking_alloc', ['id' => $id]);
    }
    
    /**
     * Create a student marker allocation.
     *
     * @param array $record Forcing values for marker allocation
     * @return stdClass Created record
     */
    public function create_marker_allocation($record) {
        global $DB;
        
        $record = (object)$record;
        
        // Required fields
        if (empty($record->assignmentid)) {
            throw new coding_exception('assignmentid must be specified when creating marker allocation');
        }
        
        if (empty($record->userid)) {
            throw new coding_exception('userid must be specified when creating marker allocation');
        }
        
        // Default values
        if (!isset($record->timecreated)) {
            $record->timecreated = time();
        }
        
        if (!isset($record->timemodified)) {
            $record->timemodified = time();
        }
        
        $id = $DB->insert_record('local_doublemarking_alloc', $record);
        return $DB->get_record('local_doublemarking_alloc', ['id' => $id]);
    }
}


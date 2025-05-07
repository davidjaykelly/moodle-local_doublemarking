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
 * Renderer for the double marking plugin.
 *
 * This renderer overrides the mod_assign renderer to use the custom
 * grading_app class for assignments with double marking enabled.
 *
 * @package   local_doublemarking
 * @copyright 2025 Your Name <your@email.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/assign/classes/output/renderer.php');

/**
 * Custom renderer for the double marking plugin.
 *
 * This renderer extends the mod_assign renderer to override the
 * render_grading_app method to use our custom grading_app class.
 *
 * @package   local_doublemarking
 * @copyright 2025 Your Name <your@email.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class local_doublemarking_renderer extends mod_assign\output\renderer {
    
    /**
     * Renders the grading app with additional double marking functionality.
     *
     * This method overrides the mod_assign render_grading_app method to use 
     * our custom grading_app class which adds double marking functionality.
     *
     * @param mod_assign\output\grading_app $app The standard grading app
     * @return string The rendered HTML
     */
    public function render_grading_app(\mod_assign\output\grading_app $app) {
        global $PAGE;
        
        // Check if this is a standard mod_assign\output\grading_app or our custom one
        if (!($app instanceof \local_doublemarking\output\grading_app)) {
            // Replace the standard grading app with our custom one
            $context = $PAGE->context;
            $assignment = $this->get_assign_from_context($context);
            
            if ($assignment) {
                $app = new \local_doublemarking\output\grading_app($assignment, $app->userid, $app->mform, $app->notifications);
            }
        }
        
        // Export the data and render the template
        $context = $app->export_for_template($this);
        return $this->render_from_template('mod_assign/grading_app', $context);
    }
    
    /**
     * Get the assignment instance from a context.
     *
     * @param \context $context The context
     * @return \assign|null The assignment instance or null
     */
    private function get_assign_from_context($context) {
        global $CFG;
        
        // Only module contexts are relevant
        if ($context->contextlevel != CONTEXT_MODULE) {
            return null;
        }
        
        // Load the assignment class
        require_once($CFG->dirroot . '/mod/assign/locallib.php');
        
        $cm = get_coursemodule_from_id('assign', $context->instanceid, 0, false, MUST_EXIST);
        $course = get_course($cm->course);
        
        return new \assign($context, $cm, $course);
    }
}

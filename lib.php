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
 * Library functions for the double marking local plugin.
 *
 * @package    local_doublemarking
 * @copyright  2025 Your Name <your@email.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Extend navigation for the plugin.
 *
 * @param global_navigation $navigation
 */
function local_doublemarking_extend_navigation(\global_navigation $navigation) {
    // Add navigation items if needed
}

/**
 * Extend settings navigation for the plugin.
 *
 * @param settings_navigation $settingsnav
 * @param context $context
 */
function local_doublemarking_extend_settings_navigation(\settings_navigation $settingsnav, \context $context) {
    global $PAGE;

    if ($PAGE->cm && $PAGE->cm->modname === 'assign' && 
        has_capability('local/doublemarking:allocate', $context)) {
        
        if ($settingnode = $settingsnav->find('modulesettings', \navigation_node::TYPE_SETTING)) {
            $settingnode->add(
                get_string('allocatemarkers', 'local_doublemarking'),
                new \moodle_url('/local/doublemarking/allocate.php', ['id' => $PAGE->cm->id]),
                \navigation_node::TYPE_SETTING
            );
        }
    }
}

/**
 * Add double marking fields to assignment settings form.
 *
 * @param \mod_assign_mod_form $formwrapper
 * @param \MoodleQuickForm $mform
 */
function local_doublemarking_coursemodule_standard_elements($formwrapper, $mform) {
    if (!$formwrapper instanceof \mod_assign_mod_form) {
        return;
    }

    $mform->addElement('header', 'doublemarking', get_string('pluginname', 'local_doublemarking'));
    
    $mform->addElement('select', 'blindsetting', get_string('blindmarking', 'local_doublemarking'), [
        0 => get_string('no'),
        1 => get_string('blindmarking', 'local_doublemarking'),
        2 => get_string('doubleblind', 'local_doublemarking')
    ]);
    $mform->addHelpButton('blindsetting', 'blindmarking', 'local_doublemarking');
    $mform->setDefault('blindsetting', 0);
    
    $mform->addElement('checkbox', 'markshidden', get_string('markshidden', 'local_doublemarking'));
    $mform->setDefault('markshidden', 1);
}

/**
 * Save double marking settings when assignment is saved.
 *
 * @param \stdClass $data
 */
function local_doublemarking_coursemodule_edit_post_actions($data) {
    global $DB;
    
    if ($data->modulename !== 'assign') {
        return $data;
    }

    // Save double marking settings
    $record = new \stdClass();
    $record->assignmentid = $data->id;
    $record->blindsetting = $data->blindsetting;
    $record->markshidden = !empty($data->markshidden);
    
    if ($existing = $DB->get_record('local_doublemarking_alloc', ['assignmentid' => $data->id])) {
        $record->id = $existing->id;
        $DB->update_record('local_doublemarking_alloc', $record);
    } else {
        $DB->insert_record('local_doublemarking_alloc', $record);
    }

    return $data;
}


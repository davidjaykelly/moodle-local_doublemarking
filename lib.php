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

defined('MOODLE_INTERNAL') || die();

/**
 * Save double marking settings when assignment is saved.
 *
 * @param \stdClass $data Module data
 * @return \stdClass The modified module data
 */
function local_doublemarking_coursemodule_edit_post_actions($data) {
    global $DB, $USER;
    
    debugging('Starting post actions with data: ' . var_export($data, true), DEBUG_DEVELOPER);
    
    if (empty($data->modulename) || $data->modulename !== 'assign') {
        debugging('Not an assignment module or missing modulename', DEBUG_DEVELOPER);
        return $data;
    }

    try {
        // Get the assignment ID for new or existing assignment
        $assignmentid = null;
        if (!empty($data->instance)) {
            $assignmentid = $data->instance;
            debugging('Using existing instance ID: ' . $assignmentid, DEBUG_DEVELOPER);
        } else if (!empty($data->add)) {
            // New assignment - get the last inserted assignment ID
            $sql = 'SELECT MAX(id) FROM {assign} WHERE course = ?';
            $params = [$data->course];
            debugging('Executing SQL: ' . $sql . ' with params: ' . var_export($params, true), DEBUG_DEVELOPER);
            
            $assignmentid = $DB->get_field_sql($sql, $params);
            debugging('Retrieved new assignment ID: ' . $assignmentid, DEBUG_DEVELOPER);
        }

        if (empty($assignmentid)) {
            debugging('Could not determine assignment ID. Data dump: ' . var_export($data, true), DEBUG_DEVELOPER);
            return $data;
        }

        // Prepare record for insertion/update
        $record = new \stdClass();
        $record->assignmentid = $assignmentid;
        $record->blindsetting = isset($data->blindsetting) ? (int)$data->blindsetting : 0;
        $record->markshidden = !empty($data->markshidden) ? 1 : 0;
        $record->timemodified = time();

        debugging('Prepared record: ' . var_export($record, true), DEBUG_DEVELOPER);

        // Check if record exists
        $existing = $DB->get_record('local_doublemarking_alloc', ['assignmentid' => $assignmentid]);
        debugging('Existing record check result: ' . var_export($existing, true), DEBUG_DEVELOPER);
        
        if ($existing) {
            $record->id = $existing->id;
            debugging('Updating record with ID: ' . $record->id, DEBUG_DEVELOPER);
            $DB->update_record('local_doublemarking_alloc', $record);
            debugging('Updated double marking settings for assignment ' . $assignmentid, DEBUG_DEVELOPER);
        } else {
            $record->timecreated = time();
            debugging('Inserting new record: ' . var_export($record, true), DEBUG_DEVELOPER);
            $newid = $DB->insert_record('local_doublemarking_alloc', $record);
            debugging('Inserted new double marking settings with ID: ' . $newid . ' for assignment ' . $assignmentid, DEBUG_DEVELOPER);
        }
        
    } catch (\Exception $e) {
        debugging('Error saving double marking settings: ' . $e->getMessage() . "\n" . 
                 'Trace: ' . $e->getTraceAsString(), DEBUG_DEVELOPER);
    }

    return $data;
}

/**
 * Add double marking fields to assignment settings form.
 *
 * @param \mod_assign_mod_form $formwrapper
 * @param \MoodleQuickForm $mform
 */
function local_doublemarking_coursemodule_standard_elements($formwrapper, $mform) {
    debugging('Adding form elements', DEBUG_DEVELOPER);
    
    if (!$formwrapper instanceof \mod_assign_mod_form) {
        debugging('Not an assignment form', DEBUG_DEVELOPER);
        return;
    }

    $mform->addElement('header', 'doublemarking', get_string('pluginname', 'local_doublemarking'));
    
    $mform->addElement('select', 'blindsetting', get_string('blindmarking', 'local_doublemarking'), [
        0 => get_string('no'),
        1 => get_string('blindmarking', 'local_doublemarking'),
        2 => get_string('doubleblind', 'local_doublemarking')
    ]);
    $mform->addHelpButton('blindsetting', 'blindmarking', 'local_doublemarking');
    
    $default_blind = get_config('local_doublemarking', 'default_blind_setting');
    debugging('Setting default blind setting: ' . $default_blind, DEBUG_DEVELOPER);
    $mform->setDefault('blindsetting', $default_blind);
    
    $mform->addElement('checkbox', 'markshidden', get_string('markshidden', 'local_doublemarking'));
    $default_hidden = get_config('local_doublemarking', 'default_marks_hidden');
    debugging('Setting default marks hidden: ' . $default_hidden, DEBUG_DEVELOPER);
    $mform->setDefault('markshidden', $default_hidden);
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

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
 * Double marking plugin upgrade code.
 *
 * @package    local_doublemarking
 * @copyright  2025 Your Name <your@email.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Upgrade function for the double marking plugin.
 *
 * @param int $oldversion The version we are upgrading from
 * @return bool Always returns true
 */
function xmldb_local_doublemarking_upgrade($oldversion) {
    global $DB;
    
    $dbman = $DB->get_manager();

    if ($oldversion < 2025050602) {
        // Define table local_doublemarking_alloc
        $table = new xmldb_table('local_doublemarking_alloc');

        // Get any existing data in the table - we'll restore this later
        $existing_settings = [];
        try {
            $existing_settings = $DB->get_records('local_doublemarking_alloc');
        } catch (Exception $e) {
            // Table might not exist yet, or have other issues - that's fine
            $existing_settings = [];
        }

        // Drop the table if it exists
        if ($dbman->table_exists($table)) {
            $dbman->drop_table($table);
        }

        // Create table local_doublemarking_alloc with new schema
        $table = new xmldb_table('local_doublemarking_alloc');
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('assignmentid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, null, null, '0');
        $table->add_field('marker1', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('marker2', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('ratifier', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('marker1grade', XMLDB_TYPE_NUMBER, '10, 5', null, null, null, null);
        $table->add_field('marker2grade', XMLDB_TYPE_NUMBER, '10, 5', null, null, null, null);
        $table->add_field('marker1feedback', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('marker2feedback', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('finalgrade', XMLDB_TYPE_NUMBER, '10, 5', null, null, null, null);
        $table->add_field('blindsetting', XMLDB_TYPE_INTEGER, '2', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('markshidden', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '1');
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');

        // Add keys
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_key('assignmentid', XMLDB_KEY_FOREIGN, ['assignmentid'], 'assign', ['id']);
        $table->add_key('userid', XMLDB_KEY_FOREIGN, ['userid'], 'user', ['id']);
        $table->add_key('marker1', XMLDB_KEY_FOREIGN, ['marker1'], 'user', ['id']);
        $table->add_key('marker2', XMLDB_KEY_FOREIGN, ['marker2'], 'user', ['id']);
        $table->add_key('ratifier', XMLDB_KEY_FOREIGN, ['ratifier'], 'user', ['id']);

        // Add indexes - note: no need for assignmentid_idx since foreign key creates an index automatically
        $table->add_index('assignment_user_idx', XMLDB_INDEX_UNIQUE, ['assignmentid', 'userid']);

        // Create the table
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Restore any existing data
        foreach ($existing_settings as $record) {
            // Ensure userid is 0, not null for global assignment settings
            if ($record->userid === null) {
                $record->userid = 0;
            }
            
            // Remove id as it will be auto-assigned
            unset($record->id);
            
            try {
                $DB->insert_record('local_doublemarking_alloc', $record);
            } catch (Exception $e) {
                // If insert fails, log it but continue
                mtrace('Error restoring record: ' . $e->getMessage());
            }
        }

        // Double marking savepoint reached.
        upgrade_plugin_savepoint(true, 2025050602, 'local', 'doublemarking');
    }

    return true;
}


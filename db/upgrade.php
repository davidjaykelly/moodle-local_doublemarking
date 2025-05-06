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

        // First remove the old index if it exists - MUST remove indexes before modifying fields
        $index = new xmldb_index('assignment_user', XMLDB_INDEX_UNIQUE, array('assignmentid', 'userid'));
        if ($dbman->index_exists($table, $index)) {
            $dbman->drop_index($table, $index);
        }

        // Now modify the userid field to have a default of 0
        $field = new xmldb_field('userid', XMLDB_TYPE_INTEGER, '10', null, null, null, '0', 'assignmentid');
        if ($dbman->field_exists($table, $field)) {
            $dbman->change_field_default($table, $field);
        }
        
        // Update existing records with NULL userid to 0
        $DB->execute("UPDATE {local_doublemarking_alloc} SET userid = 0 WHERE userid IS NULL");

        // Add new indexes after field modifications
        $index1 = new xmldb_index('assignmentid_idx', XMLDB_INDEX_NOTUNIQUE, array('assignmentid'));
        if (!$dbman->index_exists($table, $index1)) {
            $dbman->add_index($table, $index1);
        }

        $index2 = new xmldb_index('assignment_user_idx', XMLDB_INDEX_UNIQUE, array('assignmentid', 'userid'));
        if (!$dbman->index_exists($table, $index2)) {
            $dbman->add_index($table, $index2);
        }

        // Double marking savepoint reached.
        upgrade_plugin_savepoint(true, 2025050602, 'local', 'doublemarking');
    }

    return true;
}


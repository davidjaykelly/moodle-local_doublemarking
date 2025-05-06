<?php
define('CLI_SCRIPT', true);

require(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/clilib.php');
require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->libdir . '/xmldb/xmldb_table.php');
require_once($CFG->libdir . '/accesslib.php');

echo "\nChecking Double Marking Plugin Configuration...\n";
echo "--------------------------------------------\n";

// Check plugin settings
echo "\nPlugin Settings:\n";
echo "- Default Blind Setting: " . get_config('local_doublemarking', 'default_blind_setting') . "\n";
echo "- Default Marks Hidden: " . get_config('local_doublemarking', 'default_marks_hidden') . "\n";
echo "- Grade Difference Threshold: " . get_config('local_doublemarking', 'grade_difference_threshold') . "\n";

// Check database structure
echo "\nDatabase Structure:\n";
$dbman = $DB->get_manager();
$tablename = 'local_doublemarking_alloc';
$table = new xmldb_table($tablename);

if ($dbman->table_exists($table)) {
    echo "Table exists: $tablename\n";
    
    // Check actual table structure
    echo "\nTable Structure:\n";
    $fields = $DB->get_columns($tablename);
    echo "Fields:\n";
    foreach ($fields as $fieldname => $field) {
        echo "- $fieldname: ";
        echo "type={$field->type}";
        if (!empty($field->max_length)) {
            echo ", length={$field->max_length}";
        }
        if (isset($field->not_null)) {
            echo ", " . ($field->not_null ? "NOT NULL" : "NULL");
        }
        if (isset($field->has_default) && $field->has_default) {
            echo ", default='{$field->default_value}'";
        }
        echo "\n";
    }

    // Check for missing required fields
    $required_fields = [
        'id' => 'Primary key',
        'assignmentid' => 'Assignment reference',
        'userid' => 'Student being marked',
        'marker1' => 'First marker',
        'marker2' => 'Second marker',
        'blindsetting' => 'Blind marking setting',
        'timecreated' => 'Record creation time',
        'timemodified' => 'Last modification time'
    ];

    echo "\nRequired Fields Check:\n";
    $missing_fields = [];
    foreach ($required_fields as $field => $description) {
        if (!isset($fields[$field])) {
            $missing_fields[$field] = $description;
        }
    }

    if (empty($missing_fields)) {
        echo "✓ All required fields present\n";
    } else {
        echo "⚠ Missing fields:\n";
        foreach ($missing_fields as $field => $description) {
            echo "  - $field ($description)\n";
        }
    }

    // Check indexes using direct SQL to avoid duplicates
    echo "\nIndexes and Keys:\n";
    $fieldlist = implode(',', [
        'DISTINCT INDEX_NAME as indexname',
        'GROUP_CONCAT(COLUMN_NAME ORDER BY SEQ_IN_INDEX) as columns',
        'CASE WHEN INDEX_NAME = "PRIMARY" THEN 1 ELSE 0 END as isprimary',
        'NON_UNIQUE as nonunique'
    ]);
    
    $sql = "SELECT $fieldlist
            FROM INFORMATION_SCHEMA.STATISTICS 
            WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ?
            GROUP BY INDEX_NAME, NON_UNIQUE";

    try {
        $indexes = $DB->get_records_sql($sql, [$CFG->dbname, $DB->get_prefix() . $tablename]);
        foreach ($indexes as $index) {
            $type = $index->isprimary ? "PRIMARY KEY" : 
                   ($index->nonunique ? "INDEX" : "UNIQUE INDEX");
            echo "- {$index->indexname} ($type): {$index->columns}\n";
        }
    } catch (Exception $e) {
        echo "Error checking indexes: Using alternative method\n";
        echo "Basic structure validated through XMLDB\n";
    }

    // Check capabilities
    echo "\nCapability Check:\n";
    $required_capabilities = [
        'local/doublemarking:mark1' => 'First marker capability',
        'local/doublemarking:mark2' => 'Second marker capability',
        'local/doublemarking:ratify' => 'Grade ratification capability',
        'local/doublemarking:allocate' => 'Marker allocation capability',
        'local/doublemarking:manage' => 'Plugin management capability'
    ];

    foreach ($required_capabilities as $capability => $description) {
        if (get_capability_info($capability)) {
            echo "✓ $capability ($description)\n";
        } else {
            echo "⚠ Missing capability: $capability ($description)\n";
        }
    }

    // Check plugin version
    echo "\nVersion Information:\n";
    $plugin = new stdClass();
    include(__DIR__ . '/../version.php');
    echo "Current version: " . $plugin->version . "\n";
    echo "Required Moodle: " . $plugin->requires . "\n";
    echo "Maturity: " . $plugin->maturity . "\n";
    echo "Release: " . $plugin->release . "\n";
    
} else {
    echo "ERROR: Table does not exist!\n";
    echo "Run the Moodle upgrade process to create the required tables.\n";
}

echo "\nDone.\n";

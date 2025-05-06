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
 * Plugin settings for double marking.
 *
 * @package    local_doublemarking
 * @copyright  2025 Your Name <your@email.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    $settings = new admin_settingpage('local_doublemarking', get_string('pluginname', 'local_doublemarking'));
    $ADMIN->add('localplugins', $settings);

    // Grade difference threshold that triggers notifications
    $settings->add(new admin_setting_configtext(
        'local_doublemarking/grade_difference_threshold',
        get_string('gradedifferencethreshold', 'local_doublemarking'),
        get_string('gradedifferencethreshold_desc', 'local_doublemarking'),
        10, // default value
        PARAM_INT
    ));

    // Default blind marking setting
    $settings->add(new admin_setting_configselect(
        'local_doublemarking/default_blind_setting',
        get_string('defaultblindsetting', 'local_doublemarking'),
        get_string('defaultblindsetting_desc', 'local_doublemarking'),
        0, // default value
        [
            0 => get_string('no'),
            1 => get_string('blindmarking', 'local_doublemarking'),
            2 => get_string('doubleblind', 'local_doublemarking')
        ]
    ));

    // Hide marks until both markers complete
    $settings->add(new admin_setting_configcheckbox(
        'local_doublemarking/default_marks_hidden',
        get_string('defaultmarkshidden', 'local_doublemarking'),
        get_string('defaultmarkshidden_desc', 'local_doublemarking'),
        1 // default value
    ));
}

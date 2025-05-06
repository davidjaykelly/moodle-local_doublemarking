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
 * Language strings for the double marking local plugin.
 *
 * @package    local_doublemarking
 * @copyright  2025 Your Name <your@email.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['pluginname'] = 'Double Marking';
$string['plugindescription'] = 'Enables double marking functionality for assignments';

// Capability strings
$string['doublemarking:mark1'] = 'Act as first marker';
$string['doublemarking:mark2'] = 'Act as second marker';
$string['doublemarking:ratify'] = 'Ratify marks';
$string['doublemarking:allocate'] = 'Allocate markers';
$string['doublemarking:manage'] = 'Manage double marking settings';

// Settings and form strings
$string['blindmarking'] = 'Blind marking';
$string['blindmarking_help'] = 'Blind marking hides marker identities from each other until both have completed their marking.';
$string['doubleblind'] = 'Double-blind marking';
$string['doubleblind_help'] = 'Double-blind marking hides student identities from markers as well as hiding marker identities from each other.';
$string['marker1'] = 'First marker';
$string['marker2'] = 'Second marker';
$string['ratifier'] = 'Ratifier';
$string['allocatemarkers'] = 'Allocate markers';
$string['bulkallocate'] = 'Bulk allocate markers';
$string['marksvisible'] = 'Show marks to other markers';
$string['markshidden'] = 'Hide marks until both markers complete';

// Status messages
$string['awaitingmarker1'] = 'Awaiting first marker';
$string['awaitingmarker2'] = 'Awaiting second marker';
$string['awaitingratification'] = 'Awaiting ratification';
$string['markingcomplete'] = 'Marking complete';
$string['gradedisagreement'] = 'Grade disagreement detected';

// Error messages
$string['cannotmodifycompletedmarking'] = 'Cannot modify completed marking';
$string['insufficientcapability'] = 'Insufficient capability to perform this action';
$string['markerscannotbesame'] = 'First and second markers cannot be the same person';
$string['missingallocation'] = 'Marker allocation not found';

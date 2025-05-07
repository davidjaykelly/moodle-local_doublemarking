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
 * Language strings for the double marking plugin.
 *
 * @package    local_doublemarking
 * @copyright  2025 Your Name <your@email.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['pluginname'] = 'Double Marking';
$string['plugindescription'] = 'Enables double marking functionality for assignments';

// User capabilities
$string['doublemarking:viewall'] = 'View all marker allocations and grades';
$string['canresolvedifferences'] = 'Can resolve grade differences';
$string['canseebothgrades'] = 'Can see both marker grades';
$string['canseemarkeridentities'] = 'Can see marker identities';

// Capability strings
$string['doublemarking:mark1'] = 'Act as first marker';
$string['doublemarking:mark2'] = 'Act as second marker';
$string['doublemarking:ratify'] = 'Ratify marks';
$string['doublemarking:allocate'] = 'Allocate markers';
$string['doublemarking:manage'] = 'Manage double marking settings';

// Settings strings
$string['gradedifferencethreshold'] = 'Grade difference threshold';
$string['gradedifferencethreshold_desc'] = 'Maximum allowed difference between first and second marker grades before notification is triggered';
$string['defaultblindsetting'] = 'Default blind marking setting';
$string['defaultblindsetting_desc'] = 'Choose the default blind marking setting for new assignments';
$string['defaultmarkshidden'] = 'Hide marks by default';
$string['defaultmarkshidden_desc'] = 'Whether to hide marks from other markers until both have completed their marking by default';

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
$string['anonymousmarker'] = 'Marker {$a}';

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

// Grading interface strings
$string['youareassignedas'] = 'You are assigned as {$a}';
$string['youarenotassigned'] = 'You are not assigned as a marker for this submission';
$string['markeridentities'] = 'Marker identities';
$string['markergrades'] = 'Marker grades';
$string['gradedifference'] = 'Grade difference';
$string['gradingwarning'] = 'Grading warning';
$string['gradingwarning_critical'] = 'Critical grade difference (>{$a}%)';
$string['gradingwarning_large'] = 'Large grade difference (>{$a}%)';
$string['gradingwarning_moderate'] = 'Moderate grade difference (>{$a}%)';
$string['showmarkers'] = 'Show markers';
$string['hidemarkers'] = 'Hide markers';
$string['othermarkergrade'] = 'Other marker\'s grade';

// Allocation page strings
$string['student'] = 'Student';
$string['actions'] = 'Actions';
$string['selectmarker'] = 'Select marker';
$string['allocatemarkersdescription'] = 'Allocate first and second markers for assignment "{$a}".';
$string['allocationupdated'] = 'Marker allocation updated successfully';
$string['allocationcreated'] = 'Marker allocation created successfully';
$string['nostudents'] = 'No students found in this assignment';

// Ratification form strings
$string['ratification'] = 'Grade ratification';
$string['finalgrade'] = 'Final grade';
$string['finalgradedescription'] = 'Enter the final grade that will be recorded for this submission';
$string['gradedifferencerequiresratification'] = 'The grade difference between markers exceeds the threshold and requires ratification';
$string['nogradedifference'] = 'The marker grades are within the acceptable threshold';
$string['automaticgrade'] = 'The grades are within acceptable limits. An average of both grades can be automatically applied.';
$string['confirmgrade'] = 'Confirm final grade';
$string['usemarker1grade'] = 'Use first marker\'s grade ({$a})';
$string['usemarker2grade'] = 'Use second marker\'s grade ({$a})';
$string['useaveragegrade'] = 'Use average of both grades';
$string['usecustomgrade'] = 'Use custom grade';
$string['ratificationcomment'] = 'Ratification comment';
$string['saveratification'] = 'Save ratification';
$string['ratificationsaved'] = 'Grade ratification has been saved successfully';
$string['awaitingmarkers'] = 'Awaiting marking from both markers before ratification can be completed';
$string['awaitingsecondmarker'] = 'Awaiting second marker before ratification can be completed';

// Additional error messages
$string['invalidgrade'] = 'The grade entered is not valid. Please enter a valid number.';
$string['gradenotsaved'] = 'There was a problem saving the grade. Please try again.';
$string['gradesaved'] = 'Grade has been saved successfully.';
$string['errorfetchingallocation'] = 'Error fetching marker allocation data.';
$string['errorloadingsettings'] = 'Error loading double marking settings.';
$string['errorprocessingform'] = 'There was an error processing the form. Please try again.';

// Additional grading interface strings
$string['nomarkerallocations'] = 'No markers have been allocated for this submission';
$string['notassigned'] = 'Not assigned';
$string['notgraded'] = 'Not yet graded';
$string['gradesonlyvisiblewhencomplete'] = 'Grades will be visible once both markers have completed their marking';
$string['graderatified'] = 'Grade has been ratified';
$string['gradependingratification'] = 'Grade is pending ratification';
$string['gradependingmarking'] = 'Grade is pending marking completion';
$string['doublemarkingstatus'] = 'Double marking status';

// Grade difference messages
$string['gradedifferenceexceeds'] = 'Grade difference exceeds the threshold of {$a}%';
$string['gradedifferenceacceptable'] = 'Grade difference is within acceptable limits ({$a}%)';
$string['gradeagreement'] = 'Markers are in agreement on the grade';
$string['gradedifferencepercent'] = 'Grade difference: {$a}%';

// Accessibility strings for screen readers
$string['a11y_marker1details'] = 'First marker details';
$string['a11y_marker2details'] = 'Second marker details';
$string['a11y_gradedifference'] = 'Difference between first and second marker grades';
$string['a11y_ratificationform'] = 'Form for ratifying the final grade';
$string['a11y_ratificationoptions'] = 'Options for determining the final grade';
$string['a11y_criticalgradewarning'] = 'Critical warning: significant difference between marker grades';
$string['a11y_loadingallocation'] = 'Loading marker allocation data';
$string['a11y_markerhidden'] = 'Marker identity is hidden due to blind marking settings';
$string['a11y_gradehidden'] = 'Grade is hidden until both markers complete their marking';
$string['a11y_togglemarkervisibility'] = 'Toggle visibility of marker identities';

// Double marking process completion
$string['doublemarkingrequired'] = 'This assignment requires double marking';
$string['doublemarkinginprogress'] = 'Double marking is in progress';
$string['doublemarkingrequiresratification'] = 'Double marking complete but requires ratification';
$string['doublemarkingrequiresbothmarkers'] = 'This submission requires grading from both markers';
$string['doublemarkingrequiresratifier'] = 'This submission requires a ratifier to confirm the final grade';
$string['doublemarkingrequirementssatisfied'] = 'All double marking requirements have been satisfied';

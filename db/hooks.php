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
 * Hooks for the double marking local plugin.
 *
 * @package    local_doublemarking
 * @copyright  2025 Your Name <your@email.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$hooks = [
    [
        'hook' => '\\mod_assign\\hook\\submission_form_viewed',
        'callback' => '\\local_doublemarking\\hook\\mod_assign::submission_form_viewed',
        'priority' => 200
    ],
    [
        'hook' => '\\mod_assign\\hook\\grading_form_viewed',
        'callback' => '\\local_doublemarking\\hook\\mod_assign::grading_form_viewed',
        'priority' => 200
    ],
    [
        'hook' => '\\mod_assign\\hook\\before_save_grade',
        'callback' => '\\local_doublemarking\\hook\\mod_assign::before_save_grade',
        'priority' => 200
    ],
    [
        'hook' => '\\mod_assign\\hook\\after_save_grade',
        'callback' => '\\local_doublemarking\\hook\\mod_assign::after_save_grade',
        'priority' => 200
    ],
    [
        'hook' => '\\core\\hook\\output\\before_http_headers',
        'callback' => '\\local_doublemarking\\hook\\mod_assign::before_http_headers',
        'priority' => 200
    ],
    [
        'hook' => '\\mod_assign\\hook\\assignment_viewed',
        'callback' => '\\local_doublemarking\\hook\\mod_assign::assignment_viewed',
        'priority' => 200
    ],
    [
        'hook' => '\\mod_assign\\hook\\before_render_gradingpanel',
        'callback' => '\\local_doublemarking\\hook\\mod_assign::before_render_gradingpanel',
        'priority' => 200
    ]
];

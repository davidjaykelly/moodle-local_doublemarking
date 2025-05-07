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

namespace local_doublemarking\output;

defined('MOODLE_INTERNAL') || die();

use plugin_renderer_base;
use renderable;
use templatable;

class renderer extends plugin_renderer_base {
    /**
     * Render the grading panel extension.
     *
     * @param array $data The data to render
     * @return string HTML
     */
    public function render_grading_panel_extension($data) {
        global $USER, $DB;

        // Get assignment-level settings
        $settings = $DB->get_record('local_doublemarking_alloc', [
            'assignmentid' => $data['assignment']->id,
            'userid' => 0
        ]);

        // Prepare data for template
        $templatedata = [
            'assignment' => $data['assignment'],
            'userid' => $data['userid'],
            'allocation' => $data['allocation'],
            'canviewmarkers' => has_capability('local/doublemarking:viewmarkers', $this->page->context),
            'canviewgrades' => has_capability('local/doublemarking:viewgrades', $this->page->context),
            'ismarker1' => ($data['allocation']->marker1 == $USER->id),
            'ismarker2' => ($data['allocation']->marker2 == $USER->id),
            'isratifier' => ($data['allocation']->ratifier == $USER->id),
            'blindmarking' => !empty($settings->blindsetting),
            'markshidden' => !empty($settings->markshidden)
        ];

        // Build the full grading panel
        $html = $this->render_from_template('local_doublemarking/marker_allocation', $templatedata);
        $html .= $this->render_from_template('local_doublemarking/grade_status', $templatedata);
        
        if ($templatedata['isratifier'] || has_capability('local/doublemarking:viewall', $this->page->context)) {
            $html .= $this->render_from_template('local_doublemarking/ratification_panel', $templatedata);
        }

        return $html;
    }
}


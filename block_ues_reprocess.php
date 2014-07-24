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
 *
 * @package    block_ues_reprocess
 * @copyright  2014 Louisiana State University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once $CFG->dirroot . '/enrol/ues/publiclib.php';
ues::require_daos();

class block_ues_reprocess extends block_list {
    function init() {
        $this->title = get_string('pluginname', 'block_ues_reprocess');
    }

    function applicable_formats() {
        return array('course' => true, 'my' => true, 'site' => false);
    }

    function get_content() {

        if ($this->content !== NULL) {
            return $this->content;
        }

        global $OUTPUT, $USER, $COURSE;

        $content = new stdClass;

        $content->items = array();
        $content->icons = array();
        $content->footer = '';

        ues::require_daos();

        $teacher = ues_teacher::get(array('userid' => $USER->id));

        if (!$teacher) {
            return $this->content;
        }

        $_s = ues::gen_str('block_ues_reprocess');

        $url_gen = function($base, $params=null) use ($_s) {
            $url = new moodle_url('/blocks/ues_reprocess/'.$base.'.php', $params);

            return html_writer::link($url, $_s($base));
        };

        $is_site = $COURSE->id == 1;

        if ($is_site) {
            $content->items[] = $url_gen('reprocess', array(
                'id' => $USER->id, 'type' => 'user'
            ));
        } else {
            $sections = $teacher->sections(true);

            $in_this_course = function($section) use ($COURSE) {
                return $section->idnumber == $COURSE->idnumber;
            };

            $found = array_filter($sections, $in_this_course);

            if (empty($found)) {
                return $this->content;
            }

            $content->items[] = $url_gen('reprocess', array(
                'id' => $COURSE->id, 'type' => 'course'
            ));
        }

        $content->icons[] = $OUTPUT->pix_icon(
            'i/users', $_s('reprocess'), 'moodle', array('class' => 'icon')
        );

        $this->content = $content;

        return $this->content;
    }

    function cron() {
        $_s = ues::gen_str('block_ues_reprocess');

        mtrace($_s('cleanup'));

        ues_section::update_meta(array('section_reprocessed' => 0));

        return true;
    }
}

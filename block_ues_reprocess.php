<?php

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

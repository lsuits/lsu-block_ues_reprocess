<?php

abstract class ues_event_handler {
    function helpdesk_course($help) {

        if (!has_capability('block/ues_reprocess:canreprocess', $help->context)) {
            return true;
        }

        $pluginname = get_string('pluginname', 'block_ues_reprocess');

        $params = array('id' => $help->courseid, 'type' => 'course');
        $url = new moodle_url('/blocks/ues_reprocess/reprocess.php', $params);

        $help->links[] = html_writer::link($url, $pluginname);

        return true;
    }

    function ues_course_settings_navigation($params) {
        global $OUTPUT;

        $nodes = $params[0];
        $instance = $params[1];

        $context = get_context_instance(CONTEXT_COURSE, $instance->courseid);

        if (!has_capability('block/ues_reprocess:canreprocess', $context)) {
            return true;
        }

        $pluginname = get_string('reprocess', 'block_ues_reprocess');
        $params = array('id' => $instance->courseid, 'type' => 'course');

        $reprocess_link = new navigation_node(array(
            'text' => $pluginname,
            'shorttext' => $pluginname,
            'icon' => new pix_icon('i/users', $pluginname),
            'key' => 'block_ues_reprocess',
            'action' => new moodle_url('/blocks/ues_reprocess/reprocess.php', $params)
        ));

        $nodes->parent->add_node($reprocess_link, 'manageinstances');
        return true;
    }
}

<?php

class block_ues_reprocess extends block_list {
    function init() {
        $this->title = get_string('pluginname', 'ues_reprocess');
    }

    function applicable_formats() {
        return array('course' => true, 'my' => false, 'site' => false);
    }

    function get_content() {

        if ($this->content !== NULL) {
            return $this->content;
        }

        $content = new stdClass;

        $items = array();
        $icons = array();

        $content->items = $items;
        $content->icons = $icons;
        $content->footer = '';

        $this->content = $content;

        return $this->content;
    }
}

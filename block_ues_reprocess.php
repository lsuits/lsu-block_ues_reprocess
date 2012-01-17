<?php

class block_ues_reprocess extends block_list {
    function init() {
        $this->title = get_string('pluginname', 'ues_reprocess');
    }

    function get_content() {
        $content = new stdClass;

        $items = array();
        $icons = array();

        $content->footer = '';

        $this->content = $content;

        return $this->content;
    }
}

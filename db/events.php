<?php

$map = function($event) {
    return array(
        'handlerfile' => '/blocks/ues_reprocess/eventslib.php',
        'handlerfunction' => array('ues_event_handler', $event),
        'schedule' => 'instant'
    );
};

$events = array('helpdesk_course', 'ues_course_settings_navigation');

$handlers = array_combine($events, array_map($map, $events));

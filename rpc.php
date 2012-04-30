<?php

require_once '../../config.php';
require_once $CFG->dirroot . '/enrol/ues/publiclib.php';
ues::require_daos();
require_once 'lib.php';

require_login();

$id = required_param('id', PARAM_INT);
$type = required_param('type', PARAM_TEXT);

if ($type == 'user') {
    $user = $DB->get_record('user', array('id' => $id), '*', MUST_EXIST);

    $filter = function($section) {
        $section->fill_meta();
        return true;
    };

    $context = get_context_instance(CONTEXT_SYSTEM);
    $back_url = new moodle_url('/my');
} else {
    $course = $DB->get_record('course', array('id' => $id), '*', MUST_EXIST);
    $user = $USER;

    $filter = function($section) use ($course) {
        $section->fill_meta();
        return $section->idnumber == $course->idnumber;
    };

    $context = get_context_instance(CONTEXT_COURSE, $course->id);
    $back_url = new moodle_url('/course/view.php', array('id' => $id));
}

$PAGE->set_context($context);

$_s = ues::gen_str('block_ues_reprocess');

$ues_user = ues_user::upgrade($user);

if (has_capability('block/ues_reprocess:canreprocess', $context)) {
    $pre_sections = ues_section::from_course($course);
} else {
    $pre_sections = $ues_user->sections(true);
}

$owned_sections = array_filter($pre_sections, $filter);

if ($data = data_submitted() and !empty($owned_sections)) {
    try {
        $sections = ues_reprocess::post($owned_sections, $data);
        ues_reprocess::select($sections);
    } catch (Exception $e) {
        echo $OUTPUT->notification($e->getMessage());
    }
} else {
    echo $OUTPUT->notification($_s('none_found'));
}

echo $OUTPUT->continue_button($back_url);

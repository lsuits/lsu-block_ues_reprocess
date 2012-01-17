<?php

require_once '../../config.php';
require_once $CFG->dirroot . '/enrol/ues/publiclib.php';
require_once 'reprocess_form.php';
require_once 'lib.php';

require_login();

ues::require_daos();

$courseid = required_param('id', PARAM_INT);

$course = $DB->get_record('course', array('id' => $courseid), '*', MUST_EXIST);

$_s = ues::gen_str('block_ues_reprocess');

$teacher = ues_teacher::get(array('userid' => $USER->id));

$owned_sections = $teacher->sections(true);

$in_this_course = function($section) use ($course) {
    $section->fill_meta();
    return $section->idnumber == $course->idnumber;
};

$found = array_filter($owned_sections, $in_this_course);

$blockname = $_s('pluginname');
$header = $_s('reprocess_course');

$url = new moodle_url('/blocks/ues_reprocess/reprocess.php', array('id' => $USER->id));

$back_url = new moodle_url('/course/view.php?id='.$courseid);

$PAGE->set_context(get_context_instance(CONTEXT_COURSE, $courseid));
$PAGE->set_heading($blockname . ': ' . $header);
$PAGE->navbar->add($blockname, $url);
$PAGE->navbar->add($header);
$PAGE->set_title($header);
$PAGE->set_course($course);

$form = new reprocess_form(null, array('sections' => $found));

if ($form->is_cancelled()) {
    redirect($back_url);
} else if ($data = $form->get_data()) {
    $sections = ues_reprocess::post($found, $data);
    $posted = true;
}

echo $OUTPUT->header();
echo $OUTPUT->heading($header);

if (!empty($posted) and empty($sections)) {
    echo $OUTPUT->notification($_s('select'));
    $form->display();
} else if (!empty($posted)) {
    ues_reprocess::select($sections);
    echo $OUTPUT->continue_button($back_url);
} else if (empty($found)) {
    echo $OUTPUT->notification($_s('none_found'));
    echo $OUTPUT->continue_button($url);
} else {
    $form->display();
}

echo $OUTPUT->footer();

<?php

require_once '../../config.php';
require_once $CFG->dirroot . '/enrol/ues/publiclib.php';
require_once 'reprocess_form.php';
require_once 'lib.php';

require_login();

ues::require_daos();

$type = required_param('type', PARAM_TEXT);

$valid_types = array('user', 'course');

if (!in_array($type, $valid_types)) {
    print_error('not_supported', 'block_ues_reprocess', '', $type);
}

$id = required_param('id', PARAM_INT);

$_s = ues::gen_str('block_ues_reprocess');

$blockname = $_s('pluginname');

if ($type == 'user') {
    $user = $DB->get_record('user', array('id' => $id), '*', MUST_EXIST);

    $filter = function ($section) {
        $section->fill_meta();
        return true;
    };

    $header = $_s('reprocess');
    $context = get_context_instance(CONTEXT_SYSTEM);

    $back_url = new moodle_url('/my');

    $custom_page = function ($page) use ($blockname, $context, $header) {
        $page->set_context($context);
        $page->set_heading($blockname);
        $page->navbar->add($blockname);
        $page->navbar->add($header);
        $page->set_title($header);
    };
} else {
    $course = $DB->get_record('course', array('id' => $id), '*', MUST_EXIST);
    $user = $USER;

    $filter = function($section) use ($course) {
        $section->fill_meta();
        return $section->idnumber == $course->idnumber;
    };

    $header = $_s('reprocess_course');

    $back_url = new moodle_url('/course/view.php', array('id' => $id));
    $custom_page = function ($page) use ($course, $header, $blockname) {
        global $USER;
        $context = get_context_instance(CONTEXT_COURSE, $course->id);

        $url = new moodle_url('/blocks/ues_reprocess/reprocess.php', array(
            'id' => $USER->id,
            'type' => 'user'
        ));

        $page->set_context($context);
        $page->set_heading($blockname . ': ' . $header);
        $page->navbar->add($blockname, $url);
        $page->navbar->add($header);
        $page->set_title($header);
        $page->set_course($course);
    };
}

$ues_user = ues_user::upgrade($user);

$owned_sections = array_filter($ues_user->sections(true), $filter);

$custom_page($PAGE);

$form = new reprocess_form(null, array(
    'sections' => $owned_sections, 'type' => $type
));

if ($form->is_cancelled()) {
    redirect($back_url);
} else if ($data = $form->get_data()) {
    $PAGE->requires->js('/lib/jquery.js');
    $PAGE->requires->js('/blocks/ues_reprocess/js/reprocess.js');

    $basic = array('id'=> $id, 'type' => $type);

    $params = get_object_vars($data);

    $sections = ues_reprocess::post($owned_sections, $params);

    $confirm_url = new moodle_url('rpc.php', $params);
    $cancel_url = new moodle_url('reprocess.php', $basic);
    $posted = true;
}

echo $OUTPUT->header();
echo $OUTPUT->heading($header);

if (!empty($posted) and empty($sections)) {
    echo $OUTPUT->notification($_s('select'));
    $form->display();
} else if (!empty($posted)) {
    $to_number = function ($in, $section) {
        $section->semester();
        $section->course();
        return $in . "<li><strong>$section</strong></li>";
    };

    $numbers = array_reduce($sections, $to_number, '');

    echo $OUTPUT->confirm($_s('are_you_sure', $numbers), $confirm_url, $cancel_url);

    echo html_writer::start_tag('div', array('id' => 'loading', 'style' => 'display: none'));
    echo $OUTPUT->notification($_s('patience'));
    echo '<br/>';
    echo $OUTPUT->pix_icon('i/loading', 'Loading');
    echo html_writer::end_tag('div');

} else if (empty($owned_sections)) {
    echo $OUTPUT->notification($_s('none_found'));
    echo $OUTPUT->continue_button($back_url);
} else {
    $form->display();
}

echo $OUTPUT->footer();

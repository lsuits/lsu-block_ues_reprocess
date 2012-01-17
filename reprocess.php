<?php

require_once '../../config.php';
require_once $CFG->dirroot . '/enrol/ues/publiclib.php';
require_once 'reprocess_form.php';
require_once 'lib.php';

require_login();

ues::require_daos();

$userid = required_param('id', PARAM_INT);

$user = $DB->get_record('user', array('id' => $userid), '*', MUST_EXIST);

$_s = ues::gen_str('block_ues_reprocess');

$ues_user = ues_user::upgrade($user);

$owned_sections = $ues_user->sections(true);

foreach ($owned_sections as $section) {
    $section->fill_meta();
}

$blockname = $_s('pluginname');
$header = $_s('reprocess');

$back_url = new moodle_url('/my');

$PAGE->set_context(get_context_instance(CONTEXT_SYSTEM));
$PAGE->set_heading($blockname);
$PAGE->navbar->add($blockname);
$PAGE->navbar->add($header);
$PAGE->set_title($header);

$form = new reprocess_form(null, array('sections' => $owned_sections));

if ($form->is_cancelled()) {
    redirect($back_url);
} else if ($data = $form->get_data()) {
    $sections = ues_reprocess::post($owned_sections, $data);
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
} else if (empty($owned_sections)) {
    echo $OUTPUT->notification($_s('none_found'));
    echo $OUTPUT->continue_button($back_url);
} else {
    $form->display();
}

echo $OUTPUT->footer();

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
    $context = context_system::instance();

    $back_url = new moodle_url('/my');

    $custom_page = function ($page) use ($blockname, $context, $header) {
        global $USER;
        $page->set_context($context);
        $page->set_heading($blockname);
        $page->navbar->add($blockname);
        $page->navbar->add($header);
        $page->set_title($header);
        $page->set_url(new moodle_url('/blocks/ues_reprocess/reprocess.php', array(
            'id' => $USER->id, 'type' => 'user'
        )));
    };
} else {
    $course = $DB->get_record('course', array('id' => $id), '*', MUST_EXIST);
    $context = context_course::instance($course->id);

    $user = $USER;

    $filter = function($section) use ($course) {
        $section->fill_meta();
        return $section->idnumber == $course->idnumber;
    };

    $header = $_s('reprocess_course');

    $back_url = new moodle_url('/course/view.php', array('id' => $id));
    $custom_page = function ($page) use ($course, $context, $header, $blockname) {
        global $USER;

        $base = '/blocks/ues_reprocess/reprocess.php';

        $url = new moodle_url($base, array('id' => $USER->id,'type' => 'user'));

        $page->set_context($context);
        $page->set_heading($blockname . ': ' . $header);
        $page->navbar->add($blockname, $url);
        $page->navbar->add($header);
        $page->set_title($header);
        $page->set_course($course);
        $page->set_url(new moodle_url($base, array(
            'id' => $course->id, 'type' => 'course'
        )));
    };
}

$ues_user = ues_user::upgrade($user);

if (has_capability('block/ues_reprocess:canreprocess', $context)) {
    $pre_sections = ues_section::from_course($course);
} else {
    $pre_sections = $ues_user->sections(true);
}

$owned_sections = array_filter($pre_sections, $filter);

$custom_page($PAGE);

$form = new reprocess_form(null, array(
    'id' => $id, 'sections' => $owned_sections, 'type' => $type
));

if ($form->is_cancelled()) {
    redirect($back_url);
} else if ($data = $form->get_data()) {
    $module = array(
        'name' => 'blocks_ues_reprocess',
        'fullpath' => '/blocks/ues_reprocess/js/reprocess.js',
        'requires' => array('base', 'io', 'node')
    );

    $PAGE->requires->js_init_call('M.block_ues_reprocess.init', null, false, $module);

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

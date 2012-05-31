<?php

require_once $CFG->libdir . '/formslib.php';

class reprocess_form extends moodleform {
    function definition() {
        global $USER, $COURSE;

        $m =& $this->_form;

        $stocked = array();

        foreach ($this->_customdata['sections'] as $section) {
            $user_params = array(
                'userid' => $USER->id,
                'sectionid' => $section->id,
                'primary_flag' => 1,
                'status' => ues::ENROLLED
            );

            $primary = ues_teacher::get($user_params);

            if ($primary and $primary->userid != $USER->id) {
                continue;
            }

            $semid = $section->semesterid;
            $couid = $section->courseid;

            if (!isset($stocked[$semid])) {
                $stocked[$semid] = array();
            }

            if (!isset($stocked[$semid][$couid])) {
                $stocked[$semid][$couid] = array();
            }

            $stocked[$semid][$couid][$section->id] = $section;
        }

        foreach ($stocked as $semesterid => $courses) {
            $semester = ues_semester::get(array('id' => $semesterid));

            $sem_name = "$semester->year $semester->name $semester->session_key";
            $m->addElement('header', 'sem_header_' . $semesterid, $sem_name);

            foreach ($courses as $courseid => $sections) {
                $course = ues_course::get(array('id' => $courseid));

                $name = "<strong>$sem_name $course->department $course->cou_number</strong>";
                $m->addElement('checkbox', 'course_'.$semesterid.'_' . $courseid, $name, '');

                foreach ($sections as $sectionid => $section) {
                    if (empty($section->reprocessed)) {
                        $m->addElement('checkbox', 'section_'.$sectionid, 'Section ' . $section->sec_number, '');

                        $m->disabledIf('section_'.$sectionid, 'course_'.$semesterid.'_'.$courseid, 'checked');
                    } else {
                        $m->addElement('static', 'section_'.$sectionid, 'Section ' . $section->sec_number, 'X');
                    }
                }

                $m->addElement('static', 'spacer_'.$semesterid.'_'.$courseid, '', '');
            }
        }

        $m->addElement('hidden', 'id', $this->_customdata['id']);
        $m->addElement('hidden', 'type', $this->_customdata['type']);

        $buttons = array(
            $m->createElement('submit', 'reprocess', get_string('reprocess', 'block_ues_reprocess')),
            $m->createElement('cancel')
        );

        $m->addGroup($buttons, 'subgroup', '', array(' '), false);
        $m->closeHeaderBefore('subgroup');
    }
}

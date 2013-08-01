<?php

abstract class ues_reprocess {
    public static function select($sections) {
        global $OUTPUT;

        echo html_writer::start_tag('pre');

        foreach ($sections as $section) {
            ues_teacher::reset_status($section, ues::PROCESSED);
            ues_student::reset_status($section, ues::PROCESSED);
        }

        ues::reprocess_sections($sections, false);

        ues::reset_unenrollments($sections);

        // Build processed entries
        foreach ($sections as $section) {
            $section->save_meta(array('section_reprocessed' => true));
        }

        echo html_writer::end_tag('pre');
    }

    public static function post($owned_sections, $data) {
        $sections = array();

        foreach ($data as $key => $checked) {
            if (preg_match('/course_(\d+)_(\d+)/', $key, $matches)) {
                $in_ues_course = function($section) use ($matches) {

                    $same_semester = $section->semesterid == $matches[1];
                    $same_course = $section->courseid == $matches[2];

                    $same = ($same_semester and $same_course);

                    return ($same and empty($section->section_reprocessed));
                };

                $sections += array_filter($owned_sections, $in_ues_course);
                continue;
            }

            if (preg_match('/section_(\d+)/', $key, $matches)) {
                $sections[$matches[1]] = $owned_sections[$matches[1]];
            }
        }

        return $sections;
    }
}


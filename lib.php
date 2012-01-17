<?php

abstract class ues_reprocess {
    function select($sections) {
        global $OUTPUT;

        echo $OUTPUT->box_start();
        echo html_writer::start_tag('pre');

        foreach ($sections as $section) {
            ues_teacher::reset_status($section, ues::PROCESSED);
            ues_student::reset_status($section, ues::PROCESSED);
        }

        ues::reprocess_sections($sections, false);

        // Build processed entries
        foreach ($sections as $section) {
            $section->save_meta(array('reprocessed' => true));
        }

        echo html_writer::end_tag('pre');
        echo $OUTPUT->box_end();
    }

    function post($found, $data) {
        $sections = array();

        foreach (get_object_vars($data) as $key => $checked) {
            if (preg_match('/course_(\d+)/', $key, $matches)) {
                $in_ues_course = function($section) use ($matches) {
                    $same = $section->courseid == $matches[1];
                    return ($same and empty($section->reprocessed));
                };

                $sections += array_filter($found, $in_ues_course);
                continue;
            }

            if (preg_match('/section_(\d+)/', $key, $matches)) {
                $sections[$matches[1]] = $found[$matches[1]];
            }
        }

        return $sections;
    }
}


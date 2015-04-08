<?php

/**
 * TeachersActivity report renderer.
 *
 * @package    report_teachersactivity
 * @copyright  2015 Andraž Prinčič <atletek@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die;

class report_teachersactivity_renderer extends plugin_renderer_base {

    protected function render_report_teachersactivity(report_teachersactivity $reportteachersactivity) {

        switch ($reportteachersactivity->reporttype) {
            case 1:
            case 3:
                $this->report_selector_form_course_selector($reportteachersactivity);
                break;

            case 6:
            case 7:
                $this->report_selector_form_teacher_selector($reportteachersactivity);
                break;
            
            default:
                $this->report_selector_form($reportteachersactivity);
                break;
        }
    }

    /**
     * This function is used to generate and display selector form
     *
     * @param report_teachersactivity $reportteachersactivity teachersactivity report.
     */
    public function report_selector_form(report_teachersactivity $reportteachersactivity) {
        echo html_writer::start_tag('form',
                array('class' => 'reportbadgesselecform', 'action' => $reportteachersactivity->url, 'method' => 'get'));
        echo html_writer::start_div();

        echo html_writer::empty_tag('input',
                array('type' => 'hidden', 'name' => 'id', 'value' => $reportteachersactivity->courseid));

        echo html_writer::label(get_string('selectreporttype', 'report_teachersactivity'), 'menureader', false);
        echo html_writer::select($reportteachersactivity->getAvailablereports(), 'reporttype',
                $reportteachersactivity->reporttype, false);

        echo html_writer::empty_tag('input',
                array('type' => 'submit', 'value' => get_string('showreport', 'report_teachersactivity')));

        echo html_writer::end_div();
        echo html_writer::end_tag('form');
    }

    public function report_selector_form_course_selector(report_teachersactivity $reportteachersactivity) {
        global $DB;
        
        $ccid = $DB->get_field('course', 'category', array('id' => $reportteachersactivity->courseid));
        $courses = $DB->get_records('course', array('category' => $ccid));
        
        $coursesList[] = array();

        foreach ($courses as $value) {
            $coursesList[$value->id] = $value->shortname;
        }
        
        echo html_writer::start_tag('form',
                array('class' => 'reportbadgesselecform', 'action' => $reportteachersactivity->url, 'method' => 'get'));
        echo html_writer::start_div();

        echo html_writer::label(get_string('selectreporttype', 'report_teachersactivity'), 'menureader', false);
        echo html_writer::select($reportteachersactivity->getAvailablereports(), 'reporttype',
                $reportteachersactivity->reporttype, false);

        echo html_writer::label(get_string('selectcourse', 'report_teachersactivity'), 'menureader', false);
        echo html_writer::select($coursesList, 'id', $reportteachersactivity->courseid, false);
        
        echo html_writer::empty_tag('input',
                array('type' => 'submit', 'value' => get_string('showreport', 'report_teachersactivity')));

        echo html_writer::end_div();
        echo html_writer::end_tag('form');
    }

    public function report_selector_form_teacher_selector(report_teachersactivity $reportteachersactivity) {
        global $DB;
        
        $ccid = $DB->get_field('course', 'category', array('id' => $reportteachersactivity->courseid));
        
        
        $users = $DB->get_records_sql('SELECT DISTINCT
                                            u.id, u.firstname, u.lastname
                                        FROM
                                            {user} u
                                                INNER JOIN
                                            {role_assignments} ra ON ra.userid = u.id
                                                INNER JOIN
                                            {role} r ON ra.roleid = r.id
                                                INNER JOIN
                                            {context} con ON ra.contextid = con.id
                                                INNER JOIN
                                            {course} c ON c.id = con.instanceid
                                                AND con.contextlevel = 50
                                        WHERE
                                            r.id = 3 AND c.category = :ccid', 
                array('ccid' => $ccid));
        
        $usersList[] = get_string('selectuser', 'report_teachersactivity');

        foreach ($users as $value) {
            $usersList[$value->id] = "{$value->firstname} {$value->lastname}";
        }
        
        echo html_writer::start_tag('form',
                array('class' => 'reportbadgesselecform', 'action' => $reportteachersactivity->url, 'method' => 'get'));
        echo html_writer::start_div();
        
        echo html_writer::empty_tag('input',
                array('type' => 'hidden', 'name' => 'id', 'value' => $reportteachersactivity->courseid));

        echo html_writer::label(get_string('selectreporttype', 'report_teachersactivity'), 'menureader', false);
        echo html_writer::select($reportteachersactivity->getAvailablereports(), 'reporttype',
                $reportteachersactivity->reporttype, false);

        echo html_writer::label(get_string('selectuser', 'report_teachersactivity'), 'menureader', false);
        echo html_writer::select($usersList, 'teacherid', $reportteachersactivity->teacherid, false);
        
        echo html_writer::empty_tag('input',
                array('type' => 'submit', 'value' => get_string('showreport', 'report_teachersactivity')));

        echo html_writer::end_div();
        echo html_writer::end_tag('form');
    }
    
}

<?php

/**
 * TeachersActivity report renderer.
 *
 * @package    report_multicourseactivity
 * @copyright  2015 Andraž Prinčič <atletek@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die;

class report_multicourseactivity_renderer extends plugin_renderer_base {

    protected function render_report_multicourseactivity(report_multicourseactivity $reportmulticourseactivity) {

        switch ($reportmulticourseactivity->reporttype) {
            case 1:
            case 3:
                $this->report_selector_form_course_selector($reportmulticourseactivity);
                break;
            
            case 2:
                $this->report_selector_form_date_selector($reportmulticourseactivity);
                break;

            case 6:
                $this->report_selector_form_teacher_selector_date($reportmulticourseactivity);
                break;
            case 7:
                $this->report_selector_form_teacher_selector($reportmulticourseactivity);
                break;
            
            case 8:
                $this->report_selector_form_date_selector($reportmulticourseactivity);
                break;
            
            default:
                $this->report_selector_form($reportmulticourseactivity);
                break;
        }
    }

    /**
     * This function is used to generate and display selector form
     *
     * @param report_multicourseactivity $reportmulticourseactivity multicourseactivity report.
     */
    public function report_selector_form(report_multicourseactivity $reportmulticourseactivity) {
        echo html_writer::start_tag('form',
                array('class' => 'reportbadgesselecform', 'action' => $reportmulticourseactivity->url, 'method' => 'get'));
        echo html_writer::start_div();

        echo html_writer::empty_tag('input',
                array('type' => 'hidden', 'name' => 'id', 'value' => $reportmulticourseactivity->courseid));

        echo html_writer::label(get_string('selectreporttype', 'report_multicourseactivity'), 'menureader', false);
        echo html_writer::select($reportmulticourseactivity->getAvailablereports(), 'reporttype',
                $reportmulticourseactivity->reporttype, false);

        echo html_writer::empty_tag('input',
                array('type' => 'submit', 'value' => get_string('showreport', 'report_multicourseactivity')));

        echo html_writer::end_div();
        echo html_writer::end_tag('form');
    }

    public function report_selector_form_course_selector(report_multicourseactivity $reportmulticourseactivity) {
        global $DB;
        
        $ccid = $DB->get_field('course', 'category', array('id' => $reportmulticourseactivity->courseid));
        $courses = $DB->get_records('course', array('category' => $ccid));
        
        $coursesList[] = array();

        foreach ($courses as $value) {
            $coursesList[$value->id] = $value->shortname;
        }
        
        echo html_writer::start_tag('form',
                array('class' => 'reportbadgesselecform', 'action' => $reportmulticourseactivity->url, 'method' => 'get'));
        echo html_writer::start_div();

        echo html_writer::label(get_string('selectreporttype', 'report_multicourseactivity'), 'menureader', false);
        echo html_writer::select($reportmulticourseactivity->getAvailablereports(), 'reporttype',
                $reportmulticourseactivity->reporttype, false);

        echo html_writer::label(get_string('selectcourse', 'report_multicourseactivity'), 'menureader', false);
        echo html_writer::select($coursesList, 'id', $reportmulticourseactivity->courseid, false);
        
        echo html_writer::empty_tag('input',
                array('type' => 'submit', 'value' => get_string('showreport', 'report_multicourseactivity')));

        echo html_writer::end_div();
        echo html_writer::end_tag('form');
    }

    public function report_selector_form_teacher_selector(report_multicourseactivity $reportmulticourseactivity) {
        global $DB;
        
        $ccid = $DB->get_field('course', 'category', array('id' => $reportmulticourseactivity->courseid));
        
        
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
        
        $usersList[] = get_string('selectuser', 'report_multicourseactivity');

        foreach ($users as $value) {
            $usersList[$value->id] = "{$value->firstname} {$value->lastname}";
        }
        
        echo html_writer::start_tag('form',
                array('class' => 'reportbadgesselecform', 'action' => $reportmulticourseactivity->url, 'method' => 'get'));
        echo html_writer::start_div();
        
        echo html_writer::empty_tag('input',
                array('type' => 'hidden', 'name' => 'id', 'value' => $reportmulticourseactivity->courseid));

        echo html_writer::label(get_string('selectreporttype', 'report_multicourseactivity'), 'menureader', false);
        echo html_writer::select($reportmulticourseactivity->getAvailablereports(), 'reporttype',
                $reportmulticourseactivity->reporttype, false);

        echo html_writer::label(get_string('selectuser', 'report_multicourseactivity'), 'menureader', false);
        echo html_writer::select($usersList, 'teacherid', $reportmulticourseactivity->teacherid, false);
        
        echo html_writer::empty_tag('input',
                array('type' => 'submit', 'value' => get_string('showreport', 'report_multicourseactivity')));

        echo html_writer::end_div();
        echo html_writer::end_tag('form');
    }
    
    public function report_selector_form_teacher_selector_date(report_multicourseactivity $reportmulticourseactivity) {
        global $DB;
        
        $dayselector = html_writer::select_time('days', 'startday', $reportmulticourseactivity->startdate);
        $monthselector = html_writer::select_time('months', 'startmonth', $reportmulticourseactivity->startdate);
        $yearselector = html_writer::select_time('years', 'startyear', $reportmulticourseactivity->startdate);
        $startdatetimeoutput = $dayselector . $monthselector . $yearselector;
        
        $dayselector = html_writer::select_time('days', 'endday', $reportmulticourseactivity->enddate);
        $monthselector = html_writer::select_time('months', 'endmonth', $reportmulticourseactivity->enddate);
        $yearselector = html_writer::select_time('years', 'endyear', $reportmulticourseactivity->enddate);
        $enddatetimeoutput = $dayselector . $monthselector . $yearselector;
        
        $ccid = $DB->get_field('course', 'category', array('id' => $reportmulticourseactivity->courseid));
        
        
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
        
        $usersList[] = get_string('selectuser', 'report_multicourseactivity');

        foreach ($users as $value) {
            $usersList[$value->id] = "{$value->firstname} {$value->lastname}";
        }
        
        echo html_writer::start_tag('form',
                array('class' => 'reportbadgesselecform', 'action' => $reportmulticourseactivity->url, 'method' => 'get'));
        echo html_writer::start_div();
        
        echo html_writer::empty_tag('input',
                array('type' => 'hidden', 'name' => 'id', 'value' => $reportmulticourseactivity->courseid));

        echo html_writer::label(get_string('selectreporttype', 'report_multicourseactivity'), 'menureader', false);
        echo html_writer::select($reportmulticourseactivity->getAvailablereports(), 'reporttype',
                $reportmulticourseactivity->reporttype, false);

        echo html_writer::label(get_string('selectuser', 'report_multicourseactivity'), 'menureader', false);
        echo html_writer::select($usersList, 'teacherid', $reportmulticourseactivity->teacherid, false);
        
        echo html_writer::label(get_string('fromdate', 'report_multicourseactivity'), 'menureader', false);
        echo $startdatetimeoutput;
        
        echo html_writer::label(get_string('todate', 'report_multicourseactivity'), 'menureader', false);
        echo $enddatetimeoutput;  
        
        echo html_writer::empty_tag('input',
                array('type' => 'submit', 'value' => get_string('showreport', 'report_multicourseactivity')));

        echo html_writer::end_div();
        echo html_writer::end_tag('form');
    }
    
    public function report_selector_form_date_selector(report_multicourseactivity $reportmulticourseactivity) {
        
        $dayselector = html_writer::select_time('days', 'startday', $reportmulticourseactivity->startdate);
        $monthselector = html_writer::select_time('months', 'startmonth', $reportmulticourseactivity->startdate);
        $yearselector = html_writer::select_time('years', 'startyear', $reportmulticourseactivity->startdate);
        $startdatetimeoutput = $dayselector . $monthselector . $yearselector;
        
        $dayselector = html_writer::select_time('days', 'endday', $reportmulticourseactivity->enddate);
        $monthselector = html_writer::select_time('months', 'endmonth', $reportmulticourseactivity->enddate);
        $yearselector = html_writer::select_time('years', 'endyear', $reportmulticourseactivity->enddate);
        $enddatetimeoutput = $dayselector . $monthselector . $yearselector;
        
        echo html_writer::start_tag('form',
                array('class' => 'reportbadgesselecform', 'action' => $reportmulticourseactivity->url, 'method' => 'get'));
        echo html_writer::start_div();
        
        echo html_writer::empty_tag('input',
                array('type' => 'hidden', 'name' => 'id', 'value' => $reportmulticourseactivity->courseid));

        echo html_writer::label(get_string('selectreporttype', 'report_multicourseactivity'), 'menureader', false);
        echo html_writer::select($reportmulticourseactivity->getAvailablereports(), 'reporttype',
                $reportmulticourseactivity->reporttype, false);

        echo html_writer::label(get_string('fromdate', 'report_multicourseactivity'), 'menureader', false);
        echo $startdatetimeoutput;
        
        echo html_writer::label(get_string('todate', 'report_multicourseactivity'), 'menureader', false);
        echo $enddatetimeoutput;        
        
        echo html_writer::empty_tag('input',
                array('type' => 'submit', 'value' => get_string('showreport', 'report_multicourseactivity')));

        echo html_writer::end_div();
        echo html_writer::end_tag('form');
    }
    
}

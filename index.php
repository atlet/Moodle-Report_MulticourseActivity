<?php

/**
 * TeachersActivity index file.
 *
 * @package    report_multicourseactivity
 * @copyright  2015 Andraž Prinčič <atletek@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require('../../config.php');
require_once($CFG->dirroot.'/report/multicourseactivity/lib.php');
require_once($CFG->dirroot.'/report/multicourseactivity/renderable.php');
$reporttype = optional_param('reporttype', '', PARAM_INT); // Which report list to display.
$teacherid = optional_param('teacherid', '', PARAM_INT); // Which report list to display.

$startday = optional_param('startday', 0, PARAM_INT);
$startmonth = optional_param('startmonth', 0, PARAM_INT);
$startyear = optional_param('startyear', 0, PARAM_INT);

$endday = optional_param('endday', 0, PARAM_INT);
$endmonth = optional_param('endmonth', 0, PARAM_INT);
$endyear = optional_param('endyear', 0, PARAM_INT);

if ($startday !== 0 && $startmonth !== 0 && $startyear !== 0) {
    $currentstarttime = mktime(0, 0, 0, $startmonth, $startday, $startyear);
} else {
    $currentstarttime = mktime(0, 0, 0, 1, 1, date('Y'));
}

if ($endday !== 0 && $endmonth !== 0 && $endyear !== 0) {
    $currentendtime = mktime(23, 59, 59, $endmonth, $endday, $endyear);
} else {
    $currentendtime = time();
}

$id = required_param('id', PARAM_INT);// Course ID.
$params = array();
if ($id !== 0) {
    $params['id'] = $id;
}

if ($reporttype !== 0) {
    $params['reporttype'] = $reporttype;
}

if ($teacherid !== 0) {
    $params['teacherid'] = $teacherid;
}

$url = new moodle_url("/report/multicourseactivity/index.php", $params);

$PAGE->set_url('/report/multicourseactivity/index.php', $params);
$PAGE->set_pagelayout('report');

$course = null;
if ($id) {
    $course = $DB->get_record('course', array('id' => $id), '*', MUST_EXIST);
    require_login($course);
    $context = context_course::instance($course->id);
} else {
    require_login();
    $context = context_system::instance();
    $PAGE->set_context($context);
}

require_capability('report/multicourseactivity:view', $context);

$output = $PAGE->get_renderer('report_multicourseactivity');
$submissionwidget = new report_multicourseactivity($id, $url, $reporttype, $teacherid, $currentstarttime, $currentendtime);

echo $output->header();
echo $output->render($submissionwidget);
switch ($reporttype) {
    case 1:
        $submissionwidget->show_table_list_course_activity();
        break;   
    
    case 2:
        $submissionwidget->show_table_list_learners_activity();
        break;
    
    case 3:
        $submissionwidget->show_table_list_performers_by_classrooms();
        break;
    
    case 4:
        $submissionwidget->show_table_list_performers_activity();
        break;
    
    case 5:
        $submissionwidget->show_table_list_activity_by_category();
        break;
    
    case 6:
        $submissionwidget->show_table_list_activities_of_participants();
            break;
        
    case 7:
        $submissionwidget->show_table_list_teachers_activity();
        break;
    
    case 8:
        $submissionwidget->show_table_list_logins();
        break;
    
    default:
        break;
}

echo $output->footer();
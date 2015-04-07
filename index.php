<?php

/**
 * TeachersActivity index file.
 *
 * @package    report_teachersactivity
 * @copyright  2015 Andraž Prinčič <atletek@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require('../../config.php');
require_once($CFG->dirroot.'/report/teachersactivity/lib.php');
require_once($CFG->dirroot.'/report/teachersactivity/renderable.php');
$reporttype = optional_param('reporttype', '', PARAM_INT); // Which report list to display.

$id = optional_param('id', 0, PARAM_INT);// Course ID.
$params = array();
if ($id !== 0) {
    $params['id'] = $id;
}

if ($reporttype !== 0) {
    $params['reporttype'] = $reporttype;
}

$url = new moodle_url("/report/teachersactivity/index.php", $params);

$PAGE->set_url('/report/teachersactivity/index.php', $params);
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

require_capability('report/teachersactivity:view', $context);

$output = $PAGE->get_renderer('report_teachersactivity');
$submissionwidget = new report_teachersactivity($id, $url, $reporttype);

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
    
    default:
        break;
}

echo $output->footer();
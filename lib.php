<?php

/**
 * TeachersActivity lib file.
 *
 * @package    report_teachersactivity
 * @copyright  2015 Andraž Prinčič <atletek@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

function report_teachersactivity_extend_navigation_course($navigation, $course, $context) {
    if (has_capability('report/teachersactivity:view', $context)) {
        $url = new moodle_url('/report/teachersactivity/index.php', array('id' => $course->id));
        $navigation->add(get_string('pluginname', 'report_teachersactivity'), $url, navigation_node::TYPE_SETTING, null,
                null, new pix_icon('i/report', ''));
    }
}

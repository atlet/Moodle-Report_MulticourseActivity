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
        $this->report_selector_form($reportteachersactivity);
    }

    /**
     * This function is used to generate and display selector form
     *
     * @param report_teachersactivity $reportteachersactivity teachersactivity report.
     */
    public function report_selector_form(report_teachersactivity $reportteachersactivity) {

        global $DB;

        echo html_writer::start_tag('form',
                array('class' => 'reportbadgesselecform', 'action' => $reportteachersactivity->url, 'method' => 'get'));
        echo html_writer::start_div();

        echo html_writer::empty_tag('input',
                array('type' => 'hidden', 'name' => 'id', 'value' => $reportteachersactivity->courseid));

        echo html_writer::label(get_string('selectreporttype', 'report_teachersactivity'), 'menureader', false);
        echo html_writer::select($reportteachersactivity->getAvailablereports(), 'reporttype', $reportteachersactivity->reporttype, false);

        echo html_writer::empty_tag('input',
                array('type' => 'submit', 'value' => get_string('showreport', 'report_teachersactivity')));

        echo html_writer::end_div();
        echo html_writer::end_tag('form');
    }

}

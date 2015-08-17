<?php

/**
 * TeachersActivity table for displaying list of activities of participants.
 *
 * @package    report_multicourseactivity
 * @copyright  2015 Andraž Prinčič <atletek@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die;

require_once("$CFG->libdir/tablelib.php");

class list_activities_of_participants extends table_sql {

    public function __construct($uniqueid) {
        parent::__construct($uniqueid);

        $this->set_attribute('class', 'reportlog generaltable generalbox');

        $this->define_columns(array('shortname', 'st_forumov', 'st_diskusij', 'st_komentarjev', 'st_nalog', 'st_oddanih_nal', 'st_ogledov'));
        $this->define_headers(array(
            get_string('shortname', 'report_multicourseactivity'),
            get_string('st_forumov', 'report_multicourseactivity'),
            get_string('st_diskusij', 'report_multicourseactivity'),
            get_string('st_komentarjev', 'report_multicourseactivity'),
            get_string('st_nalog', 'report_multicourseactivity'),
            get_string('st_oddanih_nal', 'report_multicourseactivity'),
            get_string('st_ogledov', 'report_multicourseactivity')
                )
        );
        $this->collapsible(false);
        $this->sortable(true);
        $this->pageable(true);
    }
    
    function other_cols($colname, $value) {        
        if ($colname == 'shortname') {            
            $caurl = new moodle_url('/course/view.php', array('id' => $value->id_ucilnice));            

            $ret = '<a href="' . $caurl . '">' . $value->shortname . '</a>';

            return $ret;
        }
    }

    function query_db($pagesize, $useinitialsbar = true) {
        global $DB;
        if (!$this->is_downloading()) {
            if ($this->countsql === NULL) {
                $this->countsql = 'SELECT COUNT(1) FROM (SELECT ' . $this->sql->fields . '  FROM ' . $this->sql->from . ' WHERE ' . $this->sql->where . ' ) AS sq; ';
                $this->countparams = $this->sql->params;
            }
            $grandtotal = $DB->count_records_sql($this->countsql, $this->countparams);
            if ($useinitialsbar && !$this->is_downloading()) {
                $this->initialbars($grandtotal > $pagesize);
            }

            list($wsql, $wparams) = $this->get_sql_where();
            if ($wsql) {
                $this->countsql = 'SELECT COUNT(1) FROM (SELECT ' . $this->sql->fields . '  FROM ' . $this->sql->from . ' WHERE ' . $this->sql->where . ' AND ' . $wsql . ' GROUP BY b.name) AS sq; ';
                $this->countparams = array_merge($this->countparams, $wparams);

                $this->sql->where .= ' AND ' . $wsql;
                $this->sql->params = array_merge($this->sql->params, $wparams);

                $total = $DB->count_records_sql($this->countsql, $this->countparams);
            } else {
                $total = $grandtotal;
            }

            $this->pagesize($pagesize, $total);
        }

        // Fetch the attempts
        $sort = $this->get_sql_sort();
        if ($sort) {
            $sort = "ORDER BY $sort";
        }
        $sql = "SELECT
                  {$this->sql->fields}
                  FROM {$this->sql->from}
                  WHERE {$this->sql->where}
                  {$sort}";

        if (!$this->is_downloading()) {
            $this->rawdata = $DB->get_records_sql($sql, $this->sql->params, $this->get_page_start(),
                    $this->get_page_size());
        } else {
            $this->rawdata = $DB->get_records_sql($sql, $this->sql->params);
        }
    }

}

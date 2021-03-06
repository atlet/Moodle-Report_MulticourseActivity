<?php

/**
 * TeachersActivity table for displaying list of learners activity.
 *
 * @package    report_multicourseactivity
 * @copyright  2015 Andraž Prinčič <atletek@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die;

require_once("$CFG->libdir/tablelib.php");

class list_learners_activity_n_days extends table_sql {

    public function __construct($uniqueid) {
        parent::__construct($uniqueid);

        $this->set_attribute('class', 'reportlog generaltable generalbox');

        $this->define_columns(array('userid', 'username', 'zadnji_dostop', 'rcourseid', 'nazadnje_dostopana', 'st_pregledovanih_dni', 'st_aktivnih_dni', 'st_obiskanh_ucilnic', 'st_vstopov_v_ucil'));
        $this->define_headers(array(
            get_string('userid', 'report_multicourseactivity'),
            get_string('username', 'report_multicourseactivity'),
            get_string('zadnji_dostop', 'report_multicourseactivity'),
            get_string('rcourseid', 'report_multicourseactivity'),
            get_string('nazadnje_dostopana', 'report_multicourseactivity'),
            get_string('st_pregledovanih_dni', 'report_multicourseactivity'),
            get_string('st_aktivnih_dni', 'report_multicourseactivity'),
            get_string('st_obiskanh_ucilnic', 'report_multicourseactivity'),
            get_string('st_vstopov_v_ucil', 'report_multicourseactivity')
                )
        );
        $this->collapsible(false);
        $this->sortable(true);
        $this->pageable(true);
    }

    function other_cols($colname, $value) {
        if ($colname == 'nazadnje_dostopana') {            
            $caurl = new moodle_url('/course/view.php', array('id' => $value->rcourseid));            

            $ret = '<a href="' . $caurl . '">' . $value->nazadnje_dostopana . '</a>';

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


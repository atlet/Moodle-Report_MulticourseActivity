<?php

/**
 * TeachersActivity table for displaying list of performery by classrooms.
 *
 * @package    report_teachersactivity
 * @copyright  2015 Andraž Prinčič <atletek@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die;

require_once("$CFG->libdir/tablelib.php");

class list_performers_by_classrooms extends table_sql {

    public function __construct($uniqueid) {
        parent::__construct($uniqueid);

        $this->set_attribute('class', 'reportlog generaltable generalbox');

        $this->define_columns(array('shortname', 'firstname', 'lastname'));
        $this->define_headers(array(
            get_string('shortname', 'report_teachersactivity'),
            get_string('firstname', 'report_teachersactivity'),
            get_string('lastname', 'report_teachersactivity')
                )
        );
        $this->collapsible(false);
        $this->sortable(true);
        $this->pageable(true);
    }

    function other_cols($colname, $value) {
        
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
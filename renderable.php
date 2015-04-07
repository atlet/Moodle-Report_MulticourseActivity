<?php

/**
 * TeachersActivity report renderable.
 *
 * @package    report_teachersactivity
 * @copyright  2015 Andraž Prinčič <atletek@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die;

require_once($CFG->dirroot . '/report/teachersactivity/classes/report_teachersactivity_list_course_activity.php');
require_once($CFG->dirroot . '/report/teachersactivity/classes/report_teachersactivity_list_learners_activity.php');
require_once($CFG->dirroot . '/report/teachersactivity/classes/report_teachersactivity_list_performers_by_classrooms.php');

class report_teachersactivity implements renderable {

    /** @var moodle_url url of report page */
    public $url;

    /** @var string selected report list to display */
    public $reporttype = null;
    public $courseid;
    public $table;
    private $whereOptions = array();
    private $whereParameters = array();

    /**
     * Constructor.
     *
     * @param moodle_url|string $url (optional) page url.
     * @param string $reporttype (optional) which report list to display.
     */
    public function __construct($courseid = NULL, $url = "", $reporttype = "") {

        global $PAGE;

        $this->courseid = $courseid;

        $this->whereOptions[] = 'c.id = :courseid';
        $this->whereParameters['courseid'] = $courseid;

        // Use page url if empty.
        if (empty($url)) {
            $this->url = new moodle_url($PAGE->url);
        } else {
            $this->url = new moodle_url($url);
        }

        if (empty($reporttype)) {
            $rtypes = $this->getAvailablereports();
            if (!empty($rtypes)) {
                reset($rtypes);
                $reporttype = key($rtypes);
            } else {
                $reporttype = null;
            }
        }

        $this->reporttype = $reporttype;
    }

    public function getAvailablereports() {
        return array(
            1 => get_string('listcourseactivity', 'report_teachersactivity'),   
            2 => get_string('listlearnersactivity', 'report_teachersactivity'),
            3 => get_string('listperformersbyclassrooms', 'report_teachersactivity')
        );
    }
    
    public function show_table_list_performers_by_classrooms() {
        $fields = "u.id, c.shortname, u.firstname, u.lastname";
        
        $this->whereOptions[] = 'r.id = 3';
        
        $this->table = new list_performers_by_classrooms('report_log');
        $this->table->set_sql($fields,
                "{user} u
                    INNER JOIN
                {role_assignments} ra ON ra.userid = u.id
                    INNER JOIN
                {role} r ON ra.roleid = r.id
                    INNER JOIN
                {context} con ON ra.contextid = con.id
                    INNER JOIN
                {course} c ON c.id = con.instanceid
                    AND con.contextlevel = 50", 
                implode(' AND ', $this->whereOptions),
                $this->whereParameters);
        
        $this->table->define_baseurl($this->url);
        $this->table->is_downloadable(true);
        $this->table->show_download_buttons_at(array(TABLE_P_BOTTOM));
        $this->table->out(25, true);
    }
    
    public function show_table_list_learners_activity () {
        global $CFG, $DB;
        
        $ccid = $DB->get_field('course', 'category', array('id' => $this->courseid));
        
        $fields = "stevilo_nalog.id_ucilnice,
                    stevilo_nalog.ime AS ime_ucilnice,
                    IF(stevilo_udel.stevilo IS NULL,
                        0,
                        stevilo_udel.stevilo) AS stevilo_udel,
                    IF(stevilo_nalog.stevilo IS NULL,
                        0,
                        stevilo_nalog.stevilo) AS stevilo_nalog,
                    IF(stevilo_oddanih_nalog.stevilo IS NULL,
                        0,
                        stevilo_oddanih_nalog.stevilo) AS stevilo_oddanih_nalog,
                    stevilo_kvizov.stevilo AS stevilo_kvizov,
                    IF(kvizi.stevilo_poizkusov IS NULL,
                        0,
                        kvizi.stevilo_poizkusov) AS stevilo_resevanj_kvizov,
                    IF((diskusije.stevilo_diskusij + komentarji.stevilo_komentarjev) IS NULL,
                        0,
                        (diskusije.stevilo_diskusij + komentarji.stevilo_komentarjev)) AS diskusije_in_komentarji";
        
        $this->table = new list_learners_activity('report_log');
        $this->table->set_sql($fields,
                "{course} c
                    LEFT OUTER JOIN
                (SELECT 
                    c.id AS id_kviz, IF(q.id IS NULL, 0, COUNT(*)) AS stevilo
                FROM
                    {course} c
                LEFT OUTER JOIN {quiz} q ON c.id = q.course
                INNER JOIN {course_categories} cc ON cc.id = c.category
                WHERE
                    cc.id = :ccid1
                GROUP BY c.id) AS stevilo_kvizov ON stevilo_kvizov.id_kviz = c.id
                    LEFT OUTER JOIN
                (SELECT 
                    COUNT(qa.attempt) AS stevilo_poizkusov,
                        q.course AS course_id
                FROM
                    {quiz} q
                INNER JOIN  {quiz_attempts} qa ON q.id = qa.quiz
                INNER JOIN {course} c ON c.id = q.course
                INNER JOIN {course_categories} cc ON cc.id = c.category
                WHERE
                    cc.id = :ccid2
                GROUP BY q.course) AS kvizi ON c.id = kvizi.course_id
                    LEFT OUTER JOIN
                (SELECT 
                    cou.shortname AS ime,
                        cou.id AS id_ucilnice,
                        IF(a.id IS NULL, 0, COUNT(*)) AS stevilo
                FROM
                    {course} cou
                LEFT OUTER JOIN {assign} a ON cou.id = a.course
                INNER JOIN {course_categories} cc ON cc.id = cou.category
                WHERE
                    cc.id = :ccid3
                GROUP BY cou.id) AS stevilo_nalog ON stevilo_nalog.id_ucilnice = c.id
                    LEFT OUTER JOIN
                (SELECT 
                    IF(ass.id IS NULL, 0, COUNT(*)) AS stevilo,
                        c.id AS id_ucilnice
                FROM
                    {assign} a
                INNER JOIN {course} c ON c.id = a.course
                INNER JOIN {course_categories} cc ON cc.id = c.category
                INNER JOIN {assign_submission} ass ON ass.assignment = a.id
                WHERE
                    cc.id = :ccid4 AND ass.status = 'submitted'
                GROUP BY c.id) AS stevilo_oddanih_nalog ON stevilo_oddanih_nalog.id_ucilnice = c.id
                    LEFT OUTER JOIN
                (SELECT 
                    COUNT(fd.id) AS stevilo_diskusij, c.id AS id_ucilnice
                FROM
                    {course} c
                INNER JOIN {course_categories} cc ON cc.id = c.category
                INNER JOIN {forum} f ON c.id = f.course
                INNER JOIN {forum_discussions} fd ON fd.forum = f.id
                WHERE
                    cc.id = :ccid5
                GROUP BY c.id) AS diskusije ON diskusije.id_ucilnice = c.id
                    LEFT OUTER JOIN
                (SELECT 
                    COUNT(*) AS stevilo_komentarjev, c.id AS id_ucilnice
                FROM
                    {course} c
                INNER JOIN {forum} f ON f.course = c.id
                INNER JOIN {course_categories} cc ON cc.id = c.category
                INNER JOIN {forum_discussions} fd ON fd.forum = f.id
                INNER JOIN {forum_posts} fp ON fp.discussion = fd.id
                WHERE
                    cc.id = :ccid6
                GROUP BY c.id) AS komentarji ON komentarji.id_ucilnice = c.id
                    LEFT OUTER JOIN
                (SELECT 
                    c.id AS id_udel, COUNT(*) AS stevilo
                FROM
                    {user} u
                INNER JOIN {role_assignments} ra ON ra.userid = u.id
                INNER JOIN {role} r ON ra.roleid = r.id
                INNER JOIN {context} con ON ra.contextid = con.id
                INNER JOIN {course} c ON c.id = con.instanceid
                    AND con.contextlevel = 50
                INNER JOIN {course_categories} cc ON cc.id = c.category
                WHERE
                    r.id = 5 AND cc.id = :ccid7
                GROUP BY cc.parent , c.shortname) AS stevilo_udel ON stevilo_udel.id_udel = c.id", 
                "stevilo_nalog.ime IS NOT NULL",
                array('ccid1' => $ccid, 'ccid2' => $ccid, 'ccid3' => $ccid, 'ccid4' => $ccid, 'ccid5' => $ccid, 'ccid6' => $ccid, 'ccid7' => $ccid));
        
        $this->table->define_baseurl($this->url);
        $this->table->is_downloadable(true);
        $this->table->show_download_buttons_at(array(TABLE_P_BOTTOM));
        $this->table->out(25, true);
        
    }
    
    public function show_table_list_course_activity () {
        global $CFG;
        
        $fields = "c.id,
                    c.shortname,
                    COUNT(*) AS stevilo_kvizov,
                    SUM(kvizi.stevilo_poizkusov) AS stevilo_resevanj_kvizov,
                    (SELECT 
                            COUNT(*)
                        FROM
                            {assign} a
                                INNER JOIN
                            {course} c ON c.id = a.course
                        WHERE
                            c.id = 8756) AS stevilo_nalog,
                    (SELECT 
                            COUNT(*)
                        FROM
                            {assign} a
                                INNER JOIN
                            {course} c ON c.id = a.course
                                INNER JOIN
                            {assign_submission} ass ON ass.assignment = a.id
                        WHERE
                            c.id = 8756 AND ass.status = 'submitted') AS stevilo_oddanih_nalog,
                    (SELECT 
                            COUNT(*)
                        FROM
                            {course} c
                                INNER JOIN
                            {forum} f ON c.id = f.course
                        WHERE
                            c.id = 8756) AS stevilo_forumov,
                    (SELECT 
                            COUNT(fd.id) AS stevilo_diskusij
                        FROM
                            {course} c
                                INNER JOIN
                            {forum} f ON c.id = f.course
                                INNER JOIN
                            {forum_discussions} fd ON fd.forum = f.id
                        WHERE
                            c.id = 8756) AS stevilo_diskusij,
                    (SELECT 
                            COUNT(*)
                        FROM
                            {forum} f
                                INNER JOIN
                            {forum_discussions} fd ON fd.forum = f.id
                                INNER JOIN
                            {forum_posts} fp ON fp.discussion = fd.id
                        WHERE
                            f.course = 8756) AS stevilo_komentarjev";
        
        $this->table = new list_course_activity('report_log');
        $this->table->set_sql($fields,
                "{course} c
                    INNER JOIN
                (SELECT 
                    q.name AS kviz,
                        COUNT(qa.attempt) AS stevilo_poizkusov,
                        q.course AS course_id
                FROM
                    {quiz} q
                INNER JOIN {quiz_attempts} qa ON q.id = qa.quiz
                GROUP BY q.id , q.name , q.course) AS kvizi ON c.id = kvizi.course_id", 
                implode(' AND ', $this->whereOptions),
                $this->whereParameters);
        
        $this->table->define_baseurl($this->url);
        $this->table->is_downloadable(true);
        $this->table->show_download_buttons_at(array(TABLE_P_BOTTOM));
        $this->table->out(25, true);
    }

}

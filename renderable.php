<?php

/**
 * TeachersActivity report renderable.
 *
 * @package    report_multicourseactivity
 * @copyright  2015 Andraž Prinčič <atletek@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die;

require_once($CFG->dirroot . '/report/multicourseactivity/classes/report_multicourseactivity_list_course_activity.php');
require_once($CFG->dirroot . '/report/multicourseactivity/classes/report_multicourseactivity_list_learners_activity.php');
require_once($CFG->dirroot . '/report/multicourseactivity/classes/report_multicourseactivity_list_performers_by_classrooms.php');
require_once($CFG->dirroot . '/report/multicourseactivity/classes/report_multicourseactivity_list_performers_activity.php');
require_once($CFG->dirroot . '/report/multicourseactivity/classes/report_multicourseactivity_list_activity_by_category.php');
require_once($CFG->dirroot . '/report/multicourseactivity/classes/report_multicourseactivity_list_activities_of_participants.php');
require_once($CFG->dirroot . '/report/multicourseactivity/classes/report_multicourseactivity_list_teachers_activity.php');
require_once($CFG->dirroot . '/report/multicourseactivity/classes/report_multicourseactivity_list_logins.php');

class report_multicourseactivity implements renderable {

    /** @var moodle_url url of report page */
    public $url;

    /** @var string selected report list to display */
    public $reporttype = null;
    public $teacherid = NULL;
    public $courseid;
    public $table;
    public $startdate;
    public $enddate;
    private $whereOptions = array();
    private $whereParameters = array();

    /**
     * Constructor.
     *
     * @param moodle_url|string $url (optional) page url.
     * @param string $reporttype (optional) which report list to display.
     */
    public function __construct($courseid = NULL, $url = "", $reporttype = "", $teacherid = "", $startdate = "", $enddate = "") {

        global $PAGE;

        $this->courseid = $courseid;
        $this->teacherid = $teacherid;

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
        
        if (empty($startdate)) {
            $this->startdate = mktime(0, 0, 0, 1, 1, date('Y'));
        } else {
            $this->startdate = $startdate;
        }
        
        if (empty($enddate)) {
            $this->enddate = time();
        } else {
            $this->enddate = $enddate;
        }

        $this->reporttype = $reporttype;
    }

    public function getAvailablereports() {
        return array(
            1 => get_string('listcourseactivity', 'report_multicourseactivity'),   
            2 => get_string('listlearnersactivity', 'report_multicourseactivity'),
            3 => get_string('listperformersbyclassrooms', 'report_multicourseactivity'),
            4 => get_string('listperformersactivity', 'report_multicourseactivity'),
            5 => get_string('listactivitybycategory', 'report_multicourseactivity'),
            6 => get_string('listactivitiesofparticipants', 'report_multicourseactivity'),
            7 => get_string('listmulticourseactivity', 'report_multicourseactivity'),
            8 => get_string('listlogins', 'report_multicourseactivity'),
        );
    }
    
    // Vse prijave in unikatne prijave
    public function show_table_list_logins() {
        $fields = "CONCAT(MONTH(FROM_UNIXTIME(timecreated)) , DAY(FROM_UNIXTIME(timecreated)) , YEAR(FROM_UNIXTIME(timecreated))) AS id,
            COUNT(userid) AS 'logins',
                    COUNT(DISTINCT userid) AS 'uniqu_logins',
                    timecreated";
        
        $this->table = new list_logins('report_log');
        $this->table->set_sql($fields,
                "{logstore_standard_log}",
                "timecreated > :startdate
                    AND timecreated <= :enddate
                    AND action = 'loggedin'
            GROUP BY MONTH(FROM_UNIXTIME(timecreated)) , DAY(FROM_UNIXTIME(timecreated)) , YEAR(FROM_UNIXTIME(timecreated))
            ORDER BY MONTH(FROM_UNIXTIME(timecreated)) , DAY(FROM_UNIXTIME(timecreated)) , YEAR(FROM_UNIXTIME(timecreated))",
                array('startdate' => $this->startdate, 'enddate' => $this->enddate));
        
        $this->table->define_baseurl($this->url);
        $this->table->is_downloadable(true);
        $this->table->show_download_buttons_at(array(TABLE_P_BOTTOM));
        $this->table->out(25, true);
    }
    
    // Aktivnosti učitelja
    public function show_table_list_teachers_activity() {
        $fields = "c.id, 
                    u.firstname AS ime,
                    u.lastname AS priimek,
                    c.shortname,
                    IF(urejanje.nazadnje_urejanje IS NULL,
                        'nikoli ni urejal/a',
                        urejanje.nazadnje_urejanje) AS nazadnje_urejanje,
                    (SELECT 
                            IF(FROM_UNIXTIME(MAX(la.timeaccess), ' %h:%i:%s %D %M %Y') IS NULL,
                                    'nikoli ni dostopil/a',
                                    FROM_UNIXTIME(MAX(la.timeaccess), ' %h:%i:%s %D %M %Y'))
                        FROM
                            {user_lastaccess} la
                        WHERE
                            c.id = la.courseid AND la.userid = u.id) AS zadnji_dostop";
        
        $this->table = new list_teachers_activity('report_log');
        $this->table->set_sql($fields,
                "{course} c
                    INNER JOIN
                {context} cx ON c.id = cx.instanceid
                    INNER JOIN
                {role_assignments} ra ON cx.id = ra.contextid
                    INNER JOIN
                {user} u ON ra.userid = u.id
                    LEFT OUTER JOIN
                (SELECT 
                    log.courseid,
                        FROM_UNIXTIME(MAX(timecreated), ' %h:%i:%s %D %M %Y') AS nazadnje_urejanje
                FROM
                    {logstore_standard_log} log
                WHERE
                    userid = 4325
                        AND action IN ('updated' , 'created', 'uploaded', 'deleted', 'added', 'moved', 'removed', 'duplicated')
                GROUP BY courseid) AS urejanje ON c.id = urejanje.courseid",
                "ra.roleid IN (3)
                        AND cx.contextlevel = 50
                        AND u.id = :uid
                ORDER BY (SELECT 
                        MAX(la.timeaccess)
                    FROM
                        {user_lastaccess} la
                    WHERE
                        c.id = la.courseid AND la.userid = u.id) DESC",
                array('uid' => $this->teacherid));
        
        $this->table->define_baseurl($this->url);
        $this->table->is_downloadable(true);
        $this->table->show_download_buttons_at(array(TABLE_P_BOTTOM));
        $this->table->out(25, true);
    }
    
    // Aktivnosti po kategoriji
    public function show_table_list_activity_by_category() {
        global $DB;
        
        $ccid = $DB->get_field('course', 'category', array('id' => $this->courseid));
        
        $fields = "stevilo_nalog.id_nal,
                    stevilo_nalog.ime AS ime_ucilnice,
                    IF(stevilo_nalog.stevilo IS NULL,
                        0,
                        stevilo_nalog.stevilo) AS stevilo_nalog,
                    IF(stevilo_kvizov.stevilo IS NULL,
                        0,
                        stevilo_kvizov.stevilo) AS stevilo_kvizov,
                    IF(stevilo_priponk.stevilo IS NULL,
                        0,
                        stevilo_priponk.stevilo) AS stevilo_priponk,
                    IF(stevilo_www.stevilo IS NULL,
                        0,
                        stevilo_www.stevilo) AS stevilo_www_strani,
                    IF(stevilo_udel.stevilo IS NULL,
                        0,
                        stevilo_udel.stevilo) AS stevilo_udel";
        
        $this->table = new list_activity_by_category('report_log');
        $this->table->set_sql($fields,
                "{course} c
                    LEFT OUTER JOIN
                (SELECT 
                    cou.shortname AS ime,
                        cou.id AS id_nal,
                        IF(a.id IS NULL, 0, COUNT(*)) AS stevilo
                FROM
                    {course} cou
                LEFT OUTER JOIN {assign} a ON cou.id = a.course
                INNER JOIN {course_categories} cc ON cc.id = cou.category
                WHERE
                    cc.id = :ccid1 AND a.id IS NOT NULL
                GROUP BY cou.id) AS stevilo_nalog ON stevilo_nalog.id_nal = c.id
                    LEFT OUTER JOIN
                (SELECT 
                    c.id AS id_kviz, IF(q.id IS NULL, 0, COUNT(*)) AS stevilo
                FROM
                    {course} c
                LEFT OUTER JOIN {quiz} q ON c.id = q.course
                INNER JOIN {course_categories} cc ON cc.id = c.category
                WHERE
                    cc.id = :ccid2
                GROUP BY c.id) AS stevilo_kvizov ON stevilo_kvizov.id_kviz = c.id
                    LEFT OUTER JOIN
                (SELECT 
                    cou.id AS id_prip,
                        IF(cou.id IS NULL, 0, COUNT(*)) AS stevilo
                FROM
                    {course} cou
                LEFT OUTER JOIN {context} con ON cou.id = con.instanceid
                INNER JOIN {files} f ON f.contextid = con.id
                INNER JOIN {course_categories} cc ON cc.id = cou.category
                WHERE
                    cc.id = :ccid3
                GROUP BY cou.id) AS stevilo_priponk ON stevilo_priponk.id_prip = c.id
                    LEFT OUTER JOIN
                (SELECT 
                    c.id AS id_www, IF(p.id IS NULL, 0, COUNT(*)) AS stevilo
                FROM
                    {course} c
                LEFT OUTER JOIN {page} p ON c.id = p.course
                INNER JOIN {course_categories} cc ON cc.id = c.category
                WHERE
                    cc.id = :ccid4
                GROUP BY c.id) AS stevilo_www ON stevilo_www.id_www = c.id
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
                    r.shortname = 'student' AND cc.id = :ccid5
                GROUP BY cc.parent , c.shortname) AS stevilo_udel ON stevilo_udel.id_udel = c.id", 
                "stevilo_nalog.ime IS NOT NULL",
                array('ccid1' => $ccid, 'ccid2' => $ccid, 'ccid3' => $ccid, 'ccid4' => $ccid, 'ccid5' => $ccid));
        
        $this->table->define_baseurl($this->url);
        $this->table->is_downloadable(true);
        $this->table->show_download_buttons_at(array(TABLE_P_BOTTOM));
        $this->table->out(25, true);
    }
    
    // Aktivnosti udeležencev
    public function show_table_list_activities_of_participants() {        
        
        $fields = "c.id AS id_ucilnice,
                    c.shortname,
                    IF(forumi.stevilo_forumov IS NULL,
                        0,
                        forumi.stevilo_forumov) AS st_forumov,
                    IF(diskusije.stevilo_diskusij IS NULL,
                        0,
                        diskusije.stevilo_diskusij) AS st_diskusij,
                    IF(komentarji.stevilo_komentarjev IS NULL,
                        0,
                        komentarji.stevilo_komentarjev) AS st_komentarjev,
                    IF(naloge.stevilo_nalog IS NULL,
                        0,
                        naloge.stevilo_nalog) AS st_nalog,
                    IF(naloge_odd.stevilo IS NULL,
                        0,
                        naloge_odd.stevilo) AS st_oddanih_nal,
                    IF(ogledi.stevilo IS NULL,
                        0,
                        ogledi.stevilo) AS st_ogledov";
        
        $this->table = new list_activities_of_participants('report_log');
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
                    AND con.contextlevel = 50
                    LEFT OUTER JOIN
                (SELECT 
                    c.id AS id_ucilnice, COUNT(*) AS stevilo_forumov
                FROM
                    {course} c
                INNER JOIN {forum} f ON f.course = c.id
                WHERE
                    c.id IN (SELECT 
                            c.id AS id_ucilnice
                        FROM
                            {user} u
                        INNER JOIN {role_assignments} ra ON ra.userid = u.id
                        INNER JOIN {role} r ON ra.roleid = r.id
                        INNER JOIN {context} con ON ra.contextid = con.id
                        INNER JOIN {course} c ON c.id = con.instanceid
                            AND con.contextlevel = 50
                        WHERE
                            r.id = 3 AND u.id = :uid1)
                GROUP BY c.id) AS forumi ON forumi.id_ucilnice = c.id
                    LEFT OUTER JOIN
                (SELECT 
                    c.id AS id_ucilnice, COUNT(fd.id) AS stevilo_diskusij
                FROM
                    {course} c
                INNER JOIN {forum} f ON c.id = f.course
                INNER JOIN {forum_discussions} fd ON fd.forum = f.id
                WHERE
                    c.id IN (SELECT 
                            c.id AS id_ucilnice
                        FROM
                            {user} u
                        INNER JOIN {role_assignments} ra ON ra.userid = u.id
                        INNER JOIN {role} r ON ra.roleid = r.id
                        INNER JOIN {context} con ON ra.contextid = con.id
                        INNER JOIN {course} c ON c.id = con.instanceid
                            AND con.contextlevel = 50
                        WHERE
                            r.id = 3 AND u.id = :uid2)
                GROUP BY c.id) AS diskusije ON diskusije.id_ucilnice = c.id
                    LEFT OUTER JOIN
                (SELECT 
                    c.id AS id_ucilnice, COUNT(*) AS stevilo_komentarjev
                FROM
                    {course} c
                INNER JOIN {forum} f ON f.course = c.id
                INNER JOIN {forum_discussions} fd ON fd.forum = f.id
                INNER JOIN {forum_posts} fp ON fp.discussion = fd.id
                WHERE
                    c.id IN (SELECT 
                            c.id AS id_ucilnice
                        FROM
                            {user} u
                        INNER JOIN {role_assignments} ra ON ra.userid = u.id
                        INNER JOIN {role} r ON ra.roleid = r.id
                        INNER JOIN {context} con ON ra.contextid = con.id
                        INNER JOIN {course} c ON c.id = con.instanceid
                            AND con.contextlevel = 50
                        WHERE
                            r.id = 3 AND u.id = :uid3)
                GROUP BY c.id) AS komentarji ON komentarji.id_ucilnice = c.id
                    LEFT OUTER JOIN
                (SELECT 
                    c.id AS id_ucilnice,
                        IF(a.id IS NULL, 0, COUNT(*)) AS stevilo_nalog
                FROM
                    {course} c
                LEFT OUTER JOIN {assign} a ON c.id = a.course
                WHERE
                    c.id IN (SELECT 
                            c.id AS id_ucilnice
                        FROM
                            {user} u
                        INNER JOIN {role_assignments} ra ON ra.userid = u.id
                        INNER JOIN {role} r ON ra.roleid = r.id
                        INNER JOIN {context} con ON ra.contextid = con.id
                        INNER JOIN {course} c ON c.id = con.instanceid
                            AND con.contextlevel = 50
                        WHERE
                            r.id = 3 AND u.id = :uid4)
                GROUP BY c.id) AS naloge ON naloge.id_ucilnice = c.id
                    LEFT OUTER JOIN
                (SELECT 
                    c.id AS id_ucilnice,
                        IF(ass.id IS NULL, 0, COUNT(*)) AS stevilo
                FROM
                    {assign} a
                INNER JOIN {course} c ON c.id = a.course
                INNER JOIN {assign_submission} ass ON ass.assignment = a.id
                WHERE
                    c.id IN (SELECT 
                            c.id AS id_ucilnice
                        FROM
                            {user} u
                        INNER JOIN {role_assignments} ra ON ra.userid = u.id
                        INNER JOIN {role} r ON ra.roleid = r.id
                        INNER JOIN {context} con ON ra.contextid = con.id
                        INNER JOIN {course} c ON c.id = con.instanceid
                            AND con.contextlevel = 50
                        WHERE
                            r.id = 3 AND u.id = :uid5)
                        AND ass.status = 'submitted'
                GROUP BY c.id) AS naloge_odd ON naloge_odd.id_ucilnice = c.id
                    LEFT OUTER JOIN
                (SELECT 
                    log.courseid AS id_ucilnice, COUNT(*) AS stevilo
                FROM
                    {logstore_standard_log} log
                WHERE
                    log.courseid IN (SELECT 
                            c.id AS id_ucilnice
                        FROM
                            {user} u
                        INNER JOIN {role_assignments} ra ON ra.userid = u.id
                        INNER JOIN {role} r ON ra.roleid = r.id
                        INNER JOIN {context} con ON ra.contextid = con.id
                        INNER JOIN {course} c ON c.id = con.instanceid
                            AND con.contextlevel = 50
                        WHERE
                            r.id = 3 AND u.id = :uid6)
                        AND action = 'viewed'
                GROUP BY log.courseid) AS ogledi ON ogledi.id_ucilnice = c.id", 
                "r.id = 3 AND u.id = :uid7 ORDER BY c.id",
                array('uid1' => $this->teacherid, 'uid2' => $this->teacherid, 'uid3' => $this->teacherid, 'uid4' => $this->teacherid, 'uid5' => $this->teacherid, 'uid6' => $this->teacherid, 'uid7' => $this->teacherid));
        
        $this->table->define_baseurl($this->url);
        $this->table->is_downloadable(true);
        $this->table->show_download_buttons_at(array(TABLE_P_BOTTOM));
        $this->table->out(25, true);
    }
    
    // Aktivnosti izvajalcev
    public function show_table_list_performers_activity() {
        global $DB;
        
        $ccid = $DB->get_field('course', 'category', array('id' => $this->courseid));
        
        $fields = "ime_ucilnice.ime,
                    IF(urejanje.nazadnje_urejanje IS NULL,
                        'nikoli ni urejal/a',
                        urejanje.nazadnje_urejanje) AS zadnje_urejanje,
                    IF(dostop.zadnji_dostop IS NULL,
                        'nikoli ni dostopal/a',
                        dostop.zadnji_dostop) AS zadnji_dostop";
        
        $this->table = new list_performers_activity('report_log');
        $this->table->set_sql($fields,
                "{course} c
                    LEFT OUTER JOIN
                (SELECT 
                    c.shortname AS ime, c.id AS id_uc
                FROM
                    {course} c
                INNER JOIN {course_categories} cc ON cc.id = c.category AND cc.id = :ccid1
                GROUP BY c.id) AS ime_ucilnice ON c.id = ime_ucilnice.id_uc
                    LEFT OUTER JOIN
                (SELECT 
                    c.id AS id_uc,
                        FROM_UNIXTIME(MAX(la.timeaccess), ' %h:%i:%s %D %M %Y') AS zadnji_dostop
                FROM
                    {user_lastaccess} la
                INNER JOIN {course} c ON c.id = la.courseid
                INNER JOIN {course_categories} cc ON c.category = cc.id
                INNER JOIN {context} cx ON c.id = cx.instanceid
                INNER JOIN {role_assignments} ra ON cx.id = ra.contextid
                WHERE
                    ra.roleid IN (3)
                        AND cx.contextlevel = 50
                        AND cc.id = :ccid2
                GROUP BY c.id) AS dostop ON dostop.id_uc = c.id
                    LEFT OUTER JOIN
                (SELECT 
                    log.courseid,
                        FROM_UNIXTIME(MAX(log.timecreated), ' %h:%i:%s %D %M %Y') AS nazadnje_urejanje
                FROM
                    {logstore_standard_log} log
                INNER JOIN {course} c ON c.id = log.courseid
                INNER JOIN {course_categories} cc ON c.category = cc.id
                INNER JOIN {context} cx ON c.id = cx.instanceid
                INNER JOIN {role_assignments} ra ON cx.id = ra.contextid
                WHERE
                    ra.roleid IN (3)
                        AND cx.contextlevel = 50
                        AND cc.id = :ccid3
                        AND action IN ('updated' , 'created', 'uploaded', 'deleted', 'added', 'moved', 'removed', 'duplicated')
                GROUP BY log.courseid) AS urejanje ON c.id = urejanje.courseid", 
                "ime_ucilnice.ime IS NOT NULL",
                array('ccid1' => $ccid, 'ccid2' => $ccid, 'ccid3' => $ccid));
        
        $this->table->define_baseurl($this->url);
        $this->table->is_downloadable(true);
        $this->table->show_download_buttons_at(array(TABLE_P_BOTTOM));
        $this->table->out(25, true);
    }
    
    // Izvajalci po učilnicah
    public function show_table_list_performers_by_classrooms() {
        $fields = "u.id, c.id AS cid, c.shortname, u.firstname, u.lastname";
        
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
    
    // Aktivnosti učečih
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
    
    // Aktivnosti v učilnici
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

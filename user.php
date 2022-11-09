<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Completion overview report.
 *
 * @package    report_courselistoverview
 * @copyright  2022 Mihai Patrascanu <patrascanu.mihai@yahoo.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require('../../config.php');
require_once($CFG->libdir.'/adminlib.php');

admin_externalpage_setup('reportcourselistoverview', '', null, '', array('pagelayout' => 'report'));
require_login();

if (!is_siteadmin($USER)) {
    //here we can make a redirect with no access page
    die();
}

// Set contesxt and check capability.
$context = context_system::instance();
$PAGE->set_context($context);
$PAGE->set_title("View Courses");
require_capability('report/completationcourseoverview:view', $context);

// Read the user from DB;
$userid   = required_param('id', PARAM_INT);
$user = $DB->get_record('user', array('id'=>$userid, 'deleted'=>0), '*', MUST_EXIST);

$courses = enrol_get_users_courses($user->id, $onlyactive = false, $fields = null, $sort = null);

// Grab all courses the user is enrolled in and their completion status
$usercoursessql = "  
    SELECT  
        ue.enrolid,
        e.courseid,
        c.fullname AS coursename,
        cc.timecompleted,
        cc.timestarted
    FROM 
        {user_enrolments} ue
    JOIN 
        {enrol} e ON e.id = ue.enrolid
    JOIN 
        {course} c ON c.id = e.courseid
    LEFT JOIN 
        {course_completions} cc ON (cc.course = c.id) 
        AND cc.userid ={$user->id}
    WHERE 
        ue.userid = {$user->id}
    ";

$coursesfulldata = $DB->get_records_sql($usercoursessql);

//Starting bulding the output  
echo $OUTPUT->header();

$templatecontext = (object)[
    'name'=> $user->firstname.' '.$user->lastname,
    'count'=> count($courses)
  ];
    
echo $OUTPUT->render_from_template('report_courselistoverview/user',$templatecontext);

//This is the table which display all the courses that a someone is enrolled.
$table = new html_table();
$table->head = array ();
$table->colclasses = array();
$table->attributes['class'] = 'admintable generaltable table-sm';
$table->head[] = get_string( 'coursename', 'report_courselistoverview');
$table->head[] = get_string( 'completionstatus', 'report_courselistoverview');
$table->head[] = get_string( 'completiontime', 'report_courselistoverview');

$table->id = "courses";

foreach ($coursesfulldata as $course) {

    $row = array ();
    $row[] = '<a target="_new" href="'.$CFG->wwwroot.'/course/view.php?id='.$course->courseid.'">'.$course->coursename.'</a>';
    $row[] = ($course->timecompleted ? 'Completed' : 'Not completed');
    $row[] = ($course->timecompleted ? userdate($course['timecompleted'],                       get_string('strftimedatetime', 'langconfig')) : 'Not completed');
    $table->data[] = $row;
}


if (!empty($table)) {
    echo html_writer::start_tag('div', array('class'=>'no-overflow'));
    echo html_writer::table($table);
    echo html_writer::end_tag('div');
}

echo $OUTPUT->footer();

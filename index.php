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
require_once(dirname(__FILE__) . '/../../config.php');
require_once($CFG->libdir.'/adminlib.php');

$sort         = optional_param('sort', 'name', PARAM_ALPHANUMEXT);
$dir          = optional_param('dir', 'ASC', PARAM_ALPHA);
$page         = optional_param('page', 0, PARAM_INT);
$perpage      = optional_param('perpage', 5, PARAM_INT);        // how many per page

admin_externalpage_setup('reportcourselistoverview', '', null, '', array('pagelayout' => 'report'));
require_login();
if (!is_siteadmin($USER)) {
    //here we can make a redirect with no access page
    die();
}

// Set contesxt and check capability.
$context = context_system::instance();
$PAGE->set_context($context);
$PAGE->set_title("View Users");
require_capability('report/completationcourseoverview:view', $context);

// Read the users from DB;
$users = get_users_listing($sort, $dir, $page*$perpage, $perpage, '', '', '');
$usercount = get_users(false);

$baseurl = new moodle_url('../courselistoverview/index.php', array('sort' => $sort, 'dir' => $dir, 'perpage' => $perpage));

//Starting bulding the output  
echo $OUTPUT->header();
echo $OUTPUT->heading("$usercount ".get_string('users'));

//This is the table which display users.
$table = new html_table();
$table->head = array ();
$table->colclasses = array();
$table->attributes['class'] = 'admintable generaltable table-sm';
$table->head[] = get_string( 'name', 'report_courselistoverview');
$table->head[] = get_string( 'courses', 'report_courselistoverview');
$table->head[] = get_string( 'view', 'report_courselistoverview');
$table->id = "users";

foreach ($users as $user) {

    $lastcolumn = '';
    $fullname = fullname($user, true);
    $url = new moodle_url('../courselistoverview/user.php', array('id'=>$user->id));
    $lastcolumn = html_writer::link($url, $OUTPUT->pix_icon('t/user',get_string( 'view', 'report_courselistoverview')));

    $row = array ();
    $row[] = "<a href=\"../courselistoverview/user.php?id=$user->id\">$fullname</a>";
    $row[] = count(enrol_get_users_courses($user->id, $onlyactive = false, $fields = null, $sort = null) );
    $row[] = $lastcolumn;
    $table->data[] = $row;
}

if (!empty($table)) {
    echo html_writer::start_tag('div', array('class'=>'no-overflow'));
    echo html_writer::table($table);
    echo html_writer::end_tag('div');
    echo $OUTPUT->paging_bar($usercount, $page, $perpage, $baseurl);
}

echo $OUTPUT->footer();
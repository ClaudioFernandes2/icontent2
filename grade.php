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
 * List grades
 *
 * @package    mod_icontent
 * @copyright  2016-2015 Leo Santos {@link http://github.com/leorenis}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(dirname(__FILE__).'/../../config.php');
require_once(dirname(__FILE__).'/locallib.php');

$id			= required_param('id', PARAM_INT);      // Course Module ID
$pageid		= optional_param('pageid', 0, PARAM_INT); 	// page note ID
$action		= optional_param('action', 0, PARAM_ALPHA); // Action
$sort = optional_param('sort', '', PARAM_ALPHA);
$page = optional_param('page', 0, PARAM_INT);
$perpage = optional_param('perpage', ICONTENT_PER_PAGE, PARAM_INT);

$cm = get_coursemodule_from_id('icontent', $id, 0, false, MUST_EXIST);
$course = $DB->get_record('course', array('id'=>$cm->course), '*', MUST_EXIST);
$icontent = $DB->get_record('icontent', array('id'=>$cm->instance), '*', MUST_EXIST);

require_login($course, false, $cm);

$context = context_module::instance($cm->id);
require_capability('mod/icontent:grade', $context);
// Page setting
$PAGE->set_url('/mod/icontent/grade.php', array('id' => $cm->id, 'action'=>$action));
// Header and strings.
$PAGE->set_title($icontent->name);
$PAGE->set_heading($course->fullname);

echo $OUTPUT->header();
echo $OUTPUT->heading($icontent->name);
echo $OUTPUT->heading(get_string('summaryattempts', 'mod_icontent'), 3);
$url = new moodle_url('/mod/icontent/grade.php', array('id'=>$id, 'action'=> $action, 'page' => $page, 'perpage' => $perpage));
// Get sort value
$sort = icontent_check_value_sort($sort);
// Get users attempts
$attemptsusers = icontent_get_attempts_users($cm->id, $sort, $page, $perpage);
$tattemtpsusers = icontent_count_attempts_users($cm->id);
$tquestinstance = icontent_get_totalquestions_by_instance($cm->id);
// Make table questions
$table = new html_table();
$table->id = "idtableattemptsusers";
$table->colclasses = array('fullname', 'answers', 'result', 'grades');
$table->attributes = array('class'=>'table table-hover tableattemptsusers');
$table->head  = array(get_string('fullname'), get_string('answers', 'mod_icontent'), get_string('partialresult', 'mod_icontent'), get_string('gradingscale', 'mod_icontent', $icontent->grade));
if($attemptsusers) foreach ($attemptsusers as $attemptuser){
	// Get picture
	$picture = $OUTPUT->user_picture($attemptuser, array('size'=>35, 'class'=> 'img-thumbnail pull-left'));
	$linkfirstname = html_writer::link(new moodle_url('/user/view.php', array('id'=>$attemptuser->id, 'course'=>$course->id)), $attemptuser->firstname. ' '. $attemptuser->lastname, array('title'=>$attemptuser->firstname, 'class'=>'lkfullname'));
	// String open answers for user
	$stropenanswer = $attemptuser->totalopenanswers ? get_string('stropenanswer', 'mod_icontent', $attemptuser->totalopenanswers) : '';
	// String evaluate
	$evaluate = new stdClass();
	$evaluate->fraction = number_format($attemptuser->sumfraction, 2);
	$evaluate->maxfraction = number_format($attemptuser->totalanswers, 2);
	$evaluate->percentage = round(($attemptuser->sumfraction * 100) / $attemptuser->totalanswers);
	$evaluate->openanswer = $stropenanswer;
	$evaluate->finalgrade = ($attemptuser->sumfraction * $icontent->grade) / $tquestinstance;
	$strevaluate = get_string('strtoevaluate', 'mod_icontent', $evaluate);
	// Set data
	$table->data[] = array($picture. $linkfirstname, $attemptuser->totalanswers, $strevaluate, number_format($evaluate->finalgrade, 2));
}
else {
	echo html_writer::div(get_string('norecordsfound', 'mod_icontent'), 'alert alert-warning');
	echo $OUTPUT->footer();
	exit;
}
// Show table
echo html_writer::start_div('idtablegradeattemptsusers');
echo html_writer::table($table);
echo $OUTPUT->paging_bar($tattemtpsusers, $page, $perpage, $url);
echo html_writer::end_div();
echo $OUTPUT->footer();
<?php
/**
 * Defines the editing form for the order question type.
 *
 * @copyright &copy; 2007 Jamie Pratt
 * @author Jamie Pratt me@jamiep.org
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package questionbank
 * @subpackage questiontypes
 */

/**
 * match editing form definition.
 */
class qtype_order_edit_form extends question_edit_form {



    /**
     * Add question-type specific form fields.
     *
     * @param object $mform the form being built.
     */
    function definition_inner($mform) {
		global $CFG;

        //redirct to the new ordering question type
		$orderingurl = new moodle_url($CFG->wwwroot.'/question/question.php', $_GET);
		$orderingurl->param('qtype', 'ordering');
        redirect($orderingurl, 'This question type is deprecated. Redirection to new question ordering', 3);
    }

    function data_preprocessing($question) {
        return $question;
    }



    public function qtype() {
        return 'order';
    }
}
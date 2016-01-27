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
 * @package    moodlecore
 * @subpackage backup-moodle2
 * @copyright  2010 onwards Eloy Lafuente (stronk7) {@link http://stronk7.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * restore plugin class that provides the necessary information
 * needed to restore one order qtype plugin
 */
class restore_qtype_order_plugin extends restore_qtype_plugin {

    private $newquestionids = array();

    /**
     * Returns the paths to be handled by the plugin at question level
     */
    protected function define_question_plugin_structure() {

        $paths = array();

        // Add own qtype stuff
        $elename = 'orderoptions';
        $elepath = $this->get_pathfor('/orderoptions'); // we used get_recommended_name() so this works
        $paths[] = new restore_path_element($elename, $elepath);

        $elename = 'order';
        $elepath = $this->get_pathfor('/orders/order'); // we used get_recommended_name() so this works
        $paths[] = new restore_path_element($elename, $elepath);


        return $paths; // And we return the interesting paths
    }

    /**
     * Process the qtype/orderoptions element
     */
    public function process_orderoptions($data) {
        global $DB;

        $data = (object)$data;
        $newdata = new stdClass();
        $oldid = $data->id;

        // Detect if the question is created or mapped
        $oldquestionid   = $this->get_old_parentid('question');
        $newquestionid   = $this->get_new_parentid('question');
        $questioncreated = $this->get_mappingid('question_created', $oldquestionid) ? true : false;

        //convert to ordering
        $newdata->id =                              $data->id;
        $newdata->layouttype =                      $data->horizontal;
        $newdata->selecttype =                      '';
        $newdata->selectcount =                     0; //will be calculated later

        // If the question has been created by restore, we need to create its question_order too
        if ($questioncreated) {
            // Adjust some columns
            $newdata->questionid = $newquestionid;

            // Keep question_order->subquestions unmodified
            // after_execute_question() will perform the remapping once all subquestions
            // have been created
			
			//Added by justin hunt 20120131, previously errors occured here cos no default value for these fields in DB
			//yet since these members are new in 2.x, the 1.9 backups didn't contain them
            $newdata->correctfeedback =                 (isset($data->correctfeedback)) ? $data->correctfeedback : "";
            $newdata->correctfeedbackformat =           (isset($data->correctfeedbackformat)) ? $data->correctfeedbackformat : "";
            $newdata->incorrectfeedback =               (isset($data->incorrectfeedback)) ? $data->incorrectfeedback : "";
            $newdata->incorrectfeedbackformat =         (isset($data->incorrectfeedbackformat)) ? $data->incorrectfeedbackformat : "";
            $newdata->partiallycorrectfeedback =        (isset($data->partiallycorrectfeedback)) ? $data->partiallycorrectfeedback : "";
            $newdata->partiallycorrectfeedbackformat =  (isset($data->partiallycorrectfeedbackformat)) ? $data->partiallycorrectfeedbackformat : "";

			// Insert record
            $newitemid = $DB->insert_record('qtype_ordering_options', $newdata);

            $question = new stdClass();
            $question->id = $newquestionid;
            $question->qtype = 'ordering';
            $this->newquestionids[] = $newquestionid;

            // Create mapping
            $this->set_mapping('qtype_ordering', $oldid, $newitemid);
        } else {
            // Nothing to remap if the question already existed
        }
    }

    /**
     * Process the qtype/orders/order element
     */
    public function process_order($data) {
        global $DB;

        $data = (object)$data;
        $ordering = new stdClass();
        $oldid = $data->id;

        // Detect if the question is created or mapped
        $oldquestionid   = $this->get_old_parentid('question');
        $newquestionid   = $this->get_new_parentid('question');
        $questioncreated = $this->get_mappingid('question_created', $oldquestionid) ? true : false;


        //convert to ordering
        $ordering->id       = $data->id;
        $ordering->answer   = $data->questiontext;
        $ordering->fraction = $data->answertext;
        $ordering->feedback = "";
        $ordering->feedbackformat = 0;

        // If the question has been created by restore, we need to create its question_order_sub too
        if ($questioncreated) {
            // Adjust some columns
            $ordering->question = $newquestionid;
            // Insert record
            $newitemid = $DB->insert_record('question_answers', $ordering);
            // Create mapping (there are files and states based on this)
            $this->set_mapping('question_answers', $oldid, $newitemid);

        // order questions require mapping of question_order_sub, because
        // they are used by question_states->answer
        } else {
            // Look for ordering subquestion (by question, questiontext and answertext)
            $sub = $DB->get_record_select('question_answers', 'question = ? AND ' .
                    $DB->sql_compare_text('answer') . ' = ' .
                    $DB->sql_compare_text('?').
                    $DB->sql_compare_text('AND fraction') . ' = ' .
                    $DB->sql_compare_text('?'),
                    array($newquestionid, $ordering->answer, $ordering->fraction),
                    'id', IGNORE_MULTIPLE);
            // Found, let's create the mapping
            if ($sub) {
                $this->set_mapping('question_answers', $oldid, $sub->id);
            // Something went really wrong, cannot map subquestion for one order question
            } else {
              //  throw restore_step_exception('error_question_order_sub_missing_in_db', $data);
				print_r($ordering);
            }
        }
    }

    /**
     * This method is executed once the whole restore_structure_step,
     * more exactly ({@link restore_create_categories_and_questions})
     * has ended processing the whole xml structure. Its name is:
     * "after_execute_" + connectionpoint ("question")
     *
     * For order qtype we use it to restore the subquestions column,
     * containing one list of question_order_sub ids
     */
    public function after_execute_question() {
        global $DB;

        // Now that all the question_answers have been restored, let's process
        // the created question_ordering_options selectcount ()

        if(empty($this->newquestionids)){
            return;
        }

        $questionids = implode(",", $this->newquestionids);

        $sql = <<<SQL

        UPDATE
          {qtype_ordering_options} qo,
          (select qa.question, count(qa.id) `count` from {question_answers} qa where qa.question IN ({$questionids}) GROUP BY qa.question)   qc
        SET
          qo.selectcount = qc.count
        WHERE qc.question = qo.questionid

SQL;

        $DB->execute($sql);

        //set correct qtype for those new questions created.
        $sql = <<<SQL

        UPDATE
          {question}
        SET
          qtype = 'ordering'
        WHERE id IN({$questionids})

SQL;

        $DB->execute($sql);


    }

    /**
     * Do any re-coding necessary in the student response.
     * @param int $questionid the new id of the question
     * @param int $sequencenumber of the step within the question attempt.
     * @param array the response data from the backup.
     * @return array the recoded response.
     */
    public function recode_response($questionid, $sequencenumber, array $response) {

        $resultarr = array();

        foreach ($response as $key => $pair) {

            $pairarr = explode(',', $pair);
            $resultpair = [];

            foreach($pairarr as $questionid) {
                $newid       = $this->get_mappingid('question_answers', $questionid);
                $resultpair[] = $newid;
            }

            //override $keys to match ordering type for the choice and stem order
            if ($key == "_stemorder") {
                $key = "_correctresponse";
            }elseif ($key == "_choiceorder") {
                $key = "_currentresponse";
            }

            $resultarr[$key] = implode(',', $resultpair);

        }

        return $resultarr;

    }

}

<?php

// This file keeps track of upgrades to
// the match qtype plugin
//
// Sometimes, changes between versions involve
// alterations to database structures and other
// major things that may break installations.
//
// The upgrade function in this file will attempt
// to perform all the necessary actions to upgrade
// your older installation to the current version.
//
// If there's something it cannot do itself, it
// will tell you what you need to do.
//
// The commands in here will all be database-neutral,
// using the methods of database_manager class
//
// Please do not forget to use upgrade_set_timeout()
// before any action that may take longer time to finish.

function xmldb_qtype_order_upgrade($oldversion) {
    global $CFG, $DB, $QTYPES;

    $dbman = $DB->get_manager();

    if ($oldversion < 2011010400) {

        // Define field questiontextformat to be added to question_order_sub
        $table = new xmldb_table('question_order_sub');
        $field = new xmldb_field('questiontextformat', XMLDB_TYPE_INTEGER, '2', null, XMLDB_NOTNULL, null, '0', 'questiontext');

        // Conditionally launch add field questiontextformat
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // In the past, question_order_sub.questiontext assumed to contain
        // content of the same form as question.questiontextformat. If we are
        // using the HTML editor, then convert FORMAT_MOODLE content to FORMAT_HTML.

        // Because this question type was updated later than the core types,
        // the available/relevant version dates make it hard to differentiate
        // early 2.0 installs from 1.9 updates, hence the extra check for
        // the presence of oldquestiontextformat

        $table = new xmldb_table('question');
        $field = new xmldb_field('oldquestiontextformat');
        if ($dbman->field_exists($table, $field)) {
            $rs = $DB->get_recordset_sql('
                    SELECT qms.*, q.oldquestiontextformat
                    FROM {question_order_sub} qms
                    JOIN {question} q ON qms.question = q.id');
            foreach ($rs as $record) {
                if ($CFG->texteditors !== 'textarea' && $record->oldquestiontextformat == FORMAT_MOODLE) {
                    $record->questiontext = text_to_html($record->questiontext, false, false, true);
                    $record->questiontextformat = FORMAT_HTML;
                } else {
                    $record->questiontextformat = $record->oldquestiontextformat;
                }
                $DB->update_record('question_order_sub', $record);
            }
            $rs->close();
        }

        // match savepoint reached
        upgrade_plugin_savepoint(true, 2011010400, 'qtype', 'order');
    }

    if ($oldversion < 2011011300) {

        // Define field correctfeedback to be added to question_order
        $table = new xmldb_table('question_order');
        $field = new xmldb_field('correctfeedback', XMLDB_TYPE_TEXT, 'small', null,
                null, null, null, 'gradingmethod');

        // Conditionally launch add field correctfeedback
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);

            // Now fill it with '';
            $DB->set_field('question_order', 'correctfeedback', '');

            $field = new xmldb_field('correctfeedback', XMLDB_TYPE_TEXT, 'small', null,
                    XMLDB_NOTNULL, null, null, 'gradingmethod');
            $dbman->change_field_notnull($table, $field);
        }

        // Define field correctfeedbackformat to be added to question_order
        $field = new xmldb_field('correctfeedbackformat', XMLDB_TYPE_INTEGER, '2', null,
                XMLDB_NOTNULL, null, '0', 'correctfeedback');

        // Conditionally launch add field correctfeedbackformat
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $field = new xmldb_field('partiallycorrectfeedback', XMLDB_TYPE_TEXT, 'small', null,
                null, null, null, 'correctfeedbackformat');

        // Conditionally launch add field partiallycorrectfeedback
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);

            // Now fill it with '';
            $DB->set_field('question_order', 'partiallycorrectfeedback', '');

            // Now add the not null constraint.
            $field = new xmldb_field('partiallycorrectfeedback', XMLDB_TYPE_TEXT, 'small', null,
                    XMLDB_NOTNULL, null, null, 'correctfeedbackformat');
            $dbman->change_field_notnull($table, $field);
        }

        // Define field partiallycorrectfeedbackformat to be added to question_order
        $field = new xmldb_field('partiallycorrectfeedbackformat', XMLDB_TYPE_INTEGER, '2', null,
                XMLDB_NOTNULL, null, '0', 'partiallycorrectfeedback');

        // Conditionally launch add field partiallycorrectfeedbackformat
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Define field incorrectfeedback to be added to question_order
        $field = new xmldb_field('incorrectfeedback', XMLDB_TYPE_TEXT, 'small', null,
                null, null, null, 'partiallycorrectfeedbackformat');

        // Conditionally launch add field incorrectfeedback
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);

            // Now fill it with '';
            $DB->set_field('question_order', 'incorrectfeedback', '');

            // Now add the not null constraint.
            $field = new xmldb_field('incorrectfeedback', XMLDB_TYPE_TEXT, 'small', null,
                    XMLDB_NOTNULL, null, null, 'partiallycorrectfeedbackformat');
            $dbman->change_field_notnull($table, $field);
        }

        // Define field incorrectfeedbackformat to be added to question_order
        $field = new xmldb_field('incorrectfeedbackformat', XMLDB_TYPE_INTEGER, '2', null,
                XMLDB_NOTNULL, null, '0', 'incorrectfeedback');

        // Conditionally launch add field incorrectfeedbackformat
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Define field shownumcorrect to be added to question_order
        $field = new xmldb_field('shownumcorrect', XMLDB_TYPE_INTEGER, '2', null,
                XMLDB_NOTNULL, null, '0', 'incorrectfeedbackformat');

        // Conditionally launch add field shownumcorrect
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // match savepoint reached
        upgrade_plugin_savepoint(true, 2011080900, 'qtype', 'order');
    }

    if($oldversion < 2016012520){

        //this version converts to the qtype_ordering

        //start a transaction so we could roll back if any of those sql fails
        $trx = $DB->start_delegated_transaction();

        $table_question_answers = new xmldb_table('question_answers');
        $field_question_sub_id = new xmldb_field('order_sub_id', XMLDB_TYPE_INTEGER, 10, false);

        //Adding a field to question_answers for mapping sub order to question_answer
        //This could take a long time it the question_answer table is big
        $dbman->add_field($table_question_answers, $field_question_sub_id);

        //Convert order question setting to ordering settings.
        $sql = <<<SQL
INSERT INTO {qtype_ordering_options}
SELECT
  id id,
  question questionid,
  horizontal layouttype,
  0 selecttype,
  LENGTH(subquestions) - LENGTH(REPLACE(subquestions, ",", "")) + 1 `selectcount`,
  gradingmethod gradingtype,
  correctfeedback correctfeedback,
  correctfeedbackformat correctfeedbackformat,
  incorrectfeedback incorrectfeedback,
  incorrectfeedbackformat incorrectfeedbackformat,
  partiallycorrectfeedback partiallycorrectfeedback,
  partiallycorrectfeedbackformat partiallycorrectfeedbackformat
FROM
  {question_order}
SQL;

        $DB->execute($sql);

        //Convert the order_sub_answers to standard question answers.
        $sql = <<<SQL
INSERT INTO {question_answers} (question, answer, fraction, feedback, feedbackformat, order_sub_id)
	SELECT
		question,
	    questiontext,
        answertext,
        '',
        0,
        id
    FROM
		{question_order_sub}
SQL;

        $DB->execute($sql);


        //Get all question attempt step data for conversion.
        $sql = <<<SQL
SELECT
  qasd.id,
  qasd.`value`,
  qasd.`attemptstepid`,
  qasd.`name`,
  qas.`questionattemptid`,
  q.id questionid
FROM
  {question_attempt_step_data} qasd,
  {question_attempt_steps} qas,
  {question_attempts} qa,
  {question} q
WHERE qasd.`attemptstepid` = qas.`id`
  AND qas.`questionattemptid` = qa.`id`
  AND qa.`questionid` = q.`id`
  AND q.qtype = 'order'
SQL;

        $updating_question_attempt_data = $DB->get_records_sql($sql);


        //Get all question_answer we create from order_sub for mapping purposes.
        $sql = <<<SQL
SELECT
	qaq.order_sub_id order_sub_id,
	qaq.id  question_id,
	qaq.answer answer
FROM
	{question_answers} qaq
WHERE
	qaq.order_sub_id IS NOT NULL
SQL;

        $mapped_question_answers =  $DB->get_records_sql($sql);

        $questionattemptid = 0;
        $sub_data = [];
        $question_stem_order = [];
        $deletestempdata = [];
        $salt = (isset($CFG->passwordsaltmain)) ? $CFG->passwordsaltmain : "";
        $questionid = 0;
        foreach($updating_question_attempt_data as $question_attempt_step_data_id => $question_step_data){

            //every attemptid has multiple stepsid we need to preserve the choice and order for the other
            //sub events
            if ($questionattemptid == 0){
                $questionattemptid = $question_step_data->questionattemptid;
                $questionid = $question_step_data->questionid;
            }

            //We need to make sure we keep the individual steps together
            //If this is a new step. Save the previous on and start the new one. //do we need to save this attempt
            if( $questionattemptid !== $question_step_data->questionattemptid) {

                //Working on the next step data. Save the previous one.
                create_response_records($mapped_question_answers, $sub_data, $question_stem_order, $questionid, $salt);

                //Reset for next conversion of step_data.
                $sub_data = [];
                $question_stem_order = [];

                $questionattemptid = $question_step_data->questionattemptid;
                $questionid = $question_step_data->questionid;

            }

            //store stem and choice order for reference
            if($question_step_data->name == '_stemorder' || $question_step_data->name == '_choiceorder') {

                if ($question_step_data->name == '_stemorder') {

                    //store mapping
                    $question_stem_order = explode(',', $question_step_data->value);

                } // if

                //Convert to the ordering currentresponse and correctrespone format
                convert_stem_choiceorder($mapped_question_answers, $question_step_data->value, $question_attempt_step_data_id);

                continue;

            } // if

            //do we have a sub step_data
            if (preg_match('/(sub)(\d+)/', $question_step_data->name, $match) > 0) {

                //Add this step data to the deletelist
                $deletestempdata[] = $question_step_data->id;
                $sub_data[$question_step_data->attemptstepid][$match[2]] = $question_step_data->value;

            } // if

        } // foreach

        //Make sure we create the last stemp data record.
        if(!empty($sub_data)){
            create_response_records($mapped_question_answers, $sub_data, $question_stem_order, $questionid, $salt);
        }

        //Delete all sub stemp data
        delete_stem_data_records($deletestempdata);


        //Update nameing convention to match ordering
        $sql = <<<SQL
UPDATE
  {question_attempt_step_data} qasd,
  {question_attempt_steps} qas,
  {question_attempts} qa,
  {question}  q
SET
  qasd.name = IF (qasd.name = '_choiceorder', '_correctresponse', IF (qasd.name = '_stemorder', '_currentresponse', qasd.name))
WHERE
      qasd.`attemptstepid` = qas.`id`
  AND qas.`questionattemptid` = qa.`id`
  AND qa.`questionid` = q.`id`
  AND qtype = 'order'
SQL;

        $DB->execute($sql);

        //Change the question_type to ordering for the converted question
        $sql = <<<SQL
UPDATE
  {question}
SET
  qtype = 'ordering'
WHERE
  qtype='order'
SQL;

        $DB->execute($sql);

        //delete tables for the old question_order
        $qo_table = new xmldb_table('question_order');
        $qos_table = new xmldb_table('question_order_sub');
        $dbman->drop_table($qo_table);
        $dbman->drop_table($qos_table);

        //delete the added field for mapping
        $dbman->drop_field($table_question_answers, $field_question_sub_id);

        $returncode = $DB->commit_delegated_transaction($trx);

        if ($returncode === false) {
            return false;
        }

        upgrade_plugin_savepoint(true, 2016012520, 'qtype', 'order');

    }

    return true;

} // function xmldb_qtype_order_upgrade



/**
 * @param $mapped_question_answers
 * @param $question_step_data
 * @param $question_attempt_step_data_id
 */
function convert_stem_choiceorder($mapped_question_answers, $question_step_data, $question_attempt_step_data_id) {
    global $DB;

    $question_answers = explode(',', $question_step_data);
    $cross_question = array();
    foreach($question_answers as $order_sub_id){
        $cross_question[] =  $mapped_question_answers[$order_sub_id]->question_id;
    }

    $new_value = implode(',', $cross_question);
    $DB->set_field('question_attempt_step_data', 'value', $new_value, ['id' => $question_attempt_step_data_id ]);

} // function convert_stem_choiceorder



/**
 * @param $mapped_question_answers
 * @param $question_step_data
 * @param $question_attempt_step_data_id
 */
function convert_response_step($mapped_question_answers, $question_step_data, $question_attempt_step_data_id) {
    global $DB;

    //Convert sub* record to the response_{question_id} record

    //Delete the sub* records

    $question_answers = explode(',', $question_step_data->value);
    $cross_question = array();
    foreach($question_answers as $order_sub_id){
        $cross_question[] =  $mapped_question_answers[$order_sub_id]->question_id;
    }

    $new_value = implode(',', $cross_question);
    $DB->set_field('question_attempt_step_data', 'value', $new_value, ['id' => $question_attempt_step_data_id ]);

} // function convert_response_step



/**
 * @param $deletestempdata
 */
function delete_stem_data_records ($deletestempdata) {
    global $DB;
    $DB->delete_records_list('question_attempt_step_data','id', $deletestempdata);
} // function delete_stem_data_records



/**
 * @param $mapped_question_answers
 * @param $sub_data
 * @param $question_stem_order
 * @param $questionid
 * @param $salt
 */
function create_response_records($mapped_question_answers, $sub_data, $question_stem_order, $questionid, $salt) {
    global $DB;

    foreach($sub_data as $attemptstepid => $substempdata){

        foreach($substempdata as $stemposition => $responseposition){
            $questionanswer = $mapped_question_answers[$question_stem_order[$stemposition]]->answer;
            $response[$responseposition] = 'ordering_item_'. md5($salt.$questionanswer);

        }

        ksort($response, SORT_NUMERIC);
        $stempdata = new stdClass();
        $stempdata->attemptstepid = $attemptstepid;
        $stempdata->name = 'response_' . $questionid;
        $stempdata->value = implode(',', $response);

        $DB->insert_record('question_attempt_step_data', $stempdata);

    } // foreach

} //function create_response_records
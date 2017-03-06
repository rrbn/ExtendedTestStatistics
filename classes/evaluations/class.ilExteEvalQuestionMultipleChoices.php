<?php

/**
 * Choice Evaluation
 */
class ilExteEvalQuestionMultipleChoices extends ilExteEvalQuestion
{
	/**
	 * @var bool	evaluation provides a single value for the overview level
	 */
	protected static $provides_value = false;

	/**
	 * @var bool	evaluation provides data for a details screen
	 */
	protected static $provides_details = true;

	/**
	 * @var array   list of allowed test types, e.g. array(self::TEST_TYPE_FIXED)
	 */
	protected static $allowed_test_types = array();

	/**
	 * @var array	list of question types, e.g. array('assSingleChoice', 'assMultipleChoice', ...)
	 */
	protected static $allowed_question_types = array('assSingleChoice', 'assMultipleChoice');

	/**
	 * @var string	specific prefix of language variables (lowercase classname is default)
	 */
	protected static $lang_prefix = 'qst_choices';


    /**
     * Calculate the single value for a question (to be overwritten)
     *
     * Note:
     * This function will be called for many questions in sequence
     * - Please avoid instanciation of question objects
     * - Please try to cache question independent intermediate results
     *
     * @param integer $a_question_id
     * @return ilExteStatValue
     */
	public function calculateValue($a_question_id)
	{
        return new ilExteStatValue;
	}


    /**
     * Calculate the details question (to be overwritten)
     *
     * @param integer $a_question_id
     * @return ilExteStatDetails
     */
	public function calculateDetails($a_question_id)
	{
        global $ilDB;

        require_once('Modules/TestQuestionPool/classes/class.assQuestion.php');
        /** @var assMultipleChoice $question */
        $question = assQuestion::_instantiateQuestion($a_question_id);

        /** @var ASS_AnswerMultipleResponse[] $options */
        $options = $question->getAvailableAnswerOptions();

        // answer details
        $details = new ilExteStatDetails();
        $details->columns = array (
            ilExteStatColumn::_create('index',$this->txt('index'), ilExteStatColumn::SORT_NUMBER),
            ilExteStatColumn::_create('choice', $this->txt('choice'), ilExteStatColumn::SORT_TEXT),
            ilExteStatColumn::_create('points',$this->txt('points'),ilExteStatColumn::SORT_NUMBER),
            ilExteStatColumn::_create('count',$this->txt('count'),ilExteStatColumn::SORT_NUMBER),
        );

        $option_count = array();
        foreach ($options as $key => $option)
        {
            $option_count[$key] = 0;
        }

        /** @var ilExteStatSourceAnswer $answer */
        foreach ($this->data->getAnswersForQuestion($a_question_id, true) as $answer)
        {
            $result = $ilDB->queryF(
                "SELECT * FROM tst_solutions WHERE active_fi = %s AND pass = %s AND question_fi = %s",
                array("integer", "integer", "integer"),
                array($answer->active_id, $answer->pass, $a_question_id)
            );

            while ($data = $ilDB->fetchAssoc($result))
            {
                if (!empty($data["value1"]) && isset($options[$data["value1"]]))
                {
                    $option_count[$data["value1"]]++;
                }
            }
        }

        foreach ($options as $key => $option)
        {
           $details->rows[] = array(
                'index' => ilExteStatValue::_create($option->getOrder(), ilExteStatValue::TYPE_NUMBER, 0),
                'choice' => ilExteStatValue::_create($option->getAnswertext(), ilExteStatValue::TYPE_TEXT, 0),
                'points' => ilExteStatValue::_create($option->getPoints(), ilExteStatValue::TYPE_NUMBER, 2),
                'count' => ilExteStatValue::_create($option_count[$key], ilExteStatValue::TYPE_NUMBER, 0)
            );
        }

        return $details;
    }
}
<?php

/**
 * Example evaluation for a whole test
 */
class ilExteEvalQuestionExample extends ilExteEvalQuestion
{
	/**
	 * @var bool	evaluation provides a single value for the overview level
	 */
	protected static $provides_value = true;

	/**
	 * @var bool	evaluation provides data for a details screen
	 */
	protected static $provides_details = true;

	/**
	 * @var array   list of allowed test types, e.g. array(self::TEST_TYPE_FIXED)
	 */
	protected static $allowed_test_types = array(self::TEST_TYPE_FIXED);

	/**
	 * @var array	list of question types, e.g. array('assSingleChoice', 'assMultipleChoice', ...)
	 */
	protected static $allowed_question_types = array();


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
        $value = new ilExteStatValue;

        $count = 0;
        foreach ($this->data->getAnswersForQuestion($a_question_id) as $answerObj)
        {
            if ($answerObj->answered)
            {
                $count++;
            }
        }

        $value->type = ilExteStatValue::TYPE_NUMBER;
        $value->value = $count;
        $value->precision = 0;
        if ($count == 0)
        {
            $value->alert = ilExteStatValue::ALERT_MEDIUM;
            $value->comment = $this->txt('no_answer_available');
        }

        return $value;
	}


    /**
     * Calculate the details question (to be overwritten)
     *
     * @param integer $a_question_id
     * @return ilExteStatDetails[]
     */
	public function calculateDetails($a_question_id)
	{
        $return = array();

        // answer details
        $details = new ilExteStatDetails();
        $details->id = 'answers';
        $details->title = $this->txt('answers_title');
        $details->description = $this->txt('answers_description');
        $details->columns = array (
            ilExteStatColumn::_create('question_id','',ilExteStatColumn::SORT_NUMBER),
            ilExteStatColumn::_create('active_id','',ilExteStatColumn::SORT_NUMBER),
            ilExteStatColumn::_create('pass','',ilExteStatColumn::SORT_NUMBER),
            ilExteStatColumn::_create('sequence','',ilExteStatColumn::SORT_NUMBER),
            ilExteStatColumn::_create('answered','',ilExteStatColumn::SORT_NUMBER),
            ilExteStatColumn::_create('reached_points','',ilExteStatColumn::SORT_NUMBER),
            ilExteStatColumn::_create('manual_scored','',ilExteStatColumn::SORT_NUMBER),
        );
        foreach ($this->data->getAnswersForQuestion($a_question_id) as $answer)
        {
            $details->rows[] = array(
                'question_id' => ilExteStatValue::_create($answer->question_id, ilExteStatValue::TYPE_NUMBER, 0),
                'active_id' => ilExteStatValue::_create($answer->active_id, ilExteStatValue::TYPE_NUMBER, 0),
                'pass' => ilExteStatValue::_create($answer->pass, ilExteStatValue::TYPE_NUMBER, 0),
                'sequence' => ilExteStatValue::_create($answer->sequence, ilExteStatValue::TYPE_NUMBER, 0),
                'answered' => ilExteStatValue::_create($answer->answered, ilExteStatValue::TYPE_BOOLEAN),
                'reached_points' => ilExteStatValue::_create($answer->reached_points, ilExteStatValue::TYPE_NUMBER, 2),
                'manual_scored' => ilExteStatValue::_create($answer->manual_scored, ilExteStatValue::TYPE_BOOLEAN),
            );
        }
        $return[] = $details;

        return $return;
    }

}
<?php

/**
 * Example evaluation for a whole test
 */
class ilExteEvalQuestionFacilityIndex extends ilExteEvalQuestion
{
	/**
	 * @var bool    evaluation provides a single value for the overview level
	 */
	protected static $provides_value = true;

	/**
	 * @var bool    evaluation provides data for a details screen
	 */
	protected static $provides_details = false;

	/**
	 * @var array   list of allowed test types, e.g. array(self::TEST_TYPE_FIXED)
	 */
	protected static $allowed_test_types = array(self::TEST_TYPE_FIXED);

	/**
	 * @var array    list of question types, e.g. array('assSingleChoice', 'assMultipleChoice', ...)
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
        //Get Data
		$question_data = $this->data->getQuestion($a_question_id);
		$average_points = $question_data->average_points;

        //Get Lowest and highest score for this question
		$lowest_score = $question_data->maximum_points;
		$highest_score = 0.0;
		$count = 0;
		foreach ($this->data->getAnswersForQuestion($a_question_id) as $answerObj)
        {
			if ($answerObj->answered) {
				if ((float)$answerObj->reached_points < (float)$lowest_score)
                {
					$lowest_score = (float)$answerObj->reached_points;
				}
				if ((float)$answerObj->reached_points > (float)$highest_score)
                {
					$highest_score = (float)$answerObj->reached_points;
				}
			}
			$count++;
		}

        //Calculate facility index, if possible
        $value = new ilExteStatValue;
        $value->type = ilExteStatValue::TYPE_PERCENTAGE;
        $value->precision = 4;

        if ($count == 0)
        {
            $value->alert = ilExteStatValue::ALERT_UNKNOWN;
            $value->comment = $this->plugin->txt('not_enough_answers');
            $value->value = null;
        }
        elseif ($highest_score == $lowest_score)
        {
            $value->alert = ilExteStatValue::ALERT_UNKNOWN;
            $value->comment = $this->txt('all_scores_identical');
            $value->value = null;
        }
        else
        {
            $facility_index = (($average_points - $lowest_score) / ($highest_score - $lowest_score));
            $value->value = $facility_index;
        }

		return $value;
	}

}
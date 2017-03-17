<?php

/**
 * Example evaluation for a whole test
 */
class ilExteEvalQuestionFacilityIndex extends ilExteEvalQuestion
{
	/**
	 * @var bool    evaluation provides a single value for the overview level
	 */
	protected $provides_value = true;

	/**
	 * @var bool    evaluation provides data for a details screen
	 */
	protected $provides_details = false;

	/**
	 * @var array   list of allowed test types, e.g. array(self::TEST_TYPE_FIXED)
	 */
	protected $allowed_test_types = array();

	/**
	 * @var array    list of question types, e.g. array('assSingleChoice', 'assMultipleChoice', ...)
	 */
	protected $allowed_question_types = array();

	/**
	 * @var string	specific prefix of language variables (lowercase classname is default)
	 */
	protected $lang_prefix = 'qst_facility';

	/**
	 * Get the available parameters for this evaluation
	 * @return ilExteStatParam
	 */
	public function getAvailableParams()
	{
		return array(
			ilExteStatParam::_create('min_ans', ilExteStatParam::TYPE_INT, 2),
			ilExteStatParam::_create('min_medium', ilExteStatParam::TYPE_FLOAT, 10),
			ilExteStatParam::_create('min_good', ilExteStatParam::TYPE_FLOAT, 20),
			ilExteStatParam::_create('max_good', ilExteStatParam::TYPE_FLOAT, 80),
			ilExteStatParam::_create('max_medium', ilExteStatParam::TYPE_FLOAT, 90),
		);
	}


	/**
	 * Calculate the single value for a question (to be overwritten)
	 *
	 * Note:
	 * This function will be called for many questions in sequence
	 * - Please avoid instantiation of question objects
	 * - Please try to cache question independent intermediate results
	 *
	 * @param integer $a_question_id
	 * @return ilExteStatValue
	 */
	public function calculateValue($a_question_id)
	{
        // Get Data
		$question_data = $this->data->getQuestion($a_question_id);
		$average_points = $question_data->average_points;

        // Get the actual lowest and highest score for this question
		$lowest_score = $question_data->maximum_points;
		$highest_score = 0.0;
		$count = 0;
		foreach ($this->data->getAnswersForQuestion($a_question_id) as $answerObj)
        {
			if ($answerObj->answered) {
				if ((float) $answerObj->reached_points < (float) $lowest_score)
                {
					$lowest_score = (float) $answerObj->reached_points;
				}
				if ((float) $answerObj->reached_points > (float) $highest_score)
                {
					$highest_score = (float) $answerObj->reached_points;
				}
			}
			$count++;
		}

        //Calculate facility index, if possible
        $value = new ilExteStatValue;
        $value->type = ilExteStatValue::TYPE_PERCENTAGE;
        $value->precision = 0;

		// check minimum number of answers
		if ($count < $this->getParam('min_ans'))
        {
            $value->alert = ilExteStatValue::ALERT_UNKNOWN;
			$value->comment = sprintf($this->txt('min_ans_alert'), $this->getParam('min_ans'));
            $value->value = null;
			return $value;
        }
        elseif ($highest_score == $lowest_score)
        {
            $value->alert = ilExteStatValue::ALERT_UNKNOWN;
            $value->comment = $this->txt('all_scores_identical');
            $value->value = null;
			return $value;
        }
        else
        {
            $facility_index = 100 * (($average_points - $lowest_score) / ($highest_score - $lowest_score));
            $value->value = $facility_index;
        }

		// Alert quality
		if ( $value->value < $this->getParam('min_medium') || ($value->value > $this->getParam('max_medium') && $this->getParam('max_medium') > 0))
		{
			$value->alert = ilExteStatValue::ALERT_BAD;
		}
		elseif ( $value->value < $this->getParam('min_good') || ($value->value > $this->getParam('max_good') && $this->getParam('max_good') > 0))
		{
			$value->alert = ilExteStatValue::ALERT_MEDIUM;
		}
		elseif ($this->getParam('min_good') > 0 || $this->getParam('max_good') > 0)
		{
			$value->alert = ilExteStatValue::ALERT_GOOD;
		}

		return $value;
	}

}
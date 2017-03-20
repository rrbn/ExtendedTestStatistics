<?php
// Copyright (c) 2017 Institut fuer Lern-Innovation, Friedrich-Alexander-Universitaet Erlangen-Nuernberg, GPLv3, see LICENSE

/**
 * Standard deviation of a question
 */
class ilExteEvalQuestionStandardDeviation extends ilExteEvalQuestion
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
	protected $lang_prefix = 'qst_standarddeviation';

	/**
	 * Calculate the Standard deviation for answers in this question
	 * @param integer $a_question_id
	 * @return ilExteStatValue
	 */
	public function calculateValue($a_question_id)
	{
		//Get Data
		$question_data = $this->data->getQuestion($a_question_id);
		$average_points = $question_data->average_points;

		//Prepare variables
		$value = new ilExteStatValue;
        $value->type = ilExteStatValue::TYPE_NUMBER;
        $value->precision = 2;
        $value->value = null;

		$lowest_score = $question_data->maximum_points;
		$highest_score = 0.0;
		$sum_power_diff = 0.0;
		$count = 0;

		//Go throw answers to this questions to take results needed for calculations
		foreach ($this->data->getAnswersForQuestion($a_question_id) as $answerObj)
        {
			if ($answerObj->answered)
            {
				//Get Lowest and highest score for this question
				if ((float)$answerObj->reached_points < (float)$lowest_score)
                {
					$lowest_score = (float)$answerObj->reached_points;
				}
				if ((float)$answerObj->reached_points > (float)$highest_score)
                {
					$highest_score = (float)$answerObj->reached_points;
				}

				//Fetch the sum of squared differences between total score and it mean
				$sum_power_diff += pow((float)$answerObj->reached_points - $average_points, 2);
				$count++;
			}
		}

        if ($count < 2)
        {
            $value->alert = ilExteStatValue::ALERT_UNKNOWN;
            $value->comment = $this->plugin->txt('not_enough_answers');
            return $value;
        }
        elseif ($highest_score == $lowest_score)
        {
            $standard_deviation = 0;
        }
		else
        {
            //Calculate Variance
            $variance = (1 / ($count - 1)) * $sum_power_diff;

            //Calculate Standard deviation
			$standard_deviation = (sqrt($variance) / ($highest_score - $lowest_score));
		}

		$value->value = $standard_deviation;
		return $value;
	}

}
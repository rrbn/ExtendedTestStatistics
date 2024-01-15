<?php
// Copyright (c) 2017 Institut fuer Lern-Innovation, Friedrich-Alexander-Universitaet Erlangen-Nuernberg, GPLv3, see LICENSE

/**
 * Example evaluation for a whole test
 */
class ilExteEvalQuestionDiscriminationIndex extends ilExteEvalQuestion
{
	/**
	 * @var bool    evaluation provides a single value for the overview level
	 */
	protected $provides_value = true;

    /**
     * @var bool    evaluation provides a chart of the values presented in the overview of questions
     */
    protected $provides_overview_chart = true;

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
	protected $lang_prefix = 'qst_discrimination';


	/**
	 * Get the available parameters for this evaluation
	 * @return ilExteStatParam[]
	 */
	public function getAvailableParams()
	{
		return array(
			ilExteStatParam::_create('min_qst', ilExteStatParam::TYPE_INT, 0),
			ilExteStatParam::_create('min_ans', ilExteStatParam::TYPE_INT, 2),
			ilExteStatParam::_create('min_good', ilExteStatParam::TYPE_FLOAT, 0.3),
			ilExteStatParam::_create('min_medium', ilExteStatParam::TYPE_FLOAT, 0.1)
		);
	}

	/**
	 * Calculate the discrimination index
	 * @param integer $a_question_id
	 * @return ilExteStatValue
	 */
	public function calculateValue($a_question_id)
	{
        // Prepare the return value
        $value = new ilExteStatValue;
        $value->type = ilExteStatValue::TYPE_NUMBER;
        $value->precision = 2;
        $value->value = null;

		// check minimum number of questions
		if (count($this->data->getAllQuestions()) < $this->getParam('min_qst'))
		{
			$value->alert = ilExteStatValue::ALERT_UNKNOWN;
			$value->comment = sprintf($this->txt('min_qst_alert'), $this->getParam('min_qst'));
			return $value;
		}

		// check minimum number of answers
		if (count($this->data->getAnswersForQuestion($a_question_id)) < $this->getParam('min_ans'))
		{
			$value->alert = ilExteStatValue::ALERT_UNKNOWN;
			$value->comment = sprintf($this->txt('min_ans_alert'), $this->getParam('min_ans'));
			return $value;
		}

        //Get needed data
		$current_question_data = $this->data->getQuestion($a_question_id);
		$current_question_average_points = $current_question_data->average_points;

		//Current question Variance calculation
		$current_lowest_score = $current_question_data->maximum_points;
		$current_highest_score = 0.0;
		$current_sum_power_diff = 0.0;
		$number_of_participants_of_current_question = 0;

		//Go throw answers to this questions to take results needed for calculations
		foreach ($this->data->getAnswersForQuestion($a_question_id) as $answerObj)
        {
			if ($answerObj->answered)
            {
				//Get Lowest and highest score for this question
				if ((float)$answerObj->reached_points < (float)$current_lowest_score)
                {
					$current_lowest_score = (float)$answerObj->reached_points;
				}
				if ((float)$answerObj->reached_points > (float)$current_highest_score)
                {
					$current_highest_score = (float)$answerObj->reached_points;
				}

				//Fetch the sum of squared differences between total score and it mean
				$current_sum_power_diff += pow((float)$answerObj->reached_points - $current_question_average_points, 2);
				$number_of_participants_of_current_question++;
			}
		}

        if ($number_of_participants_of_current_question < 2)
        {
            $value->alert = ilExteStatValue::ALERT_UNKNOWN;
            $value->comment = $this->plugin->txt('not_enough_answers');
            return $value;
        }

		//Calculate current variance
		$current_question_variance_final = (1 / ($number_of_participants_of_current_question - 1)) * $current_sum_power_diff;
        if ($current_question_variance_final == 0)
        {
            $value->alert = ilExteStatValue::ALERT_UNKNOWN;
            $value->comment = $this->txt('zero_variance_of_question');
            return $value;
        }


		//Other
		$all_questions_array = $this->data->getAllQuestions();
		$other_questions_array = $all_questions_array;

		//Other question Variance calculation
		$other_lowest_score_array = array(); //array of questions
		$other_highest_score_array = array(); //array of questions
		$points_of_participants_in_test = array();

		//Calculate points of participants in test
		foreach ($this->data->getAllParticipants() as $participant)
        {
			$points_of_participants_in_test[$participant->active_id] = $participant->current_reached_points;
		}


		//Calaculate Other average
		$other_questions_reached_points_sum = array();
		$other_questions_reached_points_average = array();
		foreach ($other_questions_array as $other_question_id => $question_object)
        {
			foreach ($other_questions_array as $other_question_id_2 => $question_object_2)
            {
				if ($other_question_id != $other_question_id_2)
                {
					foreach ($this->data->getAnswersForQuestion($other_question_id_2) as $other_answer_obj)
                    {
						$other_questions_reached_points_sum[$other_question_id] += $other_answer_obj->reached_points;
					}
				}
			}
			$other_questions_reached_points_average[$other_question_id] = $other_questions_reached_points_sum[$other_question_id] / $number_of_participants_of_current_question;
		}

		//Go throw answers to this questions to take results needed for calculations
		foreach ($other_questions_array as $other_question_id => $question_object)
        {
			$other_lowest_score_array[$other_question_id] = $question_object->maximum_points;
			$other_highest_score_array[$other_question_id] = 0.0;

			foreach ($this->data->getAnswersForQuestion($other_question_id) as $answer_object)
            {
				if ($answer_object->answered)
                {
					//Get Lowest and highest score for this question
					if ((float)$answer_object->reached_points < (float)$other_lowest_score_array[$other_question_id])
                    {
						$other_lowest_score_array[$other_question_id] = (float)$answer_object->reached_points;
					}
					if ((float)$answer_object->reached_points > (float)$other_highest_score_array[$other_question_id])
                    {
						$other_highest_score_array[$other_question_id] = (float)$answer_object->reached_points;
					}
				}
			}
		}

		$other_sum_power_diff = array();
		$current_sum_power_diff = array();
		$covariance_sum = array();


		foreach ($this->data->getAllParticipants() as $other_participant)
        {
			foreach ($this->data->getAnswersForParticipant($other_participant->active_id) as $other_answer)
            {
				$other_points_difference = (float)$points_of_participants_in_test[$other_answer->active_id] - $other_answer->reached_points - $other_questions_reached_points_average[$other_answer->question_id];
				$current_sum_power_diff[$other_answer->question_id] += pow((float)$other_answer->reached_points - $current_question_average_points, 2);
				$other_sum_power_diff[$other_answer->question_id] += pow($other_points_difference, 2);
				$covariance_sum[$other_answer->question_id] += ((float)$other_answer->reached_points - $current_question_average_points) * $other_points_difference;
			}
		}

		//Calculate other variance
		$other_questions_variance_final = (1 / ($number_of_participants_of_current_question - 1)) * $other_sum_power_diff[$a_question_id];
        if ($other_questions_variance_final == 0)
        {
            $value->alert = ilExteStatValue::ALERT_UNKNOWN;
            $value->comment = $this->txt('zero_variance_of_other_questions');
            return $value;
        }

		//Final calculations
		$covariance_final = (1 / ($number_of_participants_of_current_question - 1)) * $covariance_sum[$a_question_id];
		$discrimination_index = $covariance_final / sqrt($current_question_variance_final * $other_questions_variance_final);

        $value->value = $discrimination_index;

		// Note on random values
		if ($this->data->getTestType() !== ilExteEvalBase::TEST_TYPE_FIXED)
		{
			$value->uncertain = true;
			$value->comment = $this->txt('random_test');
		}


		// Alert good quality
		if ( $this->getParam('min_good') > 0)
		{
			if ($value->value >= $this->getParam('min_good'))
			{
				$value->alert = ilExteStatValue::ALERT_GOOD;
				return $value;
			}
			else
			{
				$value->alert = ilExteStatValue::ALERT_BAD;
			}
		}

		// Alert medium quality
		if ( $this->getParam('min_medium') > 0)
		{
			if ($value->value >= $this->getParam('min_medium'))
			{
				$value->alert = ilExteStatValue::ALERT_MEDIUM;
				return $value;
			}
			else
			{
				$value->alert = ilExteStatValue::ALERT_BAD;
			}
		}

		// return value with 'bad' or no alert
		return $value;
	}
}
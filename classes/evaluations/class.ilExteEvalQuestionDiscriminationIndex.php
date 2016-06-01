<?php

/**
 * Example evaluation for a whole test
 */
class ilExteEvalQuestionDiscriminationIndex extends ilExteEvalQuestion
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
	 * Calculate the Standard deviation for answers in this question
	 * @param integer $a_question_id
	 * @return ilExteStatValue
	 */
	public function calculateValue($a_question_id)
	{
		//Get needed data
		$current_question_data = $this->data->getQuestion($a_question_id);
		$current_question_average_points = $current_question_data->average_points;

		//Global
		$array_of_sum_of_points_by_questions = array();
		$array_of_sum_of_points_participant = array();

		//Current question Variance calculation
		$current_lowest_score = $current_question_data->maximum_points;
		$current_highest_score = 0.0;
		$current_sum_power_diff = 0.0;
		$number_of_participants_of_current_question = 0;

		//Go throw answers to this questions to take results needed for calculations
		foreach ($this->data->getAnswersForQuestion($a_question_id) as $answerObj) {
			if ($answerObj->answered) {
				//Get Lowest and highest score for this question
				if ((float)$answerObj->reached_points < (float)$current_lowest_score) {
					$current_lowest_score = (float)$answerObj->reached_points;
				}
				if ((float)$answerObj->reached_points > (float)$current_highest_score) {
					$current_highest_score = (float)$answerObj->reached_points;
				}

				//Fetch the sum of squared differences between total score and it mean
				$current_sum_power_diff += pow((float)$answerObj->reached_points - $current_question_average_points, 2);
				$number_of_participants_of_current_question++;
			}
		}

		//Calculate current variance
		$current_question_variance_final = (1 / ($number_of_participants_of_current_question - 1)) * $current_sum_power_diff;

		//Other
		$all_questions_array = $this->data->getAllQuestions();
		//unset($all_questions_array[$a_question_id]);
		$other_questions_array = $all_questions_array;

		//Other question Variance calculation
		$other_lowest_score_array = array(); //array of questions
		$other_highest_score_array = array(); //array of questions
		$points_of_participants_in_test = array();

		//Calculate points of participants in test
		foreach ($this->data->getAllParticipants() as $participant) {
			$points_of_participants_in_test[$participant->active_id] = $participant->current_reached_points;
		}


		//Calaculate Other average
		$other_questions_reached_points_sum = array();
		$other_questions_reached_points_average = array();
		foreach ($other_questions_array as $other_question_id => $question_object) {
			foreach ($other_questions_array as $other_question_id_2 => $question_object_2) {
				if ($other_question_id != $other_question_id_2) {
					foreach ($this->data->getAnswersForQuestion($other_question_id_2) as $other_answer_obj) {
						$other_questions_reached_points_sum[$other_question_id] += $other_answer_obj->reached_points;
					}
				}
			}
			$other_questions_reached_points_average[$other_question_id] = $other_questions_reached_points_sum[$other_question_id] / $number_of_participants_of_current_question;
		}

		//Go throw answers to this questions to take results needed for calculations
		foreach ($other_questions_array as $other_question_id => $question_object) {
			$other_lowest_score_array[$other_question_id] = $question_object->maximum_points;
			$other_highest_score_array[$other_question_id] = 0.0;

			foreach ($this->data->getAnswersForQuestion($other_question_id) as $answer_object) {
				if ($answer_object->answered) {
					//Get Lowest and highest score for this question
					if ((float)$answer_object->reached_points < (float)$other_lowest_score_array[$other_question_id]) {
						$other_lowest_score_array[$other_question_id] = (float)$answer_object->reached_points;
					}
					if ((float)$answer_object->reached_points > (float)$other_highest_score_array[$other_question_id]) {
						$other_highest_score_array[$other_question_id] = (float)$answer_object->reached_points;
					}
				}
			}
		}

		$other_sum_power_diff = array();
		$current_sum_power_diff = array();
		$covariance_sum = array();


		foreach ($this->data->getAllParticipants() as $other_participant) {
			foreach ($this->data->getAnswersForParticipant($other_participant->active_id) as $other_answer) {
				$other_points_difference = (float)$points_of_participants_in_test[$other_answer->active_id] - $other_answer->reached_points - $other_questions_reached_points_average[$other_answer->question_id];
				$current_sum_power_diff[$other_answer->question_id] += pow((float)$other_answer->reached_points - $current_question_average_points, 2);
				$other_sum_power_diff[$other_answer->question_id] += pow($other_points_difference, 2);
				$covariance_sum[$other_answer->question_id] += ((float)$other_answer->reached_points - $current_question_average_points) * $other_points_difference;

			}
		}

		//Calculate other variance
		$other_questions_variance_final = (1 / ($number_of_participants_of_current_question - 1)) * $other_sum_power_diff[$a_question_id];

		//Final calculations
		$covariance_final = (1 / ($number_of_participants_of_current_question - 1)) * $covariance_sum[$a_question_id];



		if ($current_question_variance_final * $other_questions_variance_final) {
			$discrimination_index = 100 * $covariance_final / sqrt($current_question_variance_final * $other_questions_variance_final);
		} else {
			$discrimination_index = NULL;
		}

		//Return discrimination index
		$value = new ilExteStatValue;
		$value->type = ilExteStatValue::TYPE_PERCENTAGE;
		$value->value = $discrimination_index;
		$value->precision = 4;
		if ($number_of_participants_of_current_question == 0) {
			$value->alert = ilExteStatValue::ALERT_MEDIUM;
			$value->comment = $this->txt('no_answer_available');
		}

		return $value;

	}

}
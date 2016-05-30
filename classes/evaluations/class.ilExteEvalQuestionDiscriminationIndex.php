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
	protected static $allowed_question_types = array('assSingleChoice');


	/**
	 * Calculate the Standard deviation for answers in this question
	 * @param integer $a_question_id
	 * @return ilExteStatValue
	 */
	public function calculateValue($a_question_id)
	{
		$value = new ilExteStatValue;
		$value->type = ilExteStatValue::TYPE_TEXT;
		$value->value = "Under construction";
		$value->precision = 4;

		return $value;

		//Get Data
		$question_data = $this->data->getQuestion($a_question_id);
		$question_average_points = $question_data->average_points;
		$number_of_questions = count($this->data->getAllQuestions());
		$number_of_participants = count($this->data->getAllParticipants());

		//Prepare variables
		$value = new ilExteStatValue;
		$current_question_sum_of_points = 0.0;
		$current_question_sum_power_diff = 0.0;

		//First loop over participants
		$list_of_sum_of_points = array();
		$list_of_points = array();

		//Get list of sum of points
		foreach ($this->data->getAllParticipants() as $participant_id => $participant) {
			foreach ($this->data->getAnswersForParticipant($participant->active_id) as $participant_answer) {
				if (!isset($list_of_sum_of_points[$participant->active_id])) {
					$list_of_sum_of_points[$participant->active_id] = 0.0;
				}
				$list_of_sum_of_points[$participant->active_id] += $participant_answer->reached_points;
			}
		}

		var_dump($list_of_sum_of_points);Exit;

		$other_questions_sum_array = array();
		$other_questions_average_array = array();
		$answer_count = 0;
		//First loop over questions
		foreach ($this->data->getAllQuestions() as $question) {

			//Current question
			$answer_count_2 = 0;
			foreach ($this->data->getAnswersForQuestion($question->question_id) as $answerObj) {
				$answer_count_2++;
			}

			foreach ($this->data->getAllQuestions() as $other_question) {
				$answer_count = 0;
				foreach ($this->data->getAnswersForQuestion($other_question->question_id) as $other_question_answerObj) {
					if ($question->question_id != $other_question->question_id) {
						if (!isset($other_questions_sum_array[$question->question_id])) {
							$other_questions_sum_array[$question->question_id] = 0.0;
						}
						$other_questions_sum_array[$question->question_id] += $other_question_answerObj->reached_points;
						$answer_count++;
					}
				}
			}

			if ($answer_count > 0) {
				$other_questions_average_array[$question->question_id] = $other_questions_sum_array[$question->question_id] / $answer_count;
			}else{
				$other_questions_average_array[$question->question_id] = $list_of_sum_of_points[$question->question_id] / $answer_count_2;
			}
		}


		//Second loop over questions
		foreach ($this->data->getAllQuestions() as $question) {
			if ($question->question_id == $a_question_id) {
				//Current question
				foreach ($this->data->getAnswersForQuestion($question->question_id) as $answerObj) {
					$current_question_sum_power_diff += pow((float)$answerObj->reached_points - $current_question_average, 2);
				}
			}
		}

		//Second loop over participants
		$other_questions_sum_power_diff = 0.0;
		var_dump($other_questions_average_array);exit;
		foreach ($this->data->getAllParticipants() as $participant) {
			foreach ($this->data->getAnswersForParticipant($participant->active_id) as $answerObj) {
				$other_questions_sum_power_diff += pow($list_of_sum_of_points[$answerObj->active_id] - (float)$answerObj->reached_points - $other_questions_average_array[$answerObj->question_id], 2);
			}
		}


		//Variances
		$current_question_variance = (1 / ($number_of_participants - 1)) * $current_question_sum_power_diff;
		$other_questions_variance = (1 / ($number_of_participants - 1)) * $other_questions_sum_power_diff;

		var_dump($other_questions_sum_power_diff);
		exit;


		//Go throw answers to this questions to take results needed for calculations
		foreach ($this->data->getAnswersForQuestion($a_question_id) as $answerObj) {
			//Get Participant id.
			$participant_active_id = $answerObj->active_id;
			$other_questions_sum_power_diff = 0.0;
			$participant_data = $this->data->getParticipant($participant_active_id);

			foreach ($this->data->getAnswersForParticipant($participant_active_id, TRUE) as $participants_answer) {
				$other_questions_sum_power_diff += pow((float)$participants_answer->reached_points - $other_questions_average, 2);
			}
			if ($answerObj->answered) {
				//Fetch the sum of squared differences between total score and it mean
				$sum_power_diff += pow((float)$answerObj->reached_points - $question_average_points, 2);
				$count++;
			}
		}

		//Calculate Variance
		$variance = (1 / ($count - 1)) * $sum_power_diff;
		$other_questions_variance = (1 / ($count - 1)) * $other_questions_sum_power_diff;

		if ($variance * $other_questions_variance) {
			var_dump($other_questions_sum_power_diff);
			Exit;
		}

		$value->type = ilExteStatValue::TYPE_PERCENTAGE;
		$value->value = $standard_deviation;
		$value->precision = 4;
		if ($count == 0) {
			$value->alert = ilExteStatValue::ALERT_MEDIUM;
			$value->comment = $this->txt('no_answer_available');
		}

		return $value;
	}

}
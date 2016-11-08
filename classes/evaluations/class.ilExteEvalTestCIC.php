<?php

/**
 * Example evaluation for a whole test
 */
class ilExteEvalTestCIC extends ilExteEvalTest
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
	 * @var array list of allowed test types, e.g. array(self::TEST_TYPE_FIXED)
	 */
	protected static $allowed_test_types = array(self::TEST_TYPE_FIXED);

	/**
	 * @var array    list of question types, e.g. array('assSingleChoice', 'assMultipleChoice', ...)
	 */
	protected static $allowed_question_types = array();


	/**
	 * Calculate and get the single value for a test
	 * It sorts the list of currently results and returns the middle value
	 * if the number of attemps are odd, or return the average between the
	 * two middle values if the list number of attemps are even.
	 *
	 * @return ilExteStatValue
	 */
	public function calculateValue()
	{
		$cic = new ilExteStatValue;
        $cic->type = ilExteStatValue::TYPE_PERCENTAGE;
        $cic->precision = 4;
        $cic->value = null;

		//Get the data we need.
		$data = array();
		$number_of_questions = count($this->data->getAllQuestions());
		$number_of_users = count($this->data->getAllParticipants());

        if ($number_of_users < 2)
        {
            $cic->alert = ilExteStatValue::ALERT_UNKNOWN;
            $cic->comment = $this->plugin->txt('not_enough_test_results');
            return $cic;
        }

		//PART1
		$sumofmarkvariance = 0;
		foreach ($this->data->getAllQuestions() as $question_id => $question)
        {
			$full_answers = $this->data->getAnswersForQuestion($question_id);
			$data["sum"][$question_id] = 0.0;
			foreach ($full_answers as $answer)
            {
				$data["data"][$question_id][$answer->active_id] = $answer->reached_points;
				$data["sum"][$question_id] += $answer->reached_points;
			}
			$question_average = $data["sum"][$question_id] / $number_of_users;
			$data["mean"][$question_id] = $question_average;

			$full_answers_2 = $this->data->getAnswersForQuestion($question_id);
			foreach ($full_answers_2 as $answer_2)
            {
				$mark_difference = $answer_2->reached_points - $question_average;
				$data["calc_markvariancesum"] += pow($mark_difference, 2);
			}

			$data["markvariancesum"][$question_id] = $data["calc_markvariancesum"] / ($number_of_users - 1);
			$sumofmarkvariance += $data["markvariancesum"][$question_id];
			$data["calc_markvariancesum"] = 0;
		}

		//PART2
		$full_participants = $this->data->getAllParticipants();
		$mean = $this->getMeanOfReachedPoints();
		$sum_of_mean = 0;

		foreach ($full_participants as $active_id => $participant)
        {
			//Calculate
			$sum_of_mean += pow((float)$participant->current_reached_points - (float)$mean->value, 2);
		}

        if ($sum_of_mean == 0)
        {
            $cic->alert = ilExteStatValue::ALERT_UNKNOWN;
            $cic->comment = $this->txt('sum_of_mean_is_zero');
            return $cic;
        }

		$m2 = $sum_of_mean / $number_of_users;
		$k2 = $number_of_users * $m2 / ($number_of_users - 1);

		//GET VALUE
		$cic->value = (100 * $number_of_questions / ($number_of_questions - 1)) * (1 - ($sumofmarkvariance / $k2));;

		return $cic;
	}

	/**
	 * Calculate the details for a test
	 *
	 * @return ilExteStatDetails[]
	 */
	public function calculateDetails()
	{
		return array();
	}
}
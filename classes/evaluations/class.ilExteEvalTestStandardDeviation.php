<?php

/**
 * Example evaluation for a whole test
 */
class ilExteEvalTestStandardDeviation extends ilExteEvalTest
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
	 * @var array list of allowed test types, e.g. array(self::TEST_TYPE_FIXED)
	 */
	protected $allowed_test_types = array();

	/**
	 * @var array    list of question types, e.g. array('assSingleChoice', 'assMultipleChoice', ...)
	 */
	protected $allowed_question_types = array();

	/**
	 * @var string	specific prefix of language variables (lowercase classname is default)
	 */
	protected $lang_prefix = 'tst_standarddeviation';


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
		$standard_deviation = new ilExteStatValue;

		//Needed values
		$participants_data = $this->data->getAllParticipants();
		$basic_test_values = $this->data->getBasicTestValues();
		$mean = $basic_test_values['tst_eval_mean_of_reached_points'];

		$value_data = array();
		foreach($participants_data as $participant){
			$value_data[$participant->active_id] = $participant->current_reached_points;
		}

		//If more than one participant, then calculate.
		if (count($value_data) > 1) {
			//Fetch the sum of squared differences between total score and it's mean
			$sum_sq_diff = $this->sumOfPowersOfDifferenceToMean($value_data, $mean->value, 2);
			//Calculate Standard deviation
			$std_deviation = sqrt($sum_sq_diff / (count($value_data) - 1));

			$standard_deviation->type = ilExteStatValue::TYPE_NUMBER;
			$standard_deviation->value = $std_deviation;
			$standard_deviation->precision = 2;

		} else {
			$std_deviation = $this->txt("only_one_participant");

			$standard_deviation->type = ilExteStatValue::TYPE_TEXT;
			$standard_deviation->comment = $std_deviation;
			$standard_deviation->alert = ilExteStatValue::ALERT_MEDIUM;
		}

		return $standard_deviation;
	}


	/**
	 * Calculate the sum of powers of the difference from values to their mean
	 * (intermediate calculation for the standard deviation)
	 *
	 * @param array         $data   list of values
	 * @param float         $mean   mean of values
	 * @param integer       $power  power to use
	 * @return float|int            calculated sum
	 */
	protected function sumOfPowersOfDifferenceToMean($data, $mean, $power = 2)
	{
		$sum_power_diff = 0.0;

		//Fetch the sum of squared differences between total score and it's mean
		foreach ($data as $id => $item) {
			$sum_power_diff += pow((float)$item - $mean, $power);
		}

		return $sum_power_diff;
	}
}
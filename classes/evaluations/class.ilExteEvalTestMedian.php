<?php
// Copyright (c) 2017 Institut fuer Lern-Innovation, Friedrich-Alexander-Universitaet Erlangen-Nuernberg, GPLv3, see LICENSE

/**
 * Example evaluation for a whole test
 */
class ilExteEvalTestMedian extends ilExteEvalTest
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
	protected $lang_prefix = 'tst_median';


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
		$value = new ilExteStatValue;

		$basic_test_values = $this->data->getBasicTestValues();

		//Total attemps evaluated
		$total_attempts = $basic_test_values["tst_eval_total_persons"]->value;

		//Sort the list of results
		$data = $this->data->getAllParticipants();
		usort($data, array("ilExteEvalTestMedian", "cmp"));

		if ($total_attempts % 2 === 0)
		{
			//Attemps are even, take two middle values
			$major = $data[$total_attempts / 2];
			$minor = $data[$total_attempts / 2 - 1];

			//Returns the average
			$median = ((float)$minor->current_reached_points + (float)$major->current_reached_points) / 2;

			$value->type = ilExteStatValue::TYPE_NUMBER;
			$value->value = $median;
			$value->precision = 2;

			return $value;
		} else
		{
			//Attemps are odd, returns the middle value
			$median = (int)floor($total_attempts / 2);

			$value->type = ilExteStatValue::TYPE_NUMBER;
			$value->value = $data[$median]->current_reached_points;
			$value->precision = 2;

			return $value;
		}
	}

	/** Compare function for sorting an array of Participants in a test by current reached points.
	 * @param $a
	 * @param $b
	 * @return int
	 */
	public function cmp($a, $b)
	{
		if (is_a($a, "ilExteStatSourceParticipant") AND is_a($b, "ilExteStatSourceParticipant")) {
			if ((float)$a->current_reached_points == (float)$b->current_reached_points) {
				return 0;
			}
			return ((float)$a->current_reached_points < (float)$b->current_reached_points) ? -1 : 1;
		} else {
			return 0;
		}
	}
}
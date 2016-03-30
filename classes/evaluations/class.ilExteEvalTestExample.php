<?php

/**
 * Example evaluation for a whole test
 */
class ilExteEvalTestExample extends ilExteEvalTest
{
	/**
	 * @var bool	evaluation provides a single value for the overview level
	 */
	protected static $provides_value = true;

	/**
	 * @var bool	evaluation provides data for a details screen
	 */
	protected static $provides_details = false;

	/**
	 * @var array list of allowed test types, e.g. array(self::TEST_TYPE_FIXED)
	 */
	protected static $allowed_test_types = array(self::TEST_TYPE_FIXED);

	/**
	 * @var array	list of question types, e.g. array('assSingleChoice', 'assMultipleChoice', ...)
	 */
	protected static $allowed_question_types = array();


	/**
	 * Calculate and get the single value for a test
	 * This example just counts the sent answers
	 *
	 * @return ilExteStatValue
	 */
	public function calculateValue()
	{
		$value = new ilExteStatValue;

		$count = 0;
		foreach ($this->data->getAllAnswers() as $answerObj)
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
	 * Calculate the details for a test
	 *
	 * @return ilExteStatDetails[]
	 */
	public function calculateDetails()
	{
        return array();
	}

}
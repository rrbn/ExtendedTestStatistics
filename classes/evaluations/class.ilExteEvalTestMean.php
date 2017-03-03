<?php

/**
 * Example evaluation for a whole test
 */
class ilExteEvalTestMean extends ilExteEvalTest
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
	 * @var string	specific prefix of language variables (lowercase classname is default)
	 */
	protected static $lang_prefix = 'tst_mean';


	/**
	 * Calculate and get the single value for a test
	 * Gets the mean score for all current scored attemps of this test
	 *
	 * @return ilExteStatValue
	 */
	public function calculateValue()
	{
		return $this->getMeanOfReachedPoints();
	}
}
<?php
// Copyright (c) 2017 Institut fuer Lern-Innovation, Friedrich-Alexander-Universitaet Erlangen-Nuernberg, GPLv3, see LICENSE

/**
 * Example evaluation for a whole test
 */
class ilExteEvalTestMean extends ilExteEvalTest
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
	protected $lang_prefix = 'tst_mean';


	/**
	 * Calculate and get the single value for a test
	 * Gets the mean score for all current scored attemps of this test
	 *
	 * @return ilExteStatValue
	 */
	public function calculateValue()
	{
		$basic_test_values = $this->data->getBasicTestValues();
		return $basic_test_values['tst_eval_mean_of_reached_points'];
	}
}
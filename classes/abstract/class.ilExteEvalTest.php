<?php

/**
 * Class ilExteEvalTest
 */
abstract class ilExteEvalTest extends ilExteEvalBase
{

	/**
	 * This evaluation is for tests
	 * @return bool
	 */
	final public static function _isTestEvaluation()
	{
		return true;
	}

	/**
	 * Calculate and get the single value for a test (to be overwritten)
	 * @return ilExteStatValue
	 */
	public function calculateValue() {}

	/**
	 * Calculate the details for a test (to be overwritten)
	 * @return ilExteStatDetails
	 */
	public function calculateDetails() {}


}
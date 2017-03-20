<?php
// Copyright (c) 2017 Institut fuer Lern-Innovation, Friedrich-Alexander-Universitaet Erlangen-Nuernberg, GPLv3, see LICENSE

/**
 * Class ilExteEvalTest
 */
abstract class ilExteEvalTest extends ilExteEvalBase
{
	/**
	 * Calculate and get the single value for a test (to be overwritten)
	 *
	 * @return ilExteStatValue
	 */
	protected function calculateValue()
	{
		return new ilExteStatValue;
	}

	/**
	 * Calculate the details for a test (to be overwritten)
	 *
	 * @return ilExteStatDetails
	 */
	protected function calculateDetails()
	{
		return new ilExteStatDetails();
	}

	/**
	 * Get the calculated value
	 * This checks if the test type matches before
	 *
	 * @return ilExteStatValue
	 */
	final public function getValue()
	{
		if (!$this->isTestTypeAllowed())
		{
			$message = $this->getMessageNotAvailableForTestType();
			return ilExteStatValue::_create(null, ilExteStatValue::TYPE_TEXT, 0, $message, ilExteStatValue::ALERT_UNKNOWN);
		}
		else
		{
			return $this->calculateValue();
		}
	}

	/**
	 * Get the calculated details
	 * This checks if the test type matches before
	 *
	 * @return ilExteStatDetails
	 */
	final public function getDetails()
	{
		if (!$this->isTestTypeAllowed())
		{
			$message = $this->getMessageNotAvailableForTestType();
			return (new ilExteStatDetails)->setEmptyMessage($message);
		}
		else
		{
			return $this->calculateDetails();
		}
	}
}
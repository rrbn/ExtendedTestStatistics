<?php
// Copyright (c) 2017 Institut fuer Lern-Innovation, Friedrich-Alexander-Universitaet Erlangen-Nuernberg, GPLv3, see LICENSE

/**
 * Class ilExteEvalTest
 */
abstract class ilExteEvalTest extends ilExteEvalBase
{
	/**
	 * Calculate and get the single value for a test (to be overwritten)
	 */
	protected function calculateValue() : ilExteStatValue
	{
		return new ilExteStatValue;
	}

	/**
	 * Calculate the details for a test (to be overwritten)
	 */
	protected function calculateDetails() : ilExteStatDetails
	{
		return new ilExteStatDetails();
	}

	/**
	 * Get the calculated value
	 * This checks if the test type matches before
	 */
	final public function getValue() : ilExteStatValue
	{
		if (!$this->isTestTypeAllowed())
		{
			$message = $this->getMessageNotAvailableForTestType();
			return ilExteStatValue::_create(null, ilExteStatValue::TYPE_TEXT, 0, $message, ilExteStatValue::ALERT_UNKNOWN);
		}

		$value = $this->cache->read(get_called_class(), 'value');
		if (!isset($value))
		{
			$value = $this->calculateValue();
			$this->cache->write(get_called_class(), 'value', serialize($value));
		}
		else
		{
			$value = unserialize($value);
		}
		return $value;

	}

	/**
	 * Get the calculated details
	 * This checks if the test type matches before
	 */
	final public function getDetails() : ilExteStatDetails
	{
		if (!$this->isTestTypeAllowed())
		{
			$message = $this->getMessageNotAvailableForTestType();
			$details = new ilExteStatDetails;
			return $details->setEmptyMessage($message);
		}

		$details = $this->cache->read(get_called_class(), 'details');
		if (!isset($details))
		{
			$details = $this->calculateDetails();
			$this->cache->write(get_called_class(), 'details', serialize($details));
		}
		else
		{
			$details = unserialize($details);
		}
		return $details;
    }

    /**
     * Get the chart created by this evaluation
     */
	final public function getChart() : ilChart
    {
        return $this->generateChart($this->getDetails());
    }
    
    /**
     * Get the custom HTML
     */
    final public function getCustomHTML() : string
    {
    	$details = $this->getDetails();
    	return $details->customHTML;
    }
}
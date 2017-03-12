<?php

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


    /**
     * Get the mean of reached points by all participants
     * @return ilExteStatValue|null
     */
    protected function getMeanOfReachedPoints()
	{
		// Cache the mean value for different calculations
		static $mean;

		if (!isset($mean)) {

			$mean = new ilExteStatValue;
			$basic_test_values = $this->data->getBasicTestValues();
			$scoring_sum = 0;

			//Total attemps evaluated
			$total_attempts = $basic_test_values["tst_eval_total_persons"]->value;

			//Sum all current reached points
			$data = $this->data->getAllParticipants();
			foreach ($data as $attemp) {
				$scoring_sum += (float)$attemp->current_reached_points;
			}

			//Returns the mean
			if($total_attempts){
				$mean->value = $scoring_sum / $total_attempts;
				$mean->type = ilExteStatValue::TYPE_NUMBER;
				$mean->precision = 2;
			}else{
				$mean->value = "NAN";
				$mean->type = ilExteStatValue::TYPE_TEXT;
				$mean->alert = ilExteStatValue::ALERT_MEDIUM;
			}
		}

		return $mean;
	}
}
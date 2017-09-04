<?php
// Copyright (c) 2017 Institut fuer Lern-Innovation, Friedrich-Alexander-Universitaet Erlangen-Nuernberg, GPLv3, see LICENSE

/**
 * Base class for statistical evaluation of questions in a test
 */
abstract class ilExteEvalQuestion extends ilExteEvalBase
{
	/**
	 * Calculate the single value for a question (to be overwritten)
	 *
	 * Note:
	 * This function will be called for many questions in sequence
	 * - Please avoid instanciation of question objects
	 * - Please try to cache question independent intermediate results
	 *
	 * @param integer $a_question_id
	 * @return ilExteStatValue
	 */
	protected function calculateValue($a_question_id)
    {
        return new ilExteStatValue;
    }

	/**
	 * Calculate the details question (to be overwritten)
     *
	 * @param integer $a_question_id
	 * @return ilExteStatDetails
	 */
	protected function calculateDetails($a_question_id)
    {
       return new ilExteStatDetails();
    }

	/**
	 * Get the calculated value
	 * This checks if the test type matches before
	 *
	 * @param integer $a_question_id
	 * @return ilExteStatValue
	 */
	final public function getValue($a_question_id)
	{
		if (!$this->isTestTypeAllowed())
		{
			$message = $this->getMessageNotAvailableForTestType();
			return ilExteStatValue::_create(null, ilExteStatValue::TYPE_TEXT, 0, $message, ilExteStatValue::ALERT_UNKNOWN);
		}
		elseif (!$this->isQuestionTypeAllowed($this->data->getQuestion($a_question_id)->question_type))
		{
			$message = $this->getMessageNotAvailableForQuestionType();
			return ilExteStatValue::_create(null, ilExteStatValue::TYPE_TEXT, 0, $message, ilExteStatValue::ALERT_UNKNOWN);
		}

		$value = $this->cache->read(get_called_class(), 'value'. $a_question_id);
		if (!isset($value))
		{
			$value = $this->calculateValue($a_question_id);
			$this->cache->write(get_called_class(), 'value'. $a_question_id, serialize($value));
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
	 *
	 * @param integer $a_question_id
	 * @return ilExteStatDetails
	 */
	final public function getDetails($a_question_id)
	{
		if (!$this->isTestTypeAllowed())
		{
			$message = $this->getMessageNotAvailableForTestType();
			$details = new ilExteStatDetails;
			return $details->setEmptyMessage($message);
		}
		elseif (!$this->isQuestionTypeAllowed($this->data->getQuestion($a_question_id)->question_type))
		{
			$message = $this->getMessageNotAvailableForQuestionType();
			$details = new ilExteStatDetails;
			return $details->setEmptyMessage($message);
		}

		$details = $this->cache->read(get_called_class(), 'details'. $a_question_id);
		if (!isset($details))
		{
			$details = $this->calculateDetails($a_question_id);
			$this->cache->write(get_called_class(), 'details'. $a_question_id, serialize($details));
		}
		else
		{
			$details = unserialize($details);
		}
		return $details;
	}

    /**
     * Get the chart created by this evaluation
     *
     * @param integer $a_question_id
     * @return ilChart
     */
    final public function getChart($a_question_id)
    {
        return $this->generateChart($this->calculateDetails($a_question_id));
    }
}
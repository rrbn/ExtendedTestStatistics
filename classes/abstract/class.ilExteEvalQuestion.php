<?php

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
		else
		{
			return $this->calculateValue($a_question_id);
		}
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
			return (new ilExteStatDetails)->setEmptyMessage($message);
		}
		elseif (!$this->isQuestionTypeAllowed($this->data->getQuestion($a_question_id)->question_type))
		{
			$message = $this->getMessageNotAvailableForQuestionType();
			return (new ilExteStatDetails)->setEmptyMessage($message);
		}
		else
		{
			return $this->calculateDetails($a_question_id);
		}
	}
}
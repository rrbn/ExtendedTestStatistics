<?php

/**
 * Base class for statistical evaluation of questions in a test
 */
abstract class ilExteEvalQuestion extends ilExteEvalBase
{
	/**
	 * This evaluation is for questions
     *
	 * @return bool
	 */
	final public static function _isQuestionEvaluation()
	{
		return true;
	}

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
	public function calculateValue($a_question_id)
    {
        return new ilExteStatValue;
    }

	/**
	 * Calculate the details question (to be overwritten)
     *
	 * @param integer $a_question_id
	 * @return ilExteStatDetails[]
	 */
	public function calculateDetails($a_question_id)
    {
        return array();
    }
}
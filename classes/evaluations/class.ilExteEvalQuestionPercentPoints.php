<?php
// Copyright (c) 2017 Institut fuer Lern-Innovation, Friedrich-Alexander-Universitaet Erlangen-Nuernberg, GPLv3, see LICENSE

/**
 * Provide the mean reached percentage of maximum points from all assigned of this question
 */
class ilExteEvalQuestionPercentPoints extends ilExteEvalQuestion
{
    /**
     * @var bool    evaluation provides a single value for the overview level
     */
    protected $provides_value = true;

    /**
     * @var bool    evaluation provides a chart of the values presented in the overview of questions
     */
    protected $provides_overview_chart = true;

    /**
     * @var bool    evaluation provides data for a details screen
     */
    protected $provides_details = false;

    /**
     * @var bool    evaluation provides data for a details screen
     */
    protected $provides_chart = false;

    /**
     * @var array   list of allowed test types, e.g. array(self::TEST_TYPE_FIXED)
     */
    protected $allowed_test_types = array();

    /**
     * @var array    list of question types, e.g. array('assSingleChoice', 'assMultipleChoice', ...)
     */
    protected $allowed_question_types = array();

	/**
	 * @var string	specific prefix of language variables (lowercase classname is default)
	 */
	protected $lang_prefix = 'qst_percent_points';


    /**
     * Calculate the single value for a question (to be overwritten)
     *
     * Note:
     * This function will be called for many questions in sequence
     * - Please avoid instantiation of question objects
     * - Please try to cache question independent intermediate results
     *
     * @param integer $a_question_id
     * @return ilExteStatValue
     */
    public function calculateValue($a_question_id)
    {
        $questionObj = $this->data->getQuestion($a_question_id);
        
        if ($questionObj->assigned_count == 0) {
            return ilExteStatValue::_create(0, ilExteStatValue::TYPE_PERCENTAGE,
                0, '', $this->txt('not_assigned'));
        }

        return ilExteStatValue::_create(
            $questionObj->average_percentage,
            ilExteStatValue::TYPE_PERCENTAGE, 2);
    }


}
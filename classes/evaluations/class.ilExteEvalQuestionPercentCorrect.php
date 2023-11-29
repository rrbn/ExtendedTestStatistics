<?php
// Copyright (c) 2017 Institut fuer Lern-Innovation, Friedrich-Alexander-Universitaet Erlangen-Nuernberg, GPLv3, see LICENSE

/**
 * Prvide the percentage of correctly answered from all assigned of this question
 */
class ilExteEvalQuestionPercentCorrect extends ilExteEvalQuestion
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
     * @var bool    evaluation provides data for a details screen
     */
    protected $provides_chart = true;

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
	protected $lang_prefix = 'qst_correct';


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
        
        $correct_count = 0;
        foreach ($this->data->getAnswersForQuestion($a_question_id) as $answerObj) {
            if ($answerObj->reached_points >= $questionObj->maximum_points) {
                $correct_count++;
            }
        }

        return ilExteStatValue::_create(
            100 * $correct_count / $questionObj->assigned_count, 
            ilExteStatValue::TYPE_PERCENTAGE, 2);
    }

    /**
     * Calculate the details for a diagram to be displayed on the overview page
     * 
     * @param array $question_ids
     * @return ilChartGrid
     */
    public function getOverviewChart($question_ids = [])
    {
        $details = new ilExteStatDetails();
        $details->columns = array (
            ilExteStatColumn::_create('question_pos',$this->plugin->txt('question_position'), ilExteStatColumn::SORT_NUMBER),
            ilExteStatColumn::_create('question_title',$this->plugin->txt('question_title'),ilExteStatColumn::SORT_TEXT),
            ilExteStatColumn::_create('percent_correct',$this->txt('title_long'),ilExteStatColumn::SORT_NUMBER, '', true),
        );
        $details->chartType = ilExteStatDetails::CHART_BARS;
        $details->chartLabelsColumn = 1;
        $details->chartLines = [25 => '25%', 50 => '50%', 75 => '75%', 100 => '100%'];
        
        $questions = $this->data->getAllQuestions();
        
        $num_assigned = 0;
        $sum_percent = 0;
        $values = [];
        foreach ($questions as $question) {
            if ($question->assigned_count > 0) {
                
                $value = $this->calculateValue($question->question_id);
                $values[$question->question_id] = $value;

                $num_assigned++;
                $sum_percent += $value->value;
            }
        }
        
        if ($num_assigned > 0) {
            $average_percent = round($sum_percent / $num_assigned, 2);
            $details->chartLines[$average_percent] = '<strong>' . $this->plugin->txt('average_sign') . ' '. $average_percent . '%</strong>';
            ksort($details->chartLines);
        }

        
        foreach ($question_ids as $question_id) {
            if (isset($questions[$question_id])) {
                $question = $questions[$question_id];
                
                $title = ilUtil::shortenText($question->question_title, 20, true);
                if ($this->data->getTestType() == ilExteEvalBase::TEST_TYPE_FIXED) {
                    $title .= ' (' . $question->order_position . ')';
                }
                
                $details->rows[] = array(
                    'question_pos' => ilExteStatValue::_create($question->order_position, ilExteStatValue::TYPE_NUMBER, 0),
                    'question_title' => ilExteStatValue::_create($title),
                    'percent_correct' => $values[$question_id],
                );
            }
        }

        /**
         * @var ilChartGrid
         */
        $chart = $this->generateChart($details);
        
        return $chart;
    }

}
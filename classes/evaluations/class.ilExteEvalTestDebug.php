<?php

/**
 * Example evaluation for a whole test
 */
class ilExteEvalTestDebug extends ilExteEvalTest
{
	/**
	 * @var bool	evaluation provides a single value for the overview level
	 */
	protected static $provides_value = false;

	/**
	 * @var bool	evaluation provides data for a details screen
	 */
	protected static $provides_details = true;

	/**
	 * @var array list of allowed test types, e.g. array(self::TEST_TYPE_FIXED)
	 */
	protected static $allowed_test_types = array();

	/**
	 * @var array	list of question types, e.g. array('assSingleChoice', 'assMultipleChoice', ...)
	 */
	protected static $allowed_question_types = array();


	/**
	 * Calculate and get the single value for a test
	 *
	 * @return ilExteStatValue
	 */
	public function calculateValue()
	{
        return new ilExteStatValue;
	}


	/**
	 * Calculate the details for a test
	 *
	 * @return ilExteStatDetails[]
	 */
	public function calculateDetails()
	{
        $return = array();

        // participant details
        $details = new ilExteStatDetails();
        $details->id = 'participants';
        $details->title = $this->txt('participants_title');
        $details->description = $this->txt('participants_description');
        $details->columns = array (
            ilExteStatColumn::_create('active_id','',ilExteStatColumn::SORT_NUMBER),
            ilExteStatColumn::_create('last_pass','',ilExteStatColumn::SORT_NUMBER),
            ilExteStatColumn::_create('best_pass','',ilExteStatColumn::SORT_NUMBER),
            ilExteStatColumn::_create('scored_pass','',ilExteStatColumn::SORT_NUMBER),
			ilExteStatColumn::_create('reached_points','',ilExteStatColumn::SORT_NUMBER)
		);
        foreach ($this->data->getAllParticipants() as $participant)
        {
            $details->rows[] = array(
                'active_id' => ilExteStatValue::_create($participant->active_id, ilExteStatValue::TYPE_NUMBER, 0),
                'last_pass' => ilExteStatValue::_create($participant->last_pass, ilExteStatValue::TYPE_NUMBER, 0),
                'best_pass' => ilExteStatValue::_create($participant->best_pass, ilExteStatValue::TYPE_NUMBER, 0),
                'scored_pass' => ilExteStatValue::_create($participant->scored_pass, ilExteStatValue::TYPE_NUMBER, 0),
				'reached_points' => ilExteStatValue::_create($participant->current_reached_points, ilExteStatValue::TYPE_TEXT, 0)
			);
        }
        $return[] = $details;

        // question details
        $details = new ilExteStatDetails();
        $details->id = 'questions';
        $details->title = $this->txt('questions_title');
        $details->description = $this->txt('questions_description');
        $details->columns = array (
            ilExteStatColumn::_create('question_id','',ilExteStatColumn::SORT_NUMBER),
            ilExteStatColumn::_create('question_title','',ilExteStatColumn::SORT_TEXT),
            ilExteStatColumn::_create('question_type','',ilExteStatColumn::SORT_TEXT),
            ilExteStatColumn::_create('question_type_label','',ilExteStatColumn::SORT_TEXT),
            ilExteStatColumn::_create('assigned_count','',ilExteStatColumn::SORT_NUMBER),
            ilExteStatColumn::_create('answers_count','',ilExteStatColumn::SORT_NUMBER),
            ilExteStatColumn::_create('maximum_points','',ilExteStatColumn::SORT_NUMBER),
            ilExteStatColumn::_create('average_points','',ilExteStatColumn::SORT_NUMBER),
            ilExteStatColumn::_create('average_percentage','',ilExteStatColumn::SORT_NUMBER)
        );
        foreach ($this->data->getAllQuestions() as $question)
        {
            $details->rows[] = array(
                'question_id' => ilExteStatValue::_create($question->question_id, ilExteStatValue::TYPE_NUMBER, 0),
                'question_title' => ilExteStatValue::_create($question->question_title, ilExteStatValue::TYPE_TEXT),
                'question_type' => ilExteStatValue::_create($question->question_type, ilExteStatValue::TYPE_TEXT),
                'question_type_label' => ilExteStatValue::_create($question->question_type_label, ilExteStatValue::TYPE_TEXT),
                'maximum_points' => ilExteStatValue::_create($question->maximum_points, ilExteStatValue::TYPE_NUMBER, 2),
                'assigned_count' => ilExteStatValue::_create($question->assigned_count, ilExteStatValue::TYPE_NUMBER, 0),
                'answers_count' => ilExteStatValue::_create($question->answers_count, ilExteStatValue::TYPE_NUMBER, 0),
                'average_points' => ilExteStatValue::_create($question->average_points, ilExteStatValue::TYPE_NUMBER, 2),
                'average_percentage' => ilExteStatValue::_create($question->average_percentage, ilExteStatValue::TYPE_NUMBER, 2),
            );
        }
        $return[] = $details;

        // answer details
        $details = new ilExteStatDetails();
        $details->id = 'answers';
        $details->title = $this->txt('answers_title');
        $details->description = $this->txt('answers_description');
        $details->columns = array (
            ilExteStatColumn::_create('question_id','',ilExteStatColumn::SORT_NUMBER),
            ilExteStatColumn::_create('active_id','',ilExteStatColumn::SORT_NUMBER),
            ilExteStatColumn::_create('pass','',ilExteStatColumn::SORT_NUMBER),
            ilExteStatColumn::_create('sequence','',ilExteStatColumn::SORT_NUMBER),
            ilExteStatColumn::_create('answered','',ilExteStatColumn::SORT_NUMBER),
            ilExteStatColumn::_create('reached_points','',ilExteStatColumn::SORT_NUMBER),
            ilExteStatColumn::_create('manual_scored','',ilExteStatColumn::SORT_NUMBER),
        );
        foreach ($this->data->getAllAnswers() as $answer)
        {
            $details->rows[] = array(
                'question_id' => ilExteStatValue::_create($answer->question_id, ilExteStatValue::TYPE_NUMBER, 0),
                'active_id' => ilExteStatValue::_create($answer->active_id, ilExteStatValue::TYPE_NUMBER, 0),
                'pass' => ilExteStatValue::_create($answer->pass, ilExteStatValue::TYPE_NUMBER, 0),
                'sequence' => ilExteStatValue::_create($answer->sequence, ilExteStatValue::TYPE_NUMBER, 0),
                'answered' => ilExteStatValue::_create($answer->answered, ilExteStatValue::TYPE_BOOLEAN),
                'reached_points' => ilExteStatValue::_create($answer->reached_points, ilExteStatValue::TYPE_NUMBER, 2),
                'manual_scored' => ilExteStatValue::_create($answer->manual_scored, ilExteStatValue::TYPE_BOOLEAN),
            );
        }
        $return[] = $details;

        return $return;
	}
}
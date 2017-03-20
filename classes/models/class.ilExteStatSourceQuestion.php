<?php
// Copyright (c) 2017 Institut fuer Lern-Innovation, Friedrich-Alexander-Universitaet Erlangen-Nuernberg, GPLv3, see LICENSE

/**
 * Data model for basic evaluation results of a question in a test
 *
 * This data is calculated for all questions before the evaluations
 * It can be used to sort and slice the questions presented on a screen
 * The further evaluation are only calculated for those questions
 */
class ilExteStatSourceQuestion
{
	/**
	 @var integer 	question id
	 */
	public $question_id;

	/**
	 * @var integer	id of the original question
	 */
	public $original_id;

	/**
	 * @var string 		type tag of the question, e.g. 'assSingleChoice'
	 */
	public $question_type;

    /**
     * @var  string     label of the question type, e.g. 'Single Choice Question'
     */
    public $question_type_label;

    /**
	 * @var integer 	question title
	 */
	public $question_title;

	/**
	 * @var float	maximum points that can be reached in the question
	 */
	public $maximum_points;

	/**
	 * @var	integer	order position of the question in a fixed test
	 */
	public $order_position;

	/**
	 * @var bool obligatory status of the question in a fixed test
	 */
	public $obligatory;

	/**
	 * @var	float	average of points reached by the participants that got this question assigned
	 */
	public $average_points;

	/**
	 * @var	float 	average percentage reached by the participants that got this question assigned
	 */
	public $average_percentage;

	/**
	 * @var integer	number of users who answered the question
	 */
	public $answers_count;

    /**
     * @var integer	number of users who got this question assigned
     */
    public $assigned_count;
}
<?php

/**
 * Data model for the single answer of a test question
 * This model is created for every question presented to a participant
 * It is even created if the question is not answered
 */
class ilExteStatSourceAnswer
{
	/**
	 * @var integer		id of the answered question
	 */
	public $question_id;

	/**
	 * @var integer 	id of the active participant
	 */
	public $active_id;

	/**
	 * @var integer		number of the test pass in which the question was presented
	 */
	public $pass;

	/**
	 * @var	integer		sequence number of the question in the pass
	 */
	public $sequence;

	/**
	 * @var bool		the question was answered by the participant
	 */
	public $answered;


	/**
	 * @var float	actual points reached for the question
	 */
	public $reached_points;


	/**
	 * @var	bool	score is set manually
	 */
	public $manual_scored;
}
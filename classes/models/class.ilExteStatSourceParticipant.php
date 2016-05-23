<?php

/**
 * Class ilExteStatSourceParticipant
 */
class ilExteStatSourceParticipant
{
	/**
	 * @var integer		the participant id
	 */
	public $active_id;

	/**
	 * @var integer		index of the last pass
	 */
	public $last_pass;

	/**
	 * @var integer		index of the best pass
	 */
	public $best_pass;

	/**
	 * @var integer		index of the scored pass
	 */
	public $scored_pass;

	/**
	 * @var float		points currently reached by the participant.
	 */
	public $current_reached_points;
}
<?php
// Copyright (c) 2017 Institut fuer Lern-Innovation, Friedrich-Alexander-Universitaet Erlangen-Nuernberg, GPLv3, see LICENSE

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
	 * @var float		points reached by the participant in the currently selected pass
	 */
	public $current_reached_points;
}
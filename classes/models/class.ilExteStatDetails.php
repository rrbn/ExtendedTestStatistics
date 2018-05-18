<?php
// Copyright (c) 2017 Institut fuer Lern-Innovation, Friedrich-Alexander-Universitaet Erlangen-Nuernberg, GPLv3, see LICENSE

/**
 * Class ilExteStatDetails
 */
class ilExteStatDetails
{
    const CHART_BARS = 'bars';
    const CHART_PIE = 'pie';
    const CHART_SPIDER = 'spider';

	/**
	 * Individual message for empty details
	 * @var string
	 */
	protected $emptyMessage;

    /**
     * Table columns
     * @var ilExteStatColumn[]
     */
    public $columns = array();

    /**
     * Table rows
     * @var array   rownum => colname => ilExteStatValue
     */
	public $rows = array();


    /**
     * Type of the chart to be generated
     * @var null
     */
	public $chartType = null;

    /**
     * Index of the column to define the X axis
     * @var int
     */
    public $chartLabelsColumn = 0;

    /**
     * Custom HTML for the evaluation
     * @var string
     */
    public $customHTML = '';
    
	/**
	 * Get the message for empty details
	 * @return string
	 */
	public function getEmptyMessage()
	{
		global $lng;

		if (isset($this->emptyMessage))
		{
			return $this->emptyMessage;
		}
		return $lng->txt('no_items');
	}

	/**
	 * Get the message for empty details
	 * @param string	$message
	 * @return self
	 */
	public function setEmptyMessage($message)
	{
		$this->emptyMessage = $message;
		return $this;
	}
}
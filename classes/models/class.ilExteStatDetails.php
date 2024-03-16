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
	 */
	protected ?string $emptyMessage;

    /**
     * Table columns
     * @var ilExteStatColumn[]
     */
    public array $columns = [];

    /**
     * Table rows
     * rownum => colname => ilExteStatValue
     */
	public array $rows = [];


    /**
     * Type of the chart to be generated
     */
	public ?string $chartType = null;

    /**
     * Index of the column to define the X axis
     */
    public int $chartLabelsColumn = 0;

    /**
     * Horizontal lines to be presented in a bar chart 
     * This also allow to set a maximum value for the diagram
     * Lines will be auto-generated, if null
     * value => label
     */
    public ?array $chartLines = null;

    /**
     * Custom HTML for the evaluation
     */
    public string $customHTML = '';


    protected ilLanguage $lng;


    /**
     * Constructor
     */
    public function __construct()
    {
        global $DIC;
        $this->lng = $DIC->language();
    }

	/**
	 * Get the message for empty details
	 */
	public function getEmptyMessage(): string
	{
		if (isset($this->emptyMessage))
		{
			return $this->emptyMessage;
		}

		return $this->lng->txt('no_items');
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
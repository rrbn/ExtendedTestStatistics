<?php

/**
 * Class ilExteStatDetails
 */
class ilExteStatDetails
{
    /**
     * Id
     * Should be a short variable-like name which is unique within an evaluation
     * @var string
     */
    public $id;

    /**
     * Title
     * @var string
     */
    public $title;

    /**
     * Description
     * @var string
     */
    public $description;

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
}
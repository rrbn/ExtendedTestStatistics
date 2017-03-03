<?php

/**
 * Class ilExteStatDetails
 */
class ilExteStatDetails
{
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
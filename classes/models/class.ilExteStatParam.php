<?php
// Copyright (c) 2017 Institut fuer Lern-Innovation, Friedrich-Alexander-Universitaet Erlangen-Nuernberg, GPLv3, see LICENSE

/**
 * Class ilExteStatParam
 */
class ilExteStatParam
{
	/**
	 * Defined parameter types
	 */
	const TYPE_FLOAT = 'float';
	const TYPE_INT = 'int';
	const TYPE_BOOLEAN = 'bool';
	const TYPE_STRING = 'string';

	/**
	 * @var string		name of the parameter (should be unique within an evaluation class)
	 */
	public $name;

	/**
	 * @var string		type of the parameter
	 */
	public $type;

	/**
	 * @var mixed 		actual value
	 */
	public $value;


    /**
     * Create a parameter
     *
     * @param string $a_name
     * @param string $a_type
	 * @param mixed $a_value
     * @return ilExteStatParam
     */
    public static function _create($a_name, $a_type = self::TYPE_INT, $a_value = 0)
    {
        $param = new self;
		$param->name = $a_name;
		$param->type = $a_type;
		$param->value = $a_value;
		
		return $param;
    }
}
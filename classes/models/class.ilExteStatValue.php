<?php

/**
 * Class ilExteStatValue
 */
class ilExteStatValue
{
	/**
	 * Defined cell types
	 */
	const TYPE_ALERT = 'alert';
	const TYPE_TEXT = 'text';
	const TYPE_NUMBER = 'number';
	const TYPE_PERCENTAGE = 'percentage';
	const TYPE_DATETIME = 'datetime';
	const TYPE_DURATION = 'duration';
	const TYPE_BOOLEAN = 'bool';

	/**
	 * Defined alert modes
	 */
	const ALERT_NONE = 'none';			// no alert
	const ALERT_UNKNOWN = 'unknown';	// grey icon or background
	const ALERT_GOOD = 'good';			// green icon or background
	const ALERT_MEDIUM = 'medium';		// yellow icon or background
	const ALERT_BAD = 'bad';			// red icon or background


	/**
	 * Type of the value
	 *
	 * TYPE_ALERT will show an icon with $value as alt text
	 * @var string
	 */
	public $type = self::TYPE_TEXT;

	/**
	 * Value to be displayed
	 *
	 * The data type and semantics depends on $type
	 * TYPE_ALERT: string (used as alt text of the icon or for text in Excel cells)
	 * TYPE_TEXT: string
	 * TYPE_NUMBER float or integer (will be rounded with $precision)
	 * TYPE_PERCENTAGE: float (0 to 100, the '%' sign will be added when displayed
	 * TYPE_DATETIME: ilDateTime
	 * TYPE_DURATION: integer (seconds)
	 * TYPE_BOOLEAN: boolean
	 * @var mixed
	 */
	public $value = null;

	/**
	 * Display precision to be used for TYPE_NUMERIC and TYPE_PERCENTAGE
	 *
	 * @var int
	 */
	public $precision = 2;

	/**
	 * Optional textual comment
	 *
	 * This may be shown as tooltip or additional info text
	 * @var string
	 */
	public $comment = '';


	/**
	 * Optional alert sign
	 *
	 * If $type is TYPE_ALERT, then only the sign will be shown
	 * Otherwise the sign will be shown beneath the text
	 *
	 * @var string	alert sign constant
	 */
	public $alert = self::ALERT_NONE;


    /**
     * Create a value by parameters
     *
     * @param mixed $a_value
     * @param string $a_type
     * @param int $a_precision
     * @return ilExteStatValue
     */
    public static function _create($a_value, $a_type = self::TYPE_TEXT, $a_precision = 2, $a_comment = '', $a_alert = self::ALERT_NONE)
    {
        $value = new self;
        $value->value = $a_value;
        $value->type = $a_type;
        $value->precision = $a_precision;
        $value->comment = $a_comment;
        $value->alert = $a_alert;

        return $value;
    }
}
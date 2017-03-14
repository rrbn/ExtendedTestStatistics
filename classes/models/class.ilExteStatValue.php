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
	 * Optional uncertainty
	 *
	 * Can be set if the calculated value has an uncertainty, e.g. due to a small data set
	 * This will be shown with a specific markup
	 * @var bool
	 */
	public $uncertain = false;


    /**
     * Create a value by parameters
     *
     * @param mixed $a_value
     * @param string $a_type
     * @param int $a_precision
	 * @param string $a_comment
	 * @param string $a_alert
	 * @param bool $a_uncertain
     * @return ilExteStatValue
     */
    public static function _create($a_value, $a_type = self::TYPE_TEXT, $a_precision = 2, $a_comment = '', $a_alert = self::ALERT_NONE, $a_uncertain = false)
    {
        $value = new self;
        $value->value = $a_value;
        $value->type = $a_type;
        $value->precision = $a_precision;
        $value->comment = $a_comment;
        $value->alert = $a_alert;
		$value->uncertain = $a_uncertain;

        return $value;
    }

	/**
	 * Get a list of  demo values for testing purpose
	 * @return ilExteStatValue[]
	 */
	public static function _getDemoValues()
	{
		return array(
			self::_create('Hallo', self::TYPE_TEXT, 0, 'Text'),
			self::_create(0.13, self::TYPE_NUMBER, 2, 'Float'),
			self::_create(48, self::TYPE_NUMBER, 0, 'Integer'),
			self::_create(null, self::TYPE_NUMBER, 0, 'Unknown Integer',  self::ALERT_UNKNOWN, true),
			self::_create(true, self::TYPE_BOOLEAN, 0, 'True Boolean', self::ALERT_GOOD, true),
			self::_create(false, self::TYPE_BOOLEAN, 0, 'False Boolean', self::ALERT_BAD, true),
			self::_create('Alert Text', self::TYPE_ALERT, 2, 'Alert', self::ALERT_MEDIUM, true),
			self::_create(new ilDateTime(time(),IL_CAL_UNIX), self::TYPE_DATETIME, 0, 'DateTime'),
			self::_create(10, self::TYPE_DURATION, 0, 'Duration'),
			self::_create(50, self::TYPE_PERCENTAGE, 0, 'Percentge'),
			self::_create(50.52, self::TYPE_PERCENTAGE, 2, 'Percentge')

		);
	}
}
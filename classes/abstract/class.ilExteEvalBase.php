<?php

/**
 * Base class for all statistical evaluations
 * This class is not directly inherited,
 * but their childs ilExteEvalTest and ilExteEvalQuestion
 */
abstract class ilExteEvalBase
{

	/**
	 * type settings for test types
	 */
	const TEST_TYPE_FIXED = 'FIXED';
	const TEST_TYPE_RANDOM = 'RANDOM';
	const TEST_TYPE_DYNAMIC = 'DYNAMIC';
	const TEST_TYPE_UNKNOWN = 'UNKNOWN';

	/**
	 * @var bool    evaluation provides a single value for the overview level
	 */
	protected static $provides_value = false;

	/**
	 * @var bool    evaluation provides data for a details screen
	 */
	protected static $provides_details = false;

	/**
	 * @var array 	list of allowed test types, e.g. array(self::TEST_TYPE_FIXED)
	 */
	protected static $allowed_test_types = array();

	/**
	 * @var array    list of question types, e.g. array('assSingleChoice', 'assMultipleChoice', ...)
	 */
	protected static $allowed_question_types = array();

	/**
	 * @var string	specific prefix of language variables (lowercase class name is used as default)
	 */
	protected static $lang_prefix = null;


	/**
	 * @var ilExtendedTestStatisticsPlugin    plugin object, used in txt() method
	 */
	protected $plugin;

	/**
	 * @var ilExteStatSourceData        source data for the calculations
	 */
	protected $data;

	/**
	 * ilExtendedTestStatisticsEvalBase constructor.
	 * @param ilExteStatSourceData $a_data
	 * @param ilExtendedTestStatisticsPlugin $a_plugin
	 */
	public function __construct($a_data, $a_plugin)
	{
		$this->data = $a_data;
		$this->plugin = $a_plugin;

		$this->plugin->includeClass('models/class.ilExteStatValue.php');
		$this->plugin->includeClass('models/class.ilExteStatColumn.php');
		$this->plugin->includeClass('models/class.ilExteStatDetails.php');
	}


	/**
	 * Get the prefix for language variables of the evaluation
	 * This prefix is used additionally to the prefix of the plugin
	 * The function has to be static for the plugin configuration
	 * because evaluation objects are not created there
	 *
	 * @return string	prefix
	 */
	public static function _getLangPrefix()
	{
		return isset(static::$lang_prefix) ? static::$lang_prefix : strtolower(get_called_class());
	}


	/**
	 * Get the title of the evaluation (to be used in lists or as headline)
	 * @return string
	 */
	public function getTitle()
	{
		return $this->txt('title_long');
	}

	/**
	 * Get a short title of the evaluation (to be used as a column header)
	 * @return string
	 */
	public function getShortTitle()
	{
		return $this->txt('title_short');
	}

	/**
	 * Get a description of the evaluation (shown as tooltip or info text)
	 * @return string
	 */
	public function getDescription()
	{
		return $this->txt('description');
	}

	/**
	 * @return bool
	 */
	public function isTestTypeAllowed()
	{
		return empty(static::$allowed_test_types) || in_array($this->data->getTestType(), static::$allowed_test_types);
	}

	/**
	 * @return bool
	 */
	final public function isQuestionTypeAllowed($a_type)
	{
		return empty(static::$allowed_question_types) || in_array($a_type, static::$allowed_question_types);
	}

	/**
	 * @return bool	evaluation provides a single value
	 */
	public function providesValue()
	{
		return static::$provides_value;
	}

	/**
	 * @return bool	evaluation provides an array of details
	 */
	public function providesDetails()
	{
		return static::$provides_details;
	}

	/**
	 * Get a localized text
	 * The language variable will be prefixed by self::_getLangPrefix()
	 *
	 * @param string $a_langvar language variable
	 * @return string
	 */
	protected function txt($a_langvar)
	{
		return $this->plugin->txt(self::_getLangPrefix() . '_' . $a_langvar);
	}


	/**
	 * Get a message saying that the evaluation is not available for the test type
	 * @return	string
	 */
	protected function getMessageNotAvailableForTestType()
	{
		switch ($this->data->getTestType())
		{
			case self::TEST_TYPE_FIXED:
				return $this->plugin->txt('not_for_fixed_test');

			case self::TEST_TYPE_RANDOM:
				return $this->plugin->txt('not_for_random_test');

			case self::TEST_TYPE_DYNAMIC:
				return $this->plugin->txt('not_for_dynamic_test');

			default:
				return $this->plugin->txt('not_for_test_type');
		}
	}

	/**
	 * Get a message saying that the evaluation is not available for the question type
	 * @return	string
	 */
	protected function getMessageNotAvailableForQuestionType()
	{
		return $this->plugin->txt('not_for_question_type');
	}

}
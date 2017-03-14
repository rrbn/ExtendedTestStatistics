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
	protected $provides_value = false;

	/**
	 * @var bool    evaluation provides data for a details screen
	 */
	protected $provides_details = false;

	/**
	 * @var array 	list of allowed test types, e.g. array(self::TEST_TYPE_FIXED)
	 */
	protected $allowed_test_types = array();

	/**
	 * @var array    list of question types, e.g. array('assSingleChoice', 'assMultipleChoice', ...)
	 */
	protected $allowed_question_types = array();

	/**
	 * @var string	specific prefix of language variables (lowercase class name is used as default)
	 */
	protected $lang_prefix = null;


	/**
	 * @var ilExtendedTestStatisticsPlugin    plugin object, used in txt() method
	 */
	protected $plugin;

	/**
	 * @var ilExteStatSourceData        	source data for the calculations
	 */
	protected $data;

	/**
	 * @var ilExteStatParam[]				evaluation parameters (indexed by name)
	 */
	protected $params = array();


	/**
	 * ilExtendedTestStatisticsEvalBase constructor.
	 * @param ilExtendedTestStatisticsPlugin $a_plugin
	 */
	public function __construct($a_plugin)
	{
		$this->plugin = $a_plugin;
		$this->plugin->includeClass('models/class.ilExteStatParam.php');
		$this->plugin->includeClass('models/class.ilExteStatValue.php');
		$this->plugin->includeClass('models/class.ilExteStatColumn.php');
		$this->plugin->includeClass('models/class.ilExteStatDetails.php');

		$this->initParams();
	}

	protected function initParams()
	{
		// get all parameter data for the evaluation
		$data = $this->plugin->getConfig()->getEvaluationParameters(get_class($this));

		// initialize the parameters of the evaluation
		foreach ($this->getAvailableParams() as $param)
		{
			// add the parameter
			$this->params[$param->name] = $param;

			//set the stored data if it exists
			if (isset($data[$param->name]))
			{
				switch ($param->type)
				{
					case ilExteStatParam::TYPE_INT:
						$param->value = (int) $data[$param->name];
						break;
					case ilExteStatParam::TYPE_FLOAT:
						$param->value = (float) $data[$param->name];
						break;
					case ilExteStatParam::TYPE_BOOLEAN:
						$param->value = (bool) $data[$param->name];
						break;
				}
			}
		}
	}


	/**
	 * Set the source data
	 * This should be done before the evaluation is used on the PageGUI
	 * It can be ignored when the evaluation is called from the ConfigGUI
	 *
	 * @param ilExteStatSourceData $a_data
	 */
	public function setData($a_data)
	{
		$this->data = $a_data;
	}


	/**
	 * Get the prefix for language variables of the evaluation
	 * This prefix is used additionally to the prefix of the plugin
	 *
	 * @return string	prefix
	 */
	public function getLangPrefix()
	{
		return isset($this->lang_prefix) ? $this->lang_prefix : strtolower(get_called_class());
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
	 * Get a list of available parameters
	 *	@return ilExteStatParam[]
	 */
	public function getAvailableParams()
	{
		return array();
	}

	/**
	 * Get the initialized params
	 * @return ilExteStatParam[]		$name => ilExteStatParam
	 */
	public function getParams()
	{
		return $this->params;
	}


	/**
	 * Get the value of a single parameter
	 * @param $a_name
	 * @return mixed
	 */
	public function getParam($a_name)
	{
		return $this->params[$a_name]->value;
	}

	/**
	 * @return bool
	 */
	public function isTestTypeAllowed()
	{
		return empty($this->allowed_test_types) || in_array($this->data->getTestType(), $this->allowed_test_types);
	}

	/**
	 * @return bool
	 */
	final public function isQuestionTypeAllowed($a_type)
	{
		return empty($this->allowed_question_types) || in_array($a_type, $this->allowed_question_types);
	}

	/**
	 * @return bool	evaluation provides a single value
	 */
	public function providesValue()
	{
		return $this->provides_value;
	}

	/**
	 * @return bool	evaluation provides an array of details
	 */
	public function providesDetails()
	{
		return $this->provides_details;
	}

	/**
	 * Get a localized text
	 * The language variable will be prefixed by self::_getLangPrefix()
	 *
	 * @param string $a_langvar language variable
	 * @return string
	 */
	public function txt($a_langvar)
	{
		return $this->plugin->txt($this->getLangPrefix() . '_' . $a_langvar);
	}


	/**
	 * Get a message saying that the evaluation is not available for the test type
	 * @return	string
	 */
	public function getMessageNotAvailableForTestType()
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
	public function getMessageNotAvailableForQuestionType()
	{
		return $this->plugin->txt('not_for_question_type');
	}

}
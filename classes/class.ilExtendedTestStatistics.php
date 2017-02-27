<?php

/**
 * Basic class for doing statistics
 *
 * @author Fred Neumann <fred.neumann@fau.de>
 * @version $Id$
 *
 */
class ilExtendedTestStatistics
{
	const LEVEL_TEST = 'test';
	const LEVEL_QUESTION = 'question';

	const PROVIDES_VALUE = 'value';
	const PROVIDES_DETAILS = 'details';

	/**
	 * @var ilExtendedTestStatisticsPlugin
	 */
	protected $plugin;

	/**
	 * @var	ilExtendedTestStatisticsConfig;
	 */
	protected $config;

	/*
	 * @var ilObjTest
	 */
	protected $object;

	/**
	 * @var ilExteStatSourceData
	 */
	protected $data;

	/**
	 * @var ilExteEvalBase[]	indexed by class name
	 */
	protected $evaluations = array();


	/**
	 * ilExtendedTestStatistics constructor.
	 *
	 * @param ilObjTest $a_test_obj
	 * @param ilExtendedTestStatisticsPlugin $a_plugin
	 */
	public function __construct($a_test_obj, $a_plugin)
	{
		$this->plugin = $a_plugin;
		$this->object = $a_test_obj;

		//Set config object
		$this->plugin->includeClass("class.ilExtendedTestStatisticsConfig.php");
		$this->config = new ilExtendedTestStatisticsConfig($this->plugin);
	}

	/**
	 * Load the source data for all evaluations
	 */
	public function loadSourceData()
	{
        // workaround for missing include in ilObjtest::getQuestionCount()
        if ($this->object->isRandomTest())
        {
            require_once('Modules/Test/classes/class.ilTestRandomQuestionSetConfig.php');
        }
		$this->plugin->includeClass('models/class.ilExteStatSourceData.php');
		$this->data = new ilExteStatSourceData($this->object, $this->plugin);
		$this->data->load();
	}

	/**
	 * Get the source data object
	 * @return ilExteStatSourceData
	 */
	public function getSourceData()
	{
		if (!isset($this->data))
		{
			$this->loadSourceData();
		}

		return $this->data;
	}

	/**
	 * Load the relevant evaluation objects
	 * @param   string    	$a_level	level of the statistics, e.g. self::LEVEL_TEST
	 * @param   string  	$a_class	class name of the evaluation to load
	 */
	public function loadEvaluations($a_level = '', $a_class = '')
	{
		$this->evaluations = array();

		$isAdmin = $this->isAdmin();

		foreach ($this->config->getEvaluationClasses() as $class => $availability)
		{
			// check evaluation id
			if (!empty($a_class) && $a_class != $class)
			{
				continue;
			}

			// check configured availability
			if (!($isAdmin || $availability == ilExtendedTestStatisticsConfig::FOR_USER))
			{
				continue;
			}

			// check evaluation type
			if (($a_level == self::LEVEL_TEST && !is_subclass_of($class, 'ilExteEvalTest')) ||
				($a_level == self::LEVEL_QUESTION && !is_subclass_of($class, 'ilExteEvalQuestion')))
			{
				continue;
			}

			// instantiate the evaluation object
			$this->evaluations[$class] = new $class($this->data, $this->plugin);
		}
	}

	/**
	 * Get a single loaded evaluation
	 * @param string $a_class  class name of the evaluation
	 * @return ilExteEvalTest|ilExteEvalQuestion|null
	 */
	public function getEvaluation($a_class)
	{
		return isset($this->evaluations[$a_class]) ? $this->evaluations[$a_class] : null;
	}

	/**
	 * Get a subset of the loaded evaluation objects
	 * @param   string  $a_provides			provided result, e.g. self::PROVIDES_VALUE or empty for all
	 * @param   string  $a_question_type	question type (for question evaluations) or empty for all
	 * @return  ilExteEvalBase[]    indexed by class name
	 */
	public function getEvaluations($a_provides = '', $a_question_type = '')
	{
		$selected = array();
		foreach ($this->evaluations as $class => $evaluation)
		{
			// check if value or details are provided
			if (($a_provides == self::PROVIDES_VALUE && !$evaluation->providesValue()) ||
				($a_provides == self::PROVIDES_DETAILS && !$evaluation->providesDetails()))
			{
				continue;
			}

			// check if question type is supported
			if (!empty($a_question_type) && !$evaluation->isQuestionTypeAllowed($a_question_type))
			{
				continue;
			}

			$selected[$class] = $evaluation;
		}
		return $selected;
	}

	/**
	 * Check if the current user is administrator of the test system
	 * @return bool
	 */
	private function isAdmin()
	{
		global $tree, $rbacsystem;

		foreach ($tree->getChilds(SYSTEM_FOLDER_ID) as $object)
		{
			if ($object["type"] == "assf")
			{
				return $rbacsystem->checkAccess("visible", $object["ref_id"]);
			}
		}
		return false;
	}
}

?>
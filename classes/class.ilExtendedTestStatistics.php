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
	 * @var ilExteEvalBase[]
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
        // workaround for missing incmude in ilObjtest::getQuestionCount()
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
	 * @param   string    $a_level	level of the statistics, e.g. self::LEVEL_TEST
	 * @param   string  	$a_id	id of the evaluation to load
	 */
	public function loadEvaluations($a_level = '', $a_id = '')
	{
		$this->evaluations = array();

		$isAdmin = $this->isAdmin();

		/** @var ilExteEvalBase $class (not the class, but just its name) */
		foreach ($this->config->getEvaluationClasses() as $class => $availability)
		{
			$fits = true;

			// check configured availability
			$fits = $fits && ($isAdmin || $availability == ilExtendedTestStatisticsConfig::FOR_USER);

			// check evaluation type
			switch ($a_level)
			{
				case self::LEVEL_TEST:
					$fits = $fits && $class::_isTestEvaluation();
					break;
				case self::LEVEL_QUESTION:
					$fits = $fits && $class::_isQuestionEvaluation();
					break;
			}

			// check test type
			if ($this->object->isFixedTest())
			{
				$fits = $fits && $class::_isTestTypeAllowed(ilExteEvalBase::TEST_TYPE_FIXED);
			}
			elseif ($this->object->isRandomTest())
			{
				$fits = $fits && $class::_isTestTypeAllowed(ilExteEvalBase::TEST_TYPE_RANDOM);
			}
			elseif ($this->object->isDynamicTest())
			{
				$fits = $fits && $class::_isTestTypeAllowed(ilExteEvalBase::TEST_TYPE_DYNAMIC);
			}

			// check evaluation id
			if (!empty($a_id))
			{
				$fits = $fits && ($class::_getId() == $a_id);
			}

			// instantiate the evaluation object
			if ($fits)
			{
				$this->evaluations[$class::_getId()] = new $class($this->data, $this->plugin);
			}
		}
	}

	/**
	 * Get a single loaded evaluation
	 * @param string $a_id the evaluation id
	 * @return ilExteEvalTest|ilExteEvalQuestion|null
	 */
	public function getEvaluation($a_id)
	{
		return isset($this->evaluations[$a_id]) ? $this->evaluations[$a_id] : null;
	}

	/**
	 * Get a subset of the loaded evaluations
	 * @param   string  $a_provides			provided result, e.g. self::PROVIDES_VALUE or empty for all
	 * @param   string  $a_question_type	question type (for question evaluations) or empty for all
	 * @return  ilExteEvalBase[]    		indexed by evaluation id
	 */
	public function getEvaluations($a_provides = '', $a_question_type = '')
	{
		$selected = array();

		foreach ($this->evaluations as $evaluation)
		{
			$fits = true;

			switch ($a_provides)
			{
				case self::PROVIDES_VALUE:
					$fits = $fits && $evaluation::_providesValue();
					break;
				case self::PROVIDES_DETAILS:
					$fits = $fits && $evaluation::_providesDetails();
					break;
			}

			if (!empty($a_question_type))
			{
				$fits = $fits && $evaluation::_isQuestionTypeAllowed($a_question_type);
			}

			if ($fits)
			{
				$selected[$evaluation::_getId()] = $evaluation;
			}
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
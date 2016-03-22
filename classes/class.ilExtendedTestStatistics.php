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
	const PURPOSE_TEST_VALUES = 'test_values';
	const PURPOSE_TEST_DETAILS = 'test_details';
	const PURPOSE_QUESTION_VALUES = 'questions_values';
	const PURPOSE_QUESTION_DETAILS = 'questions_values';


	/**
	 * @var ilExtendedTestStatisticsPlugin
	 */
	protected $plugin;

	/*
	 * @var ilObjTest
	 */
	protected $object;

	/**
	 * @var string	purpose of the statistics
	 */
	protected $purpose;

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
	 * @param ilObjTest	$a_test_obj
	 * @param ilExtendedTestStatisticsPlugin $a_plugin
	 */
	public function __construct($a_test_obj, $a_plugin)
	{
		$this->plugin = $a_plugin;
		$this->object = $a_test_obj;
	}

	/**
	 * Set the purpose of this statistics
	 * This determines the evaluations loaded and the structure of provided data
	 *
	 * @param string $a_purpose
	 */
	public function setPurpose($a_purpose)
	{
		$this->purpose = $a_purpose;
	}

	/**
	 * Load the source data for all evaluations
	 */
	public function loadSourceData()
	{
		$this->plugin->includeClass('models/class.ilExteStatSourceData.php');

		$this->data = new ilExteStatSourceData($this->object, $this->plugin);
		$this->data->load();
	}


	/**
	 * Load the relevant evaluation objects
	 */
	public function loadEvaluations($a_purpose)
	{
		$this->plugin->includeClass('abstract/class.ilExteEvalBase.php');
		$this->plugin->includeClass('abstract/class.ilExteEvalQuestion.php');
		$this->plugin->includeClass('abstract/class.ilExteEvalTest.php');

		$eval_dir = $this->plugin->getDirectory().'/classes/evaluations';

		//@todo: scan this directory, check with static methods if evaliation
		//@todo	instanciate a fitting evaluation and add it to the list of evaluations

		// example
		$this->plugin->includeClass('evaluations/class.ilExteEvalTestExample.php');
		$this->evaluations[] = new ilExteEvalTestExample($this->data, $this->plugin);
	}


	public function getTestOverviewTableData()
	{
		global $lng;

		$rows = array();

		/** @var ilExteStatValue  $value */
		foreach ($this->data->getBasicTestValues() as $value_id => $value)
		{
			array_push($rows,
				array(
					'title' => $lng->txt($value_id),
					'value' => $value
			));
		}

		foreach ($this->evaluations as $evaluation)
		{
			$value =  $evaluation->calculateValue();
			array_push($rows,
				array(
					'title' => $evaluation->getTitle(),
					'value' => $value
				));
		}

		return $rows;
	}
}

?>

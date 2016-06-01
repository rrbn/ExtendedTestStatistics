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
	 * @param ilObjTest	$a_test_obj
	 * @param ilExtendedTestStatisticsPlugin $a_plugin
	 */
	public function __construct($a_test_obj, $a_plugin)
	{
		$this->plugin = $a_plugin;
		$this->object = $a_test_obj;
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
	 * @param   string	level of the statistics, e.g. self::LEVEL_TEST
     * @param   string  id of the evaluation to load
     * @param   string
	 */
	public function loadEvaluations($a_level = '', $a_id = '')
	{
		$this->evaluations = array();

		$classnames = $this->getEvaluationClasses();
        /** @var ilExteEvalBase $class  (not the class, but just its name) */
        foreach ($classnames as $class)
		{
			$fits = true;

			switch ($a_level)
            {
                case self::LEVEL_TEST:
                    $fits = $fits && $class::_isTestEvaluation();
                    break;
                case self::LEVEL_QUESTION:
                    $fits = $fits && $class::_isQuestionEvaluation();
                    break;
            }

			if ($this->object->isFixedTest())
			{
                $fits = $fits && $class::_isTestTypeAllowed(ilExteEvalBase::TEST_TYPE_FIXED);
			}
			elseif ($this->object->isRandomTest())
			{
                $fits = $fits && $class::_isTestTypeAllowed(ilExteEvalBase::TEST_TYPE_RANDOM);
			}
			elseif($this->object->isDynamicTest())
			{
                $fits = $fits && $class::_isTestTypeAllowed(ilExteEvalBase::TEST_TYPE_DYNAMIC);
			}

            if (!empty($a_id))
            {
                $fits = $fits && ($class::_getId() == $a_id);
            }

			if ($fits)
			{
				$this->evaluations[$class::_getId()] = new $class($this->data, $this->plugin);
			}
		}
	}

    /**
     * Get a single loaded evaluation
     * @param string $a_id  the evaluation id
     * @return ilExteEvalTest|ilExteEvalQuestion|null
     */
    public function getEvaluation($a_id)
    {
        return isset($this->evaluations[$a_id]) ? $this->evaluations[$a_id] : null;
    }

    /**
     * Get a subset of the loaded evaluations
     * @param   string  provided result, e.g. self::PROVIDES_VALUE
     * @param   string  question type (for question evaluations)
     * @return  ilExteEvalBase[]    indexed by evaluation id
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
	 * Includea the vailable evaluation classes and return their names
	 * @return array    list of included class namens
	 */
	protected function getEvaluationClasses()
	{
		// @todo: the files should be scanned in the plugin configuration
		// there it should be set whether an evaluation is available to admins or users
		// this information is stored in a database table
		// the current function just reads the info from the table

		$this->plugin->includeClass('abstract/class.ilExteEvalBase.php');
		$this->plugin->includeClass('abstract/class.ilExteEvalQuestion.php');
		$this->plugin->includeClass('abstract/class.ilExteEvalTest.php');

		$classnames = array();
		$classfiles = glob($this->plugin->getDirectory().'/classes/evaluations/class.*.php');
		if (!empty($classfiles))
		{
			foreach ($classfiles as $file)
			{
				require_once($file);
				$parts = explode('.',basename($file));
				$classnames[] = $parts[1];
			}
		}
		return $classnames;
	}
}

?>
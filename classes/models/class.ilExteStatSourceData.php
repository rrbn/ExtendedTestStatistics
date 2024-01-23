<?php
// Copyright (c) 2017 Institut fuer Lern-Innovation, Friedrich-Alexander-Universitaet Erlangen-Nuernberg, GPLv3, see LICENSE

/**
 * Source data for test and question evaluations
 * A call of load() must be done before any get...() function is used
 *
 */
class ilExteStatSourceData
{
	const PASS_LAST = 'last';
	const PASS_BEST = 'best';
	const PASS_FIRST = 'first';
	const PASS_SCORED = 'scored';

	/**
	 * @var ilExtendedTestStatisticsPlugin
	 */
	protected $plugin;

	/**
	 * @var ilObjTest $object
	 */
	protected $object;

	/**
	 * @var ilExtendedTestStatisticsCache $cache
	 */
	protected $cache;

	/**
	 * @var ilTestEvaluationData
	 */
	protected $eval;

	/**
     * Pass that should be selected for each participant to get the question and answer data
     * Only questions and answers of these passes (one per participant) will be loaded
     *
	 * @var string selection of pass to evaluate
	 */
	protected $pass_selection = self::PASS_SCORED;

	/**
     * List of question type titles
	 * @var array   class => title
	 */
	protected $question_types = array();


	/**
     * List of question data
	 * @var ilExteStatSourceQuestion[]    	$questions 		(indexed by question_id)
	 */
	protected $questions = array();

	/**
     * List of participant data
	 * @var ilExteStatSourceParticipant[]	$participants	(indexed by active_id)
	 */
	protected $participants = array();

	/**
     * List of answer data
	 * @var ilExteStatSourceAnswer[]    	$answers		(flat list)
	 */
	protected $answers = array();

	/**
     * List of answer data
     * @todo (ilias8) remove unnecessary pass step (only one pass is saved per participant)
	 * @var array    question_id => active_id => pass => ilExteStatSourceAnswer
	 */
	protected $answers_by_question_id = array();

	/**
     * List of answer data
     * @todo (ilias8) remove unnecessary pass step (only one pass is saved per participant)
     * @var array    active_id => pass => question_id => ilExteStatSourceAnswer
	 */
	protected $answers_by_active_id = array();

	/**
     * List of basic test values
	 * @var array    value_id => ilExteStatValue
	 */
	protected $basic_test_values = array();

	/**
     * List of basic question values
	 * @var array    question_id => value_id => ilExteStatValue
	 */
	protected $basic_question_values = array();


	/**
	 * ilExteStatSourceData constructor.
	 * @param ilObjTest $a_test_obj
	 * @param ilExtendedTestStatisticsPlugin $a_plugin
	 * @param ilExtendedTestStatisticsCache $a_cache
	 */
	public function __construct($a_test_obj, $a_plugin, $a_cache)
	{
		$this->object = $a_test_obj;
		$this->plugin = $a_plugin;
		$this->cache = $a_cache;

		$this->plugin->includeClass('models/class.ilExteStatSourceParticipant.php');
		$this->plugin->includeClass('models/class.ilExteStatSourceQuestion.php');
		$this->plugin->includeClass('models/class.ilExteStatSourceAnswer.php');
		$this->plugin->includeClass('models/class.ilExteStatValue.php');
		$this->plugin->includeClass('abstract/class.ilExteEvalBase.php');
	}

	/**
	 * Get the test title
	 * @return string
	 */
	public function getTestTitle()
	{
		return $this->object->getTitle();
	}

	/**
	 * Get the test type (fixed, random, dynamic)
	 * @return string
	 */
	public function getTestType()
	{
		if ($this->object->isFixedTest())
		{
			return ilExteEvalBase::TEST_TYPE_FIXED;
		}
		elseif ($this->object->isRandomTest())
		{
			return ilExteEvalBase::TEST_TYPE_RANDOM;
		}
		elseif ($this->object->isDynamicTest())
		{
			return ilExteEvalBase::TEST_TYPE_DYNAMIC;
		}
		else
		{
			return ilExteEvalBase::TEST_TYPE_UNKNOWN;
		}
	}

	/**
	 * Get an assoc list of the question types
	 * @return	array	class => type name
	 */
	public function getQuestionTypes()
	{
		return $this->question_types;
	}

	/**
	 * Get the selection of the evaluated pass
	 * @return string
	 */
	public function getPassSelection()
	{
		return $this->pass_selection;
	}


	/**
	 * Load the source data
	 *
	 * @param    string    $a_pass_selection	pass selection, e.g. self::PASS_SCORED
	 * @see ilObjTest::getUnfilteredEvaluationData()
	 */
	public function load($a_pass_selection = self::PASS_SCORED)
	{
		$this->pass_selection = $a_pass_selection;
		$this->cache->setPassSelection($a_pass_selection);

		if (!$this->readFromCache())
		{
			$this->readFromTest();
			$this->writeToCache();
		}
	}

	/**
	 * Reset all source data
	 */
	protected function reset()
	{
		$this->question_types = array();
		$this->questions = array();
		$this->answers = array();
		$this->participants = array();
		$this->answers_by_question_id = array();
		$this->answers_by_active_id = array();
		$this->basic_test_values = array();
		$this->basic_question_values = array();
	}

	/**
	 * Read the data from the cache
	 * @return bool	cache could be read
	 */
	protected function readFromCache()
	{
		$this->reset();
		try
		{
			$this->question_types = unserialize($this->cache->read(__CLASS__, 'question_types'));
			$this->questions = unserialize($this->cache->read(__CLASS__, 'questions'));
			$this->participants = unserialize($this->cache->read(__CLASS__, 'participants'));
			$this->answers = unserialize($this->cache->read(__CLASS__, 'answers'));
			$this->basic_test_values = unserialize($this->cache->read(__CLASS__, 'basic_test_values'));
			$this->basic_question_values = unserialize($this->cache->read(__CLASS__, 'basic_question_values'));
		}
		catch (Exception $e)
		{
			$this->reset();
			return false;
		}

		if ($this->question_types === false || $this->questions === false || $this->participants === false || $this->answers === false)
		{
			$this->reset();
			return false;
		}

		foreach ($this->answers as $answer)
		{
			$this->answers_by_question_id[$answer->question_id][$answer->active_id][$answer->pass] = $answer;
			$this->answers_by_active_id[$answer->active_id][$answer->pass][$answer->question_id] = $answer;
		}

		return false;
	}


	/**
	 * Write the data to the cache
	 */
	protected function writeToCache()
	{
		$this->cache->write(__CLASS__, 'question_types', serialize($this->question_types));
		$this->cache->write(__CLASS__, 'questions', serialize($this->questions));
		$this->cache->write(__CLASS__, 'participants', serialize($this->participants));
		$this->cache->write(__CLASS__, 'answers', serialize($this->answers));
		$this->cache->write(__CLASS__, 'basic_test_values', serialize($this->basic_test_values));
		$this->cache->write(__CLASS__, 'basic_question_values', serialize($this->basic_question_values));
	}


	/**
	 * Read the source data from the test
	 *
	 * @see ilObjTest::getUnfilteredEvaluationData()
	 */
	protected function readFromTest()
	{
		$this->eval = $this->object->getUnfilteredEvaluationData();

		// get the order and obligatory data of questions in a fixed test
        // other question data will be added later
		if ($this->object->isFixedTest())
		{
			$this->readFixedTestQuestionData();
		}

		// get the question titles
		$question_titles = $this->eval->getQuestionTitles();

		/** @var ilTestEvaluationUserData[] $participants */
		$participants = $this->eval->getParticipants();
		foreach ($participants as $active_id => $userdata)
		{
			$participant = $this->getParticipant($active_id, true);
			$participant->best_pass = $userdata->getBestPass();
			$participant->last_pass = $userdata->getLastPass();
			$participant->first_pass = 0;
			$participant->scored_pass = $userdata->getScoredPass();

			switch ($this->pass_selection)
			{
				case self::PASS_LAST:
					$pass = $userdata->getLastPassObject();
					break;
				case self::PASS_BEST:
					$pass = $userdata->getBestPassObject();
					break;
				case self::PASS_FIRST:
					$pass = $userdata->passes[0];
					break;
				case self::PASS_SCORED:
				default:
					$pass = $userdata->getScoredPassObject();
					break;
			}
			if ($pass instanceof ilTestEvaluationPassData)
			{
				// all questions for a participant in the test pass
                // in case of a random test, this fills the list of questions with all questions
                //      drawn for the participants in the chosen pass
				$pass_questions = $userdata->getQuestions($pass->getPass());

				if (is_array($pass_questions))
				{
					foreach ($pass_questions as $pass_question)
					{
						$question = $this->getQuestion($pass_question['id'], true);
						$question->original_id = $pass_question['o_id'];
						$question->maximum_points = $pass_question['points'];
						$question->question_title = $question_titles[$pass_question['id']];

                        // initiate the questions for this pass (even if not answered)
						$answer = $this->getAnswer($pass_question['id'], $active_id, $pass->getPass(), true);
						$answer->sequence = $pass_question['sequence'];
					}
				}

				// questions answered by a participant in the test pass
				$current_reached_points = 0.0;
				$pass_answers = $pass->getAnsweredQuestions();
				{
					if (is_array($pass_answers))
					{
						foreach ($pass_answers as $pass_answer)
						{
							$answer = $this->getAnswer($pass_answer['id'], $active_id, $pass->getPass(), true);
							$answer->reached_points = $pass_answer['reached'];
							$answer->answered = (bool)$pass_answer['isAnswered'];
							$answer->manual_scored = (bool)$pass_answer['manual'];

							$current_reached_points += (float) $pass_answer['reached'];
						}
					}
				}

				// sum of reached points for the selected pass
				$participant->current_reached_points = $current_reached_points;
			}
		}

		$this->readQuestionTypes();
		$this->calculateBasicTestValues();
		$this->calculateBasicQuestionValues();
	}

	/**
	 * Read the types of the relevant questions
	 */
	protected function readQuestionTypes()
	{
		global $ilDB;

		require_once('Modules/TestQuestionPool/classes/class.ilObjQuestionPool.php');
		$type_translations = ilObjQuestionPool::getQuestionTypeTranslations();
		$this->question_types = array();

		if (!empty($this->questions))
        {
			$query = "
                SELECT q.question_id, t.type_tag FROM qpl_questions q
                INNER JOIN qpl_qst_type t ON t.question_type_id = q.question_type_fi
                WHERE " . $ilDB->in('q.question_id', array_keys($this->questions), false, 'integer');

            $result = $ilDB->query($query);
            while ($row = $ilDB->fetchAssoc($result))
            {
				$this->question_types[$row['type_tag']] = $type_translations[$row['type_tag']];

                $this->questions[$row['question_id']]->question_type = $row['type_tag'];
                $this->questions[$row['question_id']]->question_type_label = $type_translations[$row['type_tag']];
            }
		}
	}

	/**
	 * Read the ordering data of questions in a fixed test
	 */
	protected function readFixedTestQuestionData()
	{
		global $ilDB;

		$query = "SELECT question_fi, sequence, obligatory FROM tst_test_question WHERE test_fi = "
			. $ilDB->quote($this->object->getTestId())
			. " ORDER BY sequence";
		$result = $ilDB->query($query);

		while ($row = $ilDB->fetchAssoc($result))
		{
			$question = $this->getQuestion($row['question_fi'], true); // reference!
			$question->order_position = $row['sequence'];
			$question->obligatory = (bool) $row['obligatory'];
		}
	}


	/**
	 * Calculate the basic values for a test (as in original ILIAS)
	 * @see ilTestEvaluationGUI::eval_a()
	 */
	protected function calculateBasicTestValues()
	{
		// Total number of people who started the test
		$value = new ilExteStatValue();
		$value->type = ilExteStatValue::TYPE_NUMBER;
		$value->precision = 0;
		$value->value = count($this->participants);
		$this->basic_test_values['tst_eval_total_persons'] = $value;

		// Total finished tests (Users that used up all possible passes.)
		$value = new ilExteStatValue();
		$value->type = ilExteStatValue::TYPE_NUMBER;
		$value->precision = 0;
		$value->value = $this->eval->getTotalFinishedParticipants();
		$this->basic_test_values['tst_eval_total_finished'] = $value;

		// Average test processing time
		$value = new ilExteStatValue();
		$value->type = ilExteStatValue::TYPE_DURATION;
		$value->value = (int) $this->object->evalTotalStartedAverageTime();
		$this->basic_test_values['tst_eval_total_finished_average_time'] = $value;

		// (intermediate calculations)
		$total_passed = 0;
		$total_passed_reached = 0;
		$total_passed_max = 0;
		$total_passed_time = 0;

		/** @var ilTestEvaluationUserData[] $foundParticipants */
		$foundParticipants = $this->eval->getParticipants();
		foreach ($foundParticipants as $userdata)
		{
			if ($userdata->getPassed())
			{
				$total_passed++;
				$total_passed_reached += $userdata->getReached();
				$total_passed_max += $userdata->getMaxpoints();
				$total_passed_time += $userdata->getTimeOfWork();
			}
		}
		$average_passed_reached = $total_passed ? $total_passed_reached / $total_passed : 0;
		$average_passed_max = $total_passed ? $total_passed_max / $total_passed : 0;
		$average_passed_time = $total_passed ? $total_passed_time / $total_passed : 0;

		// Total passed tests
		$value = new ilExteStatValue();
		$value->type = ilExteStatValue::TYPE_NUMBER;
		$value->precision = 0;
		$value->value = $total_passed;
		$this->basic_test_values['tst_eval_total_passed'] = $value;

		// Average maximum points of passed tests
		$value = new ilExteStatValue();
		$value->type = ilExteStatValue::TYPE_NUMBER;
		$value->precision = 2;
		$value->value = $average_passed_max;
		$this->basic_test_values['tst_eval_total_passed_average_max_points'] = $value;

		// Average points of passed tests
		$value = new ilExteStatValue();
		$value->type = ilExteStatValue::TYPE_NUMBER;
		$value->precision = 2;
		$value->value = $average_passed_reached;
		$this->basic_test_values['tst_eval_total_passed_average_points'] = $value;

		// Average processing time of all passed tests
		$value = new ilExteStatValue();
		$value->type = ilExteStatValue::TYPE_DURATION;
		$value->value = (int) $average_passed_time;
		$this->basic_test_values['tst_eval_total_passed_average_time'] = $value;

		// Mean of reached points
		$sum_of_points = 0.0;
		foreach ($this->participants as $participant)
		{
			$sum_of_points += $participant->current_reached_points;
		}
		$value = new ilExteStatValue();
		$value->type = ilExteStatValue::TYPE_NUMBER;
		$value->precision = 2;
		if (count($this->participants) > 0)
		{
			$value->value = $sum_of_points / count($this->participants);
		}
		else
		{
			$value->value = null;
			$value->alert = ilExteStatValue::ALERT_UNKNOWN;
		}
		$this->basic_test_values['tst_eval_mean_of_reached_points'] = $value;
	}


	/**
	 * Get a list of id, title and description of the basic question values
	 * The list includes only values that are intended to be displayed
	 * getBasicTestValues() may provide more values
	 * @return array	[['id' => string, 'title' => string, 'description' => string], ...]
	 */
	public function getBasicTestValuesList()
	{
		global $lng;

		$list = array(
		);

		if ($this->pass_selection == self::PASS_SCORED)
		{
			$list = array_merge($list, array(
				array(
					'id' => 'tst_eval_total_persons',
					'title' => $lng->txt('tst_eval_total_persons'),
					'description' => '',
				),
				array(
					'id' => 'tst_eval_total_finished',
					'title' => $lng->txt('tst_eval_total_finished'),
					'description' => '',
				),
				array(
					'id' => 'tst_eval_total_passed',
					'title' => $lng->txt('tst_eval_total_passed'),
					'description' => '',
				),
				array(
					'id' => 'tst_eval_total_passed_average_max_points',
					'title' => $this->plugin->txt('tst_passed_average_max_points'),
					'description' => '',
				),
				array(
					'id' => 'tst_eval_total_passed_average_points',
					'title' => $lng->txt('tst_eval_total_passed_average_points'),
					'description' => '',
				),
				array(
					'id' => 'tst_eval_total_finished_average_time',
					'title' => $lng->txt('tst_eval_total_finished_average_time'),
					'description' => '',
				),
				array(
					'id' => 'tst_eval_total_passed_average_time',
					'title' => $lng->txt('tst_eval_total_passed_average_time'),
					'description' => '',
				)
			));
		}

		return $list;
	}

	/**
	 * Calculate the basic values for test questions (as in original ILIAS)
	 * @see ilTestEvaluationGUI::eval_a()
	 */
	protected function calculateBasicQuestionValues()
	{
		foreach ($this->getAllQuestions() as $question_id => $question)
		{
			$assigned = 0;
			$answered = 0;
			$reached = 0;

			foreach ($this->getAnswersForQuestion($question_id) as $answer)
			{
				$assigned++;
				$answered += $answer->answered ? 1 : 0;
				$reached += $answer->reached_points;
			}

			$question->assigned_count = $assigned;
			$question->answers_count = $answered;
			$question->average_points = ($assigned ? $reached / $assigned : 0);
			$question->average_percentage = ($question->maximum_points ? 100 * $question->average_points / $question->maximum_points : 0);

			$values = array();
			$values['question_id'] = ilExteStatValue::_create($question->question_id, ilExteStatValue::TYPE_NUMBER, 0);
			$values['question_title'] = ilExteStatValue::_create($question->question_title, ilExteStatValue::TYPE_TEXT);
			$values['question_type'] = ilExteStatValue::_create($question->question_type, ilExteStatValue::TYPE_TEXT);
			$values['question_type_label'] = ilExteStatValue::_create($question->question_type_label, ilExteStatValue::TYPE_TEXT);
			$values['order_position'] = ilExteStatValue::_create($question->order_position, ilExteStatValue::TYPE_NUMBER, 0);
			$values['obligatory'] = ilExteStatValue::_create($question->obligatory, ilExteStatValue::TYPE_BOOLEAN);
			$values['assigned_count'] = ilExteStatValue::_create($question->assigned_count, ilExteStatValue::TYPE_NUMBER, 0);
			$values['answers_count'] = ilExteStatValue::_create($question->answers_count, ilExteStatValue::TYPE_NUMBER, 0);
			$values['maximum_points'] = ilExteStatValue::_create($question->maximum_points, ilExteStatValue::TYPE_NUMBER, 2);
			$values['average_points'] = ilExteStatValue::_create($question->average_points, ilExteStatValue::TYPE_NUMBER, 2);
			$values['average_percentage'] = ilExteStatValue::_create($question->average_percentage, ilExteStatValue::TYPE_PERCENTAGE, 2);

			$this->basic_question_values[$question->question_id] = $values;
		}
	}

	/**
	 * Get a list of id, title and description of the basic question values
	 * The list includes only values that are intended to be displayed
	 * getBasicQuestionValues() may provide more values
	 * @return array	[['id' => string, 'title' => string, 'description' => string], ...]
	 */
	public function getBasicQuestionValuesList()
	{
		global $lng;

		return array(
			'order_position' => array(
				'title' => $lng->txt('position'),
				'description' => '',
				'test_types' => array(ilExteEvalBase::TEST_TYPE_FIXED)
			),
			'question_id' => array(
				'title' => $lng->txt('question_id'),
				'description' => '',
				'test_types' => array()
			),
			'question_title' => array(
				'title' => $lng->txt('question_title'),
				'description' => '',
				'test_types' => array()
			),
			'question_type_label' => array(
				'title' => $this->plugin->txt('question_type'),
				'description' => '',
				'test_types' => array()
			),
			'obligatory' => array(
				'title' => $lng->txt('obligatory'),
				'description' => '',
				'test_types' => array(ilExteEvalBase::TEST_TYPE_FIXED)
			),
			'assigned_count' => array(
				'title' => $this->plugin->txt('assigned_count'),
				'description' => $this->plugin->txt('assigned_count_description'),
				'test_types' => array()
			),
			'answers_count' => array(
				'title' => $this->plugin->txt('answers_count'),
				'description' => $this->plugin->txt('answers_count_description'),
				'test_types' => array()
			),
			'maximum_points' => array(
				'title' => $this->plugin->txt('max_points'),
				'description' => $this->plugin->txt('max_points_description'),
				'test_types' => array()
			),
			'average_points' => array(
				'title' => $this->plugin->txt('average_points'),
				'description' => $this->plugin->txt('average_points_description'),
				'test_types' => array()
			),
//			'average_percentage' => array(
//				'title' => $this->plugin->txt('average_percentage'),
//				'description' => $this->plugin->txt('average_percentage_description'),
//				'test_types' => array()
//			)
		);
	}

		/**
	 * Get the basic test values
	 * @return array    value_id => ilExteStatValue
	 */
	public function getBasicTestValues()
	{
		return $this->basic_test_values;
	}


	/**
	 * Get the basic question values
	 * @return	array question_id => value_id => ilExteStatValue
	 */
	public function getBasicQuestionValues()
	{
		return $this->basic_question_values;
	}


	/**
     * Get a participant object
     * @todo (ilias8) public getter should not create (avoid evaluation interference)
     * @param integer $a_active_id the participant id
	 * @param bool $a_create create if not exists
	 * @return ilExteStatSourceParticipant|null
     */
	public function getParticipant($a_active_id, $a_create = false)
	{
		if (isset($this->participants[$a_active_id]))
		{
			return $this->participants[$a_active_id];
		}
		elseif ($a_create)
		{
			$participant = new ilExteStatSourceParticipant;
			$participant->active_id = $a_active_id;
			$this->participants[$a_active_id] = $participant;
			return $participant;
		}
		else
		{
			return null;
		}
	}


	/**
	 * Get the question object for a question id
     * @todo (ilias8) public getter should not create (avoid evaluation interference)
     *
	 * @param integer $a_question_id the question id
	 * @param bool $a_create create if necessary
	 * @return ilExteStatSourceQuestion|null
	 */
	public function getQuestion($a_question_id, $a_create = false)
	{
		if (isset($this->questions[$a_question_id]))
		{
			return $this->questions[$a_question_id];
		}
		elseif ($a_create)
		{
			$question = new ilExteStatSourceQuestion;
			$question->question_id = $a_question_id;
			$this->questions[$a_question_id] = $question;
			return $question;
		}
		else
		{
			return null;
		}
	}


	/**
	 * Get or create the answer status of a question
     * @todo (ilias8) public getter should not create (avoid evaluation interference)
     * @todo (ilias8) remove unnecessary pass step (only one pass is saved per participant)
     *
	 * @param integer $a_question_id the question id
	 * @param integer $a_active_id the active id of the user
	 * @param integer $a_pass the pass of the answer (used for creation)
	 * @param boolean $a_create create if not exists
	 * @return ilExteStatSourceAnswer|null
	 */
	public function getAnswer($a_question_id, $a_active_id, $a_pass, $a_create = false)
	{
		if (!empty($this->answers_by_question_id[$a_question_id][$a_active_id][$a_pass]))
		{
			return $this->answers_by_question_id[$a_question_id][$a_active_id][$a_pass];
		}
		elseif ($a_create)
		{
			$answer = new ilExteStatSourceAnswer;
			$answer->question_id = $a_question_id;
			$answer->active_id = $a_active_id;
			$answer->pass = $a_pass;

			$this->answers[] = $answer;
			$this->answers_by_question_id[$a_question_id][$a_active_id][$a_pass] = $answer;
			$this->answers_by_active_id[$a_active_id][$a_pass][$a_question_id] = $answer;

			return $answer;
		}
		else
		{
			return null;
		}
	}


	/**
	 * Get all participants in the test
	 *
	 * @return ilExteStatSourceParticipant[]
	 */
	public function getAllParticipants()
	{
		return $this->participants;
	}


	/**
	 * Get all questions in the test
	 *
	 * @return ilExteStatSourceQuestion[]
	 */
	public function getAllQuestions()
	{
		return $this->questions;
	}


	/**
	 * Get all answers in the test
	 * The answer objects provide information about questions being displayed to the participants
	 * The 'answered' property indicates if an answer was saved by the participant
	 *
	 * @return ilExteStatSourceAnswer[]
	 */
	public function getAllAnswers()
	{
		return $this->answers;
	}


	/**
	 * Get all answers for a question of any participant in his/her relevant pass
	 *
	 * @param integer $a_question_id the question id
	 * @param boolean $a_only_answered get only the really answered
	 * @return ilExteStatSourceAnswer[]
	 */
	public function getAnswersForQuestion($a_question_id, $a_only_answered = false)
	{
		$answers = array();
		if (is_array($this->answers_by_question_id[$a_question_id]))
		{
			foreach ($this->answers_by_question_id[$a_question_id] as $active_id => $pass_answers)
			{
				if (is_array($pass_answers))
				{
					foreach ($pass_answers as $pass => $answer)
					{
						if (!$a_only_answered or ($a_only_answered and $answer->answered))
						{
							$answers[] = $answer;
						}
					}
				}
			}
		}
		return $answers;
	}


	/**
	 * Get all answers for a participant in his/her relevant pass
	 *
	 * @param integer $a_active_id the participant id
	 * @param boolean $a_only_answered get only the really answered
	 * @return ilExteStatSourceAnswer[] indexed by question_id
	 */
	public function getAnswersForParticipant($a_active_id, $a_only_answered = false)
	{
		$answers = array();
		if (is_array($this->answers_by_active_id[$a_active_id]))
		{
			foreach ($this->answers_by_active_id[$a_active_id] as $pass => $pass_answers)
			{
				if (is_array($pass_answers))
				{
					foreach ($pass_answers as $question_id => $answer)
					{
						if (!$a_only_answered or ($answer and $answer->answered))
						{
							$answers[$question_id] = $answer;
						}
					}
				}
			}
		}
		return $answers;
	}


	/**
	 * Get the original test evaluation data
	 *
	 * IMPORTANT NOTE:    This returns an internal object of ILIAS
	 *                    Its API is not wrapped by the plugin
	 *                    Use it in exceptional cases only!
	 *
	 * @return ilTestEvaluationData
	 */
	public function getOriginalTestEvaluationData()
	{
		return $this->eval;
	}
}
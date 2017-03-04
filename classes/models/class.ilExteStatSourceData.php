<?php

/**
 * Data model for answered question
 */
class ilExteStatSourceData
{
	const PASS_ALL = 'all';
	const PASS_BEST = 'best';
	const PASS_LAST = 'last';
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
	 * @var ilTestEvaluationData
	 */
	protected $eval;

	/**
	 * @var string selection of passe to evaluate
	 */
	protected $pass_selection = self::PASS_SCORED;

	/**
	 * @var array    question_id => ilExteStatSourceQuestion
	 */
	protected $questions = array();

	/**
	 * @var array    active_id => ilExteStatSourceParticipant
	 */
	protected $participants = array();

	/**
	 * @var array    list of ilExteStatSourceAnswer
	 */
	protected $answers = array();

	/**
	 * @var array    question_id => active_id => pass => ilExteStatSourceAnswer
	 */
	protected $answers_by_question_id = array();

	/**
	 * @var array    active_id => pass => question_id => ilExteStatSourceAnswer
	 */
	protected $answers_by_active_id = array();

	/**
	 * @var array    value_id => ilExteStatValue
	 */
	protected $basic_test_values = array();

	/**
	 * @var array    question_id => value_id => ilExteStatValue
	 */
	protected $basic_question_values = array();

	/*
	 * @var array	value_id => ilExteStatValue
	 */
	protected $cached_data = array();


	/**
	 * ilExteStatSourceData constructor.
	 * @param ilObjTest $a_test_obj
	 * @param ilExtendedTestStatisticsPlugin $a_plugin
	 */
	public function __construct($a_test_obj, $a_plugin)
	{
		$this->object = $a_test_obj;
		$this->plugin = $a_plugin;

		$this->plugin->includeClass('models/class.ilExteStatSourceParticipant.php');
		$this->plugin->includeClass('models/class.ilExteStatSourceQuestion.php');
		$this->plugin->includeClass('models/class.ilExteStatSourceAnswer.php');
		$this->plugin->includeClass('models/class.ilExteStatValue.php');
		$this->plugin->includeClass('abstract/class.ilExteEvalBase.php');
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
	 * Load the source data from the test
	 *
	 * @param    string    $a_pass_selection	pass selection, e.g. self::PASS_SCORED
	 * @see ilTestEvaluationGUI::eval_a()
	 */
	public function load($a_pass_selection = self::PASS_SCORED)
	{
		$this->pass_selection = $a_pass_selection;
		$this->eval = $this->object->getUnfilteredEvaluationData();

		$question_titles = $this->eval->getQuestionTitles();

		/** @var ilTestEvaluationUserData[] $participants */
		$participants =& $this->eval->getParticipants();
		foreach ($participants as $active_id => $userdata) {
			$participant = $this->getParticipant($active_id, true);
			$participant->best_pass = $userdata->getBestPass();
			$participant->last_pass = $userdata->getLastPass();
			$participant->scored_pass = $userdata->getScoredPass();

			switch ($this->pass_selection) {
				case self::PASS_SCORED:
					$passes = array($userdata->getScoredPassObject());
					break;
				case self::PASS_BEST:
					$passes = array($userdata->getBestPassObject());
					break;
				case self::PASS_LAST:
					$passes = array($userdata->getLastPassObject());
					break;
				case self::PASS_ALL:
					$passes = $userdata->getPasses();
					break;
				default:
					$passes = array();
			}

			foreach ($passes as $pass) {
				if ($pass instanceof ilTestEvaluationPassData) {
					// all quetions for a participant in the test pass
					$pass_questions = $userdata->getQuestions($pass->getPass());

					if (is_array($pass_questions)) {
						foreach ($pass_questions as $pass_question) {
							$question = $this->getQuestion($pass_question['id'], true);
							$question->original_id = $pass_question['o_id'];
							$question->maximum_points = $pass_question['points'];
							$question->question_title = $question_titles[$pass_question['id']];

							$answer = $this->getAnswer($pass_question['id'], $active_id, $pass->getPass(), true);
							$answer->sequence = $pass_question['sequence'];
						}
					}

					// questions answered by a participant in the test pass
					$current_reached_points = 0.0;
					$pass_answers = $pass->getAnsweredQuestions();
					{
						if (is_array($pass_answers)) {
							foreach ($pass_answers as $pass_answer) {
								$answer = $this->getAnswer($pass_answer['id'], $active_id, $pass->getPass(), true);
								$answer->reached_points = $pass_answer['reached'];
								$current_reached_points += $pass_answer['reached'];
								$answer->answered = (bool)$pass_answer['isAnswered'];
								$answer->manual_scored = (bool)$pass_answer['manual'];
							}
						}
					}
					$participant->current_reached_points = (String)$current_reached_points;
				}
			}
		}
		$this->loadQuestionTypes();
		$this->calculateBasicTestValues();
		$this->calculateBasicQuestionValues();
	}

	/**
	 * Load the types of the relevant questions
	 */
	protected function loadQuestionTypes()
	{
		global $ilDB;

		$type_translations = $this->object->getQuestionTypeTranslations();

		if (!empty($this->questions))
        {
			$query = "
                SELECT q.question_id, t.type_tag FROM qpl_questions q
                INNER JOIN qpl_qst_type t ON t.question_type_id = q.question_type_fi
                WHERE " . $ilDB->in('q.question_id', array_keys($this->questions), false, 'integer');

            $result = $ilDB->query($query);
            while ($row = $ilDB->fetchAssoc($result))
            {
                $this->questions[$row['question_id']]->question_type = $row['type_tag'];
                $this->questions[$row['question_id']]->question_type_label = $type_translations[$row['type_tag']];
            }

		}
	}

	/**
	 * Calculate the basic values for a test (as in original ILIAS)
	 * @see ilTestEvaluationGUI::eval_a()
	 */
	protected function calculateBasicTestValues()
	{
		global $lng;

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
		$value->value = $this->object->evalTotalFinished();
		$this->basic_test_values['tst_eval_total_finished'] = $value;

		// Average test processing time
		$average_time = $this->object->evalTotalStartedAverageTime();
		$diff_seconds = $average_time;
		$diff_hours = floor($diff_seconds / 3600);
		$diff_seconds -= $diff_hours * 3600;
		$diff_minutes = floor($diff_seconds / 60);
		$diff_seconds -= $diff_minutes * 60;

		$value = new ilExteStatValue();
		$value->type = ilExteStatValue::TYPE_TEXT;
		$value->value = sprintf("%02d:%02d:%02d", $diff_hours, $diff_minutes, $diff_seconds);
		$this->basic_test_values['tst_eval_total_finished_average_time'] = $value;

		// (intermediate calculations)
		$total_passed = 0;
		$total_passed_reached = 0;
		$total_passed_max = 0;
		$total_passed_time = 0;
		$foundParticipants =& $this->eval->getParticipants();
		foreach ($foundParticipants as $userdata) {
			if ($userdata->getPassed()) {
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

		// Average points of passed tests
		$value = new ilExteStatValue();
		$value->type = ilExteStatValue::TYPE_TEXT;
		$value->value = sprintf("%2.2f", $average_passed_reached) . " " . strtolower($lng->txt("of")) . " " . sprintf("%2.2f", $average_passed_max);
		$this->basic_test_values['tst_eval_total_passed_average_points'] = $value;

		// Average processing time of all passed tests
		$average_time = $average_passed_time;
		$diff_seconds = $average_time;
		$diff_hours = floor($diff_seconds / 3600);
		$diff_seconds -= $diff_hours * 3600;
		$diff_minutes = floor($diff_seconds / 60);
		$diff_seconds -= $diff_minutes * 60;

		$value = new ilExteStatValue();
		$value->type = ilExteStatValue::TYPE_TEXT;
		$value->value = sprintf("%02d:%02d:%02d", $diff_hours, $diff_minutes, $diff_seconds);
		$this->basic_test_values['tst_eval_total_passed_average_time'] = $value;
	}


	/**
	 * Calculate the basic values for test questions (as in original ILIAS)
	 * @see ilTestEvaluationGUI::eval_a()
	 */
	protected function calculateBasicQuestionValues()
	{
		foreach ($this->getAllQuestions() as $question_id => $question) {
			$assigned = 0;
			$answered = 0;
			$reached = 0;

			foreach ($this->getAnswersForQuestion($question_id) as $answer) {
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
	 * @return array	[['id' => string, 'title' => string, 'description' => string], ...]
	 */
	public function getBasicQuestionValuesList()
	{
		global $lng;

		return array(
			array(
				'id' => 'question_id',
				'title' => $lng->txt('question_id'),
				'description' => '',
			),
			array(
				'id' => 'question_title',
				'title' => $lng->txt('question_title'),
				'description' => '',
			),
			array(
				'id' => 'question_type_label',
				'title' => $this->plugin->txt('question_type'),
				'description' => '',
			),
			array(
				'id' => 'assigned_count',
				'title' => $this->plugin->txt('assigned_count'),
				'description' => $this->plugin->txt('assigned_count_description'),
			),
			array(
				'id' => 'answers_count',
				'title' => $this->plugin->txt('answers_count'),
				'description' => $this->plugin->txt('answers_count_description'),
			),
			array(
				'id' => 'maximum_points',
				'title' => $this->plugin->txt('max_points'),
				'description' => ''
			),
			array(
				'id' => 'average_points',
				'title' => $this->plugin->txt('average_points'),
				'description' => $this->plugin->txt('average_points_description'),
			),
			array(
				'id' => 'average_percentage',
				'title' => $this->plugin->txt('average_percentage'),
				'description' => $this->plugin->txt('average_percentage_description'),
			)
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
	 * @param integer $a_active_id the participant id
	 * @param bool $a_create create if not exists
	 * @return ilExteStatSourceParticipant|null
	 */
	public function getParticipant($a_active_id, $a_create = false)
	{
		if (isset($this->participants[$a_active_id])) {
			return $this->participants[$a_active_id];
		} elseif ($a_create) {
			$participant = new ilExteStatSourceParticipant;
			$participant->active_id = $a_active_id;
			$this->participants[$a_active_id] = $participant;
			return $participant;
		} else {
			return null;
		}
	}


	/**
	 * Get the source question object for a question id
	 *
	 * @param integer $a_question_id the question id
	 * @param bool $a_create create if necessary
	 * @return ilExteStatSourceQuestion|null
	 */
	public function getQuestion($a_question_id, $a_create = false)
	{
		if (isset($this->questions[$a_question_id])) {
			return $this->questions[$a_question_id];
		} elseif ($a_create) {
			$question = new ilExteStatSourceQuestion;
			$question->question_id = $a_question_id;
			$this->questions[$a_question_id] = $question;
			return $question;
		} else {
			return null;
		}
	}


	/**
	 * Get the source answer status of a question
	 *
	 * @param integer $a_question_id the question id
	 * @param integer $a_active_id the active id of the user
	 * @param integer $a_pass the pass of the answer
	 * @param boolean $a_create create if not exists
	 * @return ilExteStatSourceAnswer|null
	 */
	public function getAnswer($a_question_id, $a_active_id, $a_pass, $a_create = false)
	{
		if (!empty($this->answers_by_question_id[$a_question_id][$a_active_id][$a_pass])) {
			return $this->answers_by_question_id[$a_question_id][$a_active_id][$a_pass];
		} elseif ($a_create) {
			$answer = new ilExteStatSourceAnswer;
			$answer->question_id = $a_question_id;
			$answer->active_id = $a_active_id;
			$answer->pass = $a_pass;

			$this->answers[] = $answer;
			$this->answers_by_question_id[$a_question_id][$a_active_id][$a_pass] = $answer;
			$this->answers_by_active_id[$a_active_id][$a_pass][$a_question_id] = $answer;

			return $answer;
		} else {
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
	 * Note:    the answers don't have to be sent by a participant
	 *            check the 'answered' property of the answer object
	 *
	 * @return ilExteStatSourceAnswer[]
	 */
	public function getAllAnswers()
	{
		return $this->answers;
	}


	/**
	 * Get all answers for a question
	 *
	 * @param integer $a_question_id the question id
	 * @param boolean $a_only_answered get only the really answered
	 * @return ilExteStatSourceAnswer[]
	 */
	public function getAnswersForQuestion($a_question_id, $a_only_answered = false)
	{
		$answers = array();
		if (is_array($this->answers_by_question_id[$a_question_id])) {
			foreach ($this->answers_by_question_id[$a_question_id] as $active_id => $pass_answers) {
				if (is_array($pass_answers)) {
					foreach ($pass_answers as $pass => $answer) {
						if (!$a_only_answered or ($a_only_answered and $answer->answered)) {
							$answers[] = $answer;
						}
					}
				}
			}
		}
		return $answers;
	}


	/**
	 * Get all answers for a participant
	 *
	 * @param integer $a_active_id the participant id
	 * @param boolean $a_answered get only the really answered
	 * @return ilExteStatSourceAnswer[]
	 */
	public function getAnswersForParticipant($a_active_id, $a_answered = false)
	{
		$answers = array();
		if (is_array($this->answers_by_active_id[$a_active_id])) {
			foreach ($this->answers_by_active_id[$a_active_id] as $pass => $pass_answers) {
				if (is_array($pass_answers)) {
					foreach ($pass_answers as $question_id => $answer) {
						if (!$a_answered or ($a_answered and $answer->answered)) {
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
<?php
// Copyright (c) 2020 Institut fuer Lern-Innovation, Friedrich-Alexander-Universitaet Erlangen-Nuernberg, GPLv3, see LICENSE

/**
 * Choice Evaluation
 */
class ilExteEvalQuestionStack extends ilExteEvalQuestion
{
	/**
	 * @var bool    evaluation provides a single value for the overview level
	 */
	protected $provides_value = false;

	/**
	 * @var bool    evaluation provides data for a details screen
	 */
	protected $provides_details = true;

	/**
	 * @var bool    evaluation provides a chart
	 */
	protected $provides_chart = true;

	/**
	 * @var array   list of allowed test types, e.g. array(self::TEST_TYPE_FIXED)
	 */
	protected $allowed_test_types = array();

	/**
	 * @var array    list of question types, e.g. array('assSingleChoice', 'assMultipleChoice', ...)
	 */
	protected $allowed_question_types = array('assStackQuestion');

	/**
	 * @var string    specific prefix of language variables (lowercase classname is default)
	 */
	protected $lang_prefix = 'qst_stack';


	/**
	 * Calculate the single value for a question (to be overwritten)
	 *
	 * Note:
	 * This function will be called for many questions in sequence
	 * - Please avoid instanciation of question objects
	 * - Please try to cache question independent intermediate results
	 *
	 * @param integer $a_question_id
	 * @return ilExteStatValue
	 */
	public function calculateValue($a_question_id)
	{
		return new ilExteStatValue;
	}


	/**
	 * Calculate the details question (to be overwritten)
	 *
	 * @param integer $a_question_id
	 * @return ilExteStatDetails
	 */
	public function calculateDetails($a_question_id)
	{
		global $ilDB;


		require_once('Modules/TestQuestionPool/classes/class.assQuestion.php');
		/** @var assStackQuestion $question */
		$question = assQuestion::_instantiateQuestion($a_question_id);
		if (!is_object($question)) {
			return new ilExteStatDetails();
		}

		$raw_data = array();
		/** @var ilExteStatSourceAnswer $answer */
		foreach ($this->data->getAnswersForQuestion($a_question_id, true) as $answer) {
			$result = $ilDB->queryF(
				"SELECT * FROM tst_solutions WHERE active_fi = %s AND pass = %s AND question_fi = %s",
				array("integer", "integer", "integer"),
				array($answer->active_id, $answer->pass, $a_question_id)
			);
			while ($data = $ilDB->fetchAssoc($result)) {
				if ($data["points"] != NULL) {
					$raw_data[$answer->active_id . "_" . $answer->pass][$data["value2"]] = $data["points"];
				} else {
					$raw_data[$answer->active_id . "_" . $answer->pass][$data["value1"]] = $data["value2"];
				}
			}
		}
		$data = $this->processData($raw_data, $question->getPotentialResponsesTrees(), $question->getPoints());
		// answer details
		$details = new ilExteStatDetails();
		$details->columns = array(
			ilExteStatColumn::_create('prt', $this->txt('prt')."-".$this->txt('node'), ilExteStatColumn::SORT_TEXT),
			ilExteStatColumn::_create('model_response', $this->txt('model_response'), ilExteStatColumn::SORT_TEXT),
			ilExteStatColumn::_create('feedback_errors', $this->txt('feedback_errors'), ilExteStatColumn::SORT_TEXT),
			ilExteStatColumn::_create('partial', $this->txt('partial'), ilExteStatColumn::SORT_TEXT),
			ilExteStatColumn::_create('count', $this->txt('count'), ilExteStatColumn::SORT_NUMBER),
			ilExteStatColumn::_create('frequency', $this->txt('frequency'), ilExteStatColumn::SORT_NUMBER)
		);
		$details->chartType = ilExteStatDetails::CHART_BARS;
		$details->chartLabelsColumn = 3;

		foreach ($data as $key => $option) {
			$details->rows[] = array(
				'prt' => ilExteStatValue::_create($option["prt"] . "-" . $option["node"], ilExteStatValue::TYPE_TEXT, 0),
				'model_response' => ilExteStatValue::_create($option["answernote"], ilExteStatValue::TYPE_TEXT, 0),
				'feedback_errors' => ilExteStatValue::_create($option["feedback"], ilExteStatValue::TYPE_TEXT, 0),
				'partial' => ilExteStatValue::_create((string)$option["partial"], ilExteStatValue::TYPE_TEXT, 0),
				'count' => ilExteStatValue::_create((string)$option["count"], ilExteStatValue::TYPE_NUMBER, 0),
				'frequency' => ilExteStatValue::_create((string)$option["frequency"], ilExteStatValue::TYPE_NUMBER, 2),
			);
		}

		return $details;
	}

	public function processData($raw_data, $prts, $points)
	{
		$data = array();
		$points_structure = $this->getPointsStructure($prts, $points);
		foreach ($prts as $prt_name => $prt_obj) {
			foreach ($prt_obj->getPRTNodes() as $node_name => $node) {
				$node_name = (string)$node_name;
				//FALSE
				$data[$prt_name . "-" . $node_name . "-F"] = array("prt" => $prt_name, "node" => $node_name, "answernote" => $prt_name . "-" . $node_name . "-F", "feedback" => "", "count" => 0, "frequency" => 0, "partial" => $points_structure[$prt_name][$node_name]["false_mode"].$points_structure[$prt_name][$node_name]["false_value"]);
				//TRUE
				$data[$prt_name . "-" . $node_name . "-T"] = array("prt" => $prt_name, "node" => $node_name, "answernote" => $prt_name . "-" . $node_name . "-T", "feedback" => "", "count" => 0, "frequency" => 0, "partial" => $points_structure[$prt_name][$node_name]["true_mode"].$points_structure[$prt_name][$node_name]["true_value"]);
				//NO ANSWER
				$data[$prt_name . "-" . $node_name . "-NoAnswer"] = array("prt" => $prt_name, "node" => $node_name, "answernote" => $this->txt("no_answer"), "feedback" => "", "count" => 0, "frequency" => 0, "partial" => "");
			}
		}

		foreach ($raw_data as $attempt) {
			foreach ($prts as $prt_name => $prt_obj) {
				//ANSWERNOTE
				if (isset($attempt["xqcas_prt_" . $prt_name . "_answernote"])) {
					$answer_note = $this->processAnswerNote($attempt["xqcas_prt_" . $prt_name . "_answernote"]);

					foreach ($prt_obj->getPRTNodes() as $node_name => $node) {
						$node_name = (string)$node_name;

						//FALSE
						if ($prt_name . "-" . $node_name . "-F" == $answer_note or (stripos($answer_note, "-" . $node_name . "-F") !== FALSE)) {
							$data[$prt_name . "-" . $node_name . "-F"]["count"]++;
							if(stripos($answer_note, "-" . $node_name . "-F")){
								if($data[$prt_name . "-" . $node_name . "-F"]["feedback"] == ""){
									$data[$prt_name . "-" . $node_name . "-F"]["feedback"] .= $answer_note;
								}else{
									$data[$prt_name . "-" . $node_name . "-F"]["feedback"] .= " / ".$answer_note;
								}
							}
							//RECALCULATE frequency
							$total = (float)$data[$prt_name . "-" . $node_name . "-F"]["count"] + $data[$prt_name . "-" . $node_name . "-T"]["count"] + $data[$prt_name . "-" . $node_name . "-NoAnswer"]["count"];
							$data[$prt_name . "-" . $node_name . "-F"]["frequency"] = ((float)$data[$prt_name . "-" . $node_name . "-F"]["count"] * 100) / $total;
							$data[$prt_name . "-" . $node_name . "-T"]["frequency"] = ((float)$data[$prt_name . "-" . $node_name . "-T"]["count"] * 100) / $total;
							$data[$prt_name . "-" . $node_name . "-NoAnswer"]["frequency"] = ((float)$data[$prt_name . "-" . $node_name . "-NoAnswer"]["count"] * 100) / $total;
							continue;

						}
						//TRUE
						if ($prt_name . "-" . $node_name . "-T" == $answer_note or (stripos($answer_note, "-" . $node_name . "-T") !== FALSE)) {
							$data[$prt_name . "-" . $node_name . "-T"]["count"]++;
							if(stripos($answer_note, "-" . $node_name . "-T")) {
								if($data[$prt_name . "-" . $node_name . "-T"]["feedback"] == ""){
									$data[$prt_name . "-" . $node_name . "-T"]["feedback"] .= $answer_note;
								}else{
									$data[$prt_name . "-" . $node_name . "-T"]["feedback"] .= " / ". $answer_note;
								}
							}
							//RECALCULATE frequency
							$total = (float)$data[$prt_name . "-" . $node_name . "-F"]["count"] + $data[$prt_name . "-" . $node_name . "-T"]["count"] + $data[$prt_name . "-" . $node_name . "-NoAnswer"]["count"];
							$data[$prt_name . "-" . $node_name . "-F"]["frequency"] = ((float)$data[$prt_name . "-" . $node_name . "-F"]["count"] * 100) / $total;
							$data[$prt_name . "-" . $node_name . "-T"]["frequency"] = ((float)$data[$prt_name . "-" . $node_name . "-T"]["count"] * 100) / $total;
							$data[$prt_name . "-" . $node_name . "-NoAnswer"]["frequency"] = ((float)$data[$prt_name . "-" . $node_name . "-NoAnswer"]["count"] * 100) / $total;
							continue;
						}
						//NO ANSWER
						$data[$prt_name . "-" . $node_name . "-NoAnswer"]["count"]++;
						//$data[$prt_name . "-" . $node_name . "-NoAnswer"]["feedback"] .= $answer_note;
						//RECALCULATE frequency
						$total = (float)$data[$prt_name . "-" . $node_name . "-F"]["count"] + $data[$prt_name . "-" . $node_name . "-T"]["count"] + $data[$prt_name . "-" . $node_name . "-NoAnswer"]["count"];
						$data[$prt_name . "-" . $node_name . "-F"]["frequency"] = ((float)$data[$prt_name . "-" . $node_name . "-F"]["count"] * 100) / $total;
						$data[$prt_name . "-" . $node_name . "-T"]["frequency"] = ((float)$data[$prt_name . "-" . $node_name . "-T"]["count"] * 100) / $total;
						$data[$prt_name . "-" . $node_name . "-NoAnswer"]["frequency"] = ((float)$data[$prt_name . "-" . $node_name . "-NoAnswer"]["count"] * 100) / $total;
					}

					//COUNT PRT
				}
			}
		}

		return $data;
	}

	/**
	 *
	 * Maxima seems to rename the nodes, prtx-0 is prtx-1 for maxima
	 * This function translate from maxima to ILIAS notation to avoid user confusion
	 * @param $raw_answernote
	 */
	public function processAnswerNote($raw_answernote)
	{

		$answer_note = str_replace("-1-", "-0-", $raw_answernote);
		$answer_note = str_replace("-2-", "-1-", $answer_note);
		$answer_note = str_replace("-3-", "-2-", $answer_note);
		$answer_note = str_replace("-4-", "-3-", $answer_note);
		$answer_note = str_replace("-5-", "-4-", $answer_note);
		$answer_note = str_replace("-6-", "-5-", $answer_note);
		$answer_note = str_replace("-7-", "-6-", $answer_note);
		$answer_note = str_replace("-8-", "-7-", $answer_note);
		$answer_note = str_replace("-9-", "-8-", $answer_note);
		$answer_note = str_replace("-10-", "-9-", $answer_note);
		$answer_note = str_replace("-11-", "-10-", $answer_note);
		$answer_note = str_replace("-12-", "-11-", $answer_note);
		$answer_note = str_replace("-13-", "-12-", $answer_note);
		$answer_note = str_replace("-14-", "-13-", $answer_note);
		$answer_note = str_replace("-15-", "-14-", $answer_note);
		$answer_note = str_replace("-16-", "-15-", $answer_note);

		return $answer_note;
	}

	public function getPointsStructure($prts, $question_points)
	{
		//Set variables
		$max_weight = 0.0;
		$structure = array();

		//Get max weight of the PRT
		foreach ($prts as $prt_name => $prt) {
			$max_weight += $prt->getPRTValue();
		}

		//fill the structure
		foreach ($prts as $prt_name => $prt) {
			$prt_max_weight = $prt->getPRTValue();
			$prt_max_points = ($prt_max_weight / $max_weight) * $question_points;
			$structure[$prt_name]['max_points'] = $prt_max_points;
			foreach ($prt->getPRTNodes() as $node_name => $node) {
				$structure[$prt_name][$node_name]['true_mode'] = $node->getTrueScoreMode();
				$structure[$prt_name][$node_name]['true_value'] = ($node->getTrueScore() * $prt_max_points);
				$structure[$prt_name][$node_name]['false_mode'] = $node->getFalseScoreMode();
				$structure[$prt_name][$node_name]['false_value'] = ($node->getFalseScore() * $prt_max_points);
			}
		}

		return $structure;
	}

}
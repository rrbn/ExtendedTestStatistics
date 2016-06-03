<?php

include_once("./Services/Component/classes/class.ilPluginConfigGUI.php");

/**
 * Extended Test statistics configuration user interface class
 *
 * @author Fred Neumann <fred.neumann@fau.de>
 * @version $Id$
 *
 */
class ilExtendedTestStatisticsConfigGUI extends ilPluginConfigGUI
{

	protected $plugin;

	/**
	 * Handles all commmands, default is "configure"
	 */
	function performCommand($cmd)
	{
		$this->plugin = $this->getPluginObject();
		switch ($cmd) {
			case "configure":
			case "showTestEvaluations":
			case "showQuestionEvaluations":
			case "saveTestSettings":
			case "saveQuestionSettings":
				$this->initTabs();
				$this->$cmd();
				break;

		}
	}

	/**
	 * Configure screen
	 */
	function configure()
	{
		global $tpl;

		$form = $this->initConfigurationForm();
		$tpl->setContent($form->getHTML());
	}

	//
	// From here on, this is just an example implementation using
	// a standard form (without saving anything)
	//

	/**
	 * Init configuration form.
	 *
	 * @return object form object
	 */
	public function initConfigurationForm()
	{
		global $ilTabs;

		$this->plugin = $this->getPluginObject();

		$ilTabs->setTabActive('show_test_evaluations');

		$form = $this->getTestEvaluationsForm();

		return $form;
	}

	/**
	 * Save form input (currently does not save anything to db)
	 *
	 */
	public function saveTestSettings()
	{
		global $tpl, $ilDB, $ilCtrl;

		$this->plugin = $this->getPluginObject();
		$test_form = $this->getTestEvaluationsForm();
		$evaluation_classes = self::_getEvaluationClasses("", "classes");
		$evaluation_db = self::_getEvaluationClasses("", "db");

		if ($test_form->checkInput()) {
			//Check for test changes
			foreach ($evaluation_classes["Tests"] as $evaluation_name => $value) {
				$new_value = $test_form->getInput($evaluation_name);
				//Check for inclusion into the DB
				if (isset($evaluation_db["Tests"][$evaluation_name])) {
					//If value is different, update it to DB
					if ($value != $new_value) {
						$update_query = $ilDB->query("UPDATE etstat_settings SET value = '" . $new_value . "' WHERE evaluation_name = '" . $evaluation_name . "'");
						if (!$update_query->result) {
							ilUtil::sendFailure($this->plugin->txt("error_saving_configuration"), true);
						}
					}
				} else {
					$ilDB->insert("etstat_settings", array(
						"evaluation_name" => array("text", $evaluation_name),
						"value" => array("text", "admin")));
				}
			}
		} else {
			$test_form->setValuesByPost();
			$tpl->setContent($test_form->getHtml());
		}

		//Save to DB
		ilUtil::sendSuccess($this->plugin->txt("test_settings_saved"), true);
		$ilCtrl->redirect($this, "showTestEvaluations");

	}

	public function saveQuestionSettings()
	{
		global $tpl, $ilDB, $ilCtrl;

		$this->plugin = $this->getPluginObject();
		$question_form = $this->getQuestionEvaluationsForm();
		$evaluation_classes = self::_getEvaluationClasses("", "classes");
		$evaluation_db = self::_getEvaluationClasses("", "db");

		if ($question_form->checkInput()) {
			//Check for question changes
			foreach ($evaluation_classes["Questions"] as $evaluation_name => $value) {
				$new_value = $question_form->getInput($evaluation_name);
				//Check for inclusion into the DB
				if (isset($evaluation_db["Questions"][$evaluation_name])) {
					//If value is different, update it to DB
					if ($value != $new_value) {
						$update_query = $ilDB->query("UPDATE etstat_settings SET value = '" . $new_value . "' WHERE evaluation_name = '" . $evaluation_name . "'");
						if (!$update_query->result) {
							ilUtil::sendFailure($this->plugin->txt("error_saving_configuration"), true);
						}
					}
				} else {
					$ilDB->insert("etstat_settings", array(
						"evaluation_name" => array("text", $evaluation_name),
						"value" => array("text", "admin")));
				}
			}
		} else {
			$question_form->setValuesByPost();
			$tpl->setContent($question_form->getHtml());
		}

		//Save to DB
		ilUtil::sendSuccess($this->plugin->txt("question_settings_saved"), true);
		$ilCtrl->redirect($this, "showQuestionEvaluations");

	}

	/**
	 * @param $a_type
	 * @param null $a_mode
	 * Includes the available evaluation classes and return their names
	 * @return array    list of included class names
	 */
	public static function _getEvaluationClasses($a_type, $a_mode = NULL)
	{
		global $ilDB;

		include_once("./Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/ExtendedTestStatistics/classes/abstract/class.ilExteEvalBase.php");
		include_once("./Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/ExtendedTestStatistics/classes/abstract/class.ilExteEvalQuestion.php");
		include_once("./Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/ExtendedTestStatistics/classes/abstract/class.ilExteEvalTest.php");

		if ($a_mode != "classes") {
			//Step 1: Read from the database
			$db_classnames = array();
			$database_select = $ilDB->query("SELECT * FROM etstat_settings");
			while ($evaluations_db_row = $ilDB->fetchAssoc($database_select)) {
				if (strpos($evaluations_db_row["evaluation_name"], "ilExteEvalQuestion") === 0) {
					$db_classnames["Questions"][$evaluations_db_row["evaluation_name"]] = $evaluations_db_row["value"];
				} elseif (strpos($evaluations_db_row["evaluation_name"], "ilExteEvalTest") === 0) {
					$db_classnames["Tests"][$evaluations_db_row["evaluation_name"]] = $evaluations_db_row["value"];
				}
			}

			//Return db classes if mode is set to "db"
			if ($a_mode == "db") {
				if ($a_type == "test") {
					return $db_classnames["Tests"];
				} elseif ($a_type == "question") {
					return $db_classnames["Questions"];
				} else {
					return $db_classnames;
				}
			}
		}

		//Step 2: Read from class files
		$classnames = array();
		$classfiles = glob('./Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/ExtendedTestStatistics/classes/evaluations/class.*.php');
		if (!empty($classfiles)) {
			foreach ($classfiles as $file) {
				require_once($file);
				$parts = explode('.', basename($file));
				if (strpos($parts[1], "ilExteEvalQuestion") === 0) {
					if (isset($db_classnames["Questions"][$parts[1]]) OR $a_mode == "classes") {
						$classnames["Questions"][$parts[1]] = $db_classnames["Questions"][$parts[1]];
					}
				} elseif (strpos($parts[1], "ilExteEvalTest") === 0) {
					if (isset($db_classnames["Tests"][$parts[1]]) OR $a_mode == "classes") {
						$classnames["Tests"][$parts[1]] = $db_classnames["Tests"][$parts[1]];
					}
				}
			}
		}

		if ($a_type == "test") {
			return $classnames["Tests"];
		} elseif ($a_type == "question") {
			return $classnames["Questions"];
		} else {
			return $classnames;
		}
	}

	/**
	 * @param string $a_mode
	 */
	public function initTabs($a_mode = "")
	{
		global $ilCtrl, $ilTabs;
		$ilTabs->addTab("show_test_evaluations", $this->plugin->txt('show_test_evaluations'), $ilCtrl->getLinkTarget($this, 'showTestEvaluations'));
		$ilTabs->addTab("show_question_evaluations", $this->plugin->txt('show_question_evaluations'), $ilCtrl->getLinkTarget($this, 'showQuestionEvaluations'));

	}

	public function showTestEvaluations()
	{
		global $tpl, $ilTabs;
		$ilTabs->setTabActive('show_test_evaluations');

		$form = $this->getTestEvaluationsForm();
		$tpl->setContent($form->getHTML());
	}

	public function showQuestionEvaluations()
	{
		global $tpl, $ilTabs;
		$ilTabs->setTabActive('show_question_evaluations');

		$form = $this->getQuestionEvaluationsForm();
		$tpl->setContent($form->getHTML());
	}

	public function getTestEvaluationsForm()
	{
		global $ilCtrl, $lng;
		require_once("./Services/Form/classes/class.ilPropertyFormGUI.php");
		$form = new ilPropertyFormGUI();
		$form->setFormAction($ilCtrl->getFormAction($this));

		//Run throw all the test evaluations to check if there must be available for admins
		// or users or not available in test of current platform

		foreach (self::_getEvaluationClasses("test") as $evaluation_class => $value) {
			$select_input = new ilSelectInputGUI($this->plugin->txt(strtolower($evaluation_class) . "_title_short"), $evaluation_class);
			$select_input->setOptions(array(
				"admin" => $this->plugin->txt("evaluation_available_for_admins"),
				"users" => $this->plugin->txt("evaluation_available_for_users"),
				"none" => $this->plugin->txt("evaluation_available_for_noone")
			));
			$select_input->setValue($value);
			$form->addItem($select_input);
		}

		$form->setTitle($this->plugin->txt('test_evaluation_settings'));
		$form->addCommandButton("saveTestSettings", $lng->txt("save"));

		return $form;
	}

	public function getQuestionEvaluationsForm()
	{
		global $ilCtrl, $lng;
		require_once("./Services/Form/classes/class.ilPropertyFormGUI.php");
		$form = new ilPropertyFormGUI();
		$form->setFormAction($ilCtrl->getFormAction($this));

		//Run throw all the test evaluations to check if there must be available for admins
		// or users or not available in test of current platform
		foreach (self::_getEvaluationClasses("question") as $evaluation_class => $value) {
			$select_input = new ilSelectInputGUI($this->plugin->txt(strtolower($evaluation_class) . "_title_short"), $evaluation_class);
			$select_input->setOptions(array(
				"admin" => $this->plugin->txt("evaluation_available_for_admins"),
				"users" => $this->plugin->txt("evaluation_available_for_users"),
				"none" => $this->plugin->txt("evaluation_available_for_noone")
			));
			$select_input->setValue($value);
			$form->addItem($select_input);
		}

		$form->setTitle($this->plugin->txt('question_evaluation_settings'));
		$form->addCommandButton("saveQuestionSettings", $lng->txt("save"));


		return $form;
	}

}

?>
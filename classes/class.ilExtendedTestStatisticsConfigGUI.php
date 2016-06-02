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
			case "save":
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
	public function save()
	{
		global $tpl, $lng, $ilCtrl;

		$pl = $this->getPluginObject();

		$form = $this->getTestEvaluationsForm();
		if ($form->checkInput()) {
			$set1 = $form->getInput("setting_1");
			$set2 = $form->getInput("setting_2");

			// @todo: implement saving to db

			ilUtil::sendSuccess($pl->txt("saving_invoked"), true);
			$ilCtrl->redirect($this, "configure");
		} else {
			$form->setValuesByPost();
			$tpl->setContent($form->getHtml());
		}
	}

	/**
	 * Includea the available evaluation classes and return their names
	 * @return array    list of included class namens
	 */
	protected function getEvaluationClasses($a_mode)
	{
		global $ilDB;

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


		//Read from class files
		$this->plugin->includeClass('abstract/class.ilExteEvalBase.php');
		$this->plugin->includeClass('abstract/class.ilExteEvalQuestion.php');
		$this->plugin->includeClass('abstract/class.ilExteEvalTest.php');

		$classnames = array();
		$classfiles = glob($this->plugin->getDirectory() . '/classes/evaluations/class.*.php');
		if (!empty($classfiles)) {
			foreach ($classfiles as $file) {
				require_once($file);
				$parts = explode('.', basename($file));
				if (strpos($parts[1], "ilExteEvalQuestion") === 0) {
					if (isset($db_classnames["Questions"][$parts[1]])) {
						$classnames["Questions"][$parts[1]] = $db_classnames["Questions"][$parts[1]];
					}
				} elseif (strpos($parts[1], "ilExteEvalTest") === 0) {
					if (isset($db_classnames["Tests"][$parts[1]])) {
						$classnames["Tests"][$parts[1]] = $db_classnames["Tests"][$parts[1]];
					}
				}
			}
		}

		if ($a_mode == "test") {
			return $classnames["Tests"];
		} elseif ($a_mode == "question") {
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

		foreach ($this->getEvaluationClasses("test") as $evaluation_class => $value) {
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
		$form->addCommandButton("save", $lng->txt("save"));

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
		foreach ($this->getEvaluationClasses("question") as $evaluation_class => $value) {
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
		$form->addCommandButton("save", $lng->txt("save"));


		return $form;
	}

}

?>
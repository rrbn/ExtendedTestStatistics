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

	/*
	 * @var ilExtendedTestStatisticsConfig
	 */
	protected $config;

	/**
	 * Handles all commmands, default is "configure"
	 */
	function performCommand($cmd)
	{
		$this->plugin = $this->getPluginObject();

		//Set config object
		$this->plugin->includeClass("config/class.ilExtendedTestStatisticsConfig.php");
		$config_obg = new ilExtendedTestStatisticsConfig($this->plugin);
		$this->config = $config_obg;

		switch ($cmd)
		{
			case "configure":
			case "showTestEvaluations":
				$this->initTabs();
				$this->configure("test");
				break;
			case "showQuestionEvaluations":
				$this->initTabs();
				$this->configure("question");
				break;
			case "saveTestSettings":
				$this->initTabs();
				$this->saveSettings("test");
				break;
			case "saveQuestionSettings":
				$this->initTabs();
				$this->saveSettings("question");
				break;
		}
	}

	/**
	 * Configure screen
	 */
	function configure($a_mode)
	{
		global $tpl;

		$form = $this->initConfigurationForm($a_mode);
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
	public function initConfigurationForm($a_mode)
	{
		global $ilTabs;

		$this->plugin = $this->getPluginObject();

		if ($a_mode == "test")
		{
			$ilTabs->setTabActive('show_test_evaluations');

			$form = $this->getTestEvaluationsForm();

			return $form;

		} elseif ($a_mode == "question")
		{
			$ilTabs->setTabActive('show_question_evaluations');

			$form = $this->getQuestionEvaluationsForm();

			return $form;

		}
	}

	/**
	 * @param $a_type
	 */
	public function saveSettings($a_type)
	{
		global $tpl, $ilCtrl;

		$this->plugin = $this->getPluginObject();
		$test_form = $this->getTestEvaluationsForm();
		$evaluation_classes = $this->config->getEvaluationClasses($a_type);

		if ($test_form->checkInput())
		{
			//Check for test changes
			foreach ($evaluation_classes as $evaluation_name => $evaluation_name_2)
			{
				$new_value = $test_form->getInput($evaluation_name);
				if ($new_value)
				{
					//Delete entry
					if ($this->config->deleteConfig($evaluation_name))
					{
						//Insert entry
						$this->config->insertConfig($evaluation_name, $new_value);
					}
				}
			}
		} else
		{
			$test_form->setValuesByPost();
			$tpl->setContent($test_form->getHtml());
		}

		if ($a_type == "question")
		{
			ilUtil::sendSuccess($this->plugin->txt("question_settings_saved"), true);
			$ilCtrl->redirect($this, "showQuestionEvaluations");
		}
		if ($a_type == "test")
		{
			ilUtil::sendSuccess($this->plugin->txt("test_settings_saved"), true);
			$ilCtrl->redirect($this, "showTestEvaluations");
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

		foreach ($this->config->getEvaluationClasses("test") as $evaluation_class => $value)
		{
			$select_input = new ilSelectInputGUI($this->plugin->txt(strtolower($evaluation_class) . "_title_short"), $evaluation_class);
			$select_input->setOptions(array("admin" => $this->plugin->txt("evaluation_available_for_admins"), "users" => $this->plugin->txt("evaluation_available_for_users"), "none" => $this->plugin->txt("evaluation_available_for_noone")));
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
		foreach ($this->config->getEvaluationClasses("question") as $evaluation_class => $value)
		{
			$select_input = new ilSelectInputGUI($this->plugin->txt(strtolower($evaluation_class) . "_title_short"), $evaluation_class);
			$select_input->setOptions(array("admin" => $this->plugin->txt("evaluation_available_for_admins"), "users" => $this->plugin->txt("evaluation_available_for_users"), "none" => $this->plugin->txt("evaluation_available_for_noone")));
			$select_input->setValue($value);
			$form->addItem($select_input);
		}

		$form->setTitle($this->plugin->txt('question_evaluation_settings'));
		$form->addCommandButton("saveQuestionSettings", $lng->txt("save"));


		return $form;
	}

}

?>
<?php

include_once("./Services/Component/classes/class.ilPluginConfigGUI.php");

/**
 * Extended Test statistics configuration user interface class
 *
 * @author Fred Neumann <fred.neumann@fau.de>
 * @author Jesus Copado <jesus.copado@fau.de>
 */
class ilExtendedTestStatisticsConfigGUI extends ilPluginConfigGUI
{
	/** @var ilExtendedTestStatisticsPlugin $plugin */
	protected $plugin;

	/** @var ilExtendedTestStatisticsConfig $config */
	protected $config;

	/**
	 * Handles all commands, default is "configure"
	 */
	public function performCommand($cmd)
	{
		$this->plugin = $this->getPluginObject();

		//Set config object
		$this->plugin->includeClass("class.ilExtendedTestStatisticsConfig.php");
		$this->config = new ilExtendedTestStatisticsConfig($this->plugin);

		switch ($cmd)
		{
			case "configure":
			case "showTestEvaluations":
				$this->initTabs("test");
				$this->configure("test");
				break;
			case "showQuestionEvaluations":
				$this->initTabs("question");
				$this->configure("question");
				break;
			case "saveTestSettings":
				$this->initTabs("test");
				$this->saveSettings("test");
				break;
			case "saveQuestionSettings":
				$this->initTabs("question");
				$this->saveSettings("question");
				break;
		}
	}

	/**
	 * Show configuration screen screen
	 * @var	string	$a_mode	(test or question)
	 */
	protected function configure($a_mode)
	{
		global $tpl;
		$form = $this->initConfigurationForm($a_mode);
		$tpl->setContent($form->getHTML());
	}


	/**
	 * Initialize the configuration form
	 * @param	string $a_type	(test or question)
	 * @return ilPropertyFormGUI form object
	 */
	protected function initConfigurationForm($a_type)
	{
		global $ilCtrl, $lng;

		require_once("./Services/Form/classes/class.ilPropertyFormGUI.php");
		$form = new ilPropertyFormGUI();
		$form->setFormAction($ilCtrl->getFormAction($this));

		// Run throw all the test evaluations to check if there must be available for admins
		// or users or not available in test of current platform
		foreach ($this->config->getEvaluationClasses($a_type) as $class => $value)
		{
			$select_input = new ilSelectInputGUI($this->plugin->txt(strtolower($class) . "_title_long"), $class);
			$select_input->setOptions($this->config->getAvailabilityOptions());
			$select_input->setValue($value);
			$select_input->setInfo($this->plugin->txt(strtolower($class) . "_description"));
			$form->addItem($select_input);
		}

		$form->setTitle($this->plugin->txt($a_type == 'test' ? 'test_evaluation_settings' : 'question_evaluation_settings'));
		$form->addCommandButton("saveTestSettings", $lng->txt("save"));
		return $form;
	}

	/**
	 * Save the settings
	 * @param $a_type (test or question)
	 */
	protected function saveSettings($a_type)
	{
		global $tpl, $ilCtrl;

		$form = $this->initConfigurationForm($a_type);
		if ($form->checkInput())
		{
			foreach ($this->config->getEvaluationClasses($a_type) as $evaluation_class => $value)
			{
				$new_value = $form->getInput($evaluation_class);
				if ($new_value)
				{
					$this->config->writeAvailability($evaluation_class, $new_value);
				}
			}
			ilUtil::sendSuccess($this->plugin->txt($a_type == 'test' ? "test_settings_saved" : "question_settings_saved"), true);
			$ilCtrl->redirect($this, $a_type == 'test' ? "showTestEvaluations" : "showQuestionEvaluations");
		}
		else
		{
			$form->setValuesByPost();
			$tpl->setContent($form->getHtml());
		}
	}

	/**
	 * Init the Tabs
	 * @param string $a_mode	active settings mode (test or question)
	 */
	protected function initTabs($a_mode = "")
	{
		global $ilCtrl, $ilTabs;

		$ilTabs->addTab("show_test_evaluations", $this->plugin->txt('show_test_evaluations'), $ilCtrl->getLinkTarget($this, 'showTestEvaluations'));
		$ilTabs->addTab("show_question_evaluations", $this->plugin->txt('show_question_evaluations'), $ilCtrl->getLinkTarget($this, 'showQuestionEvaluations'));
		$ilTabs->setTabActive($a_mode == 'test' ? 'show_test_evaluations' : 'show_question_evaluations');
	}
}

?>
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
		$this->config = $this->plugin->getConfig();

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
		/**
		 * @var ilExteEvalBase $class	(classname, not object)
		 */
		foreach ($this->config->getEvaluationClasses($a_type) as $class => $value)
		{
			/** @var ilExteEvalBase $evaluation */
			$evaluation = new $class($this->plugin);
			$prefix = $evaluation->getLangPrefix();

			$select_input = new ilSelectInputGUI($this->plugin->txt($prefix . "_title_long"), $class);
			$select_input->setOptions($this->config->getAvailabilityOptions());
			$select_input->setValue($value);
			$select_input->setInfo($this->plugin->txt($prefix . "_description").'<br /><em>'.$class.'</em>');
			$form->addItem($select_input);

			foreach ($evaluation->getParams() as $name => $param)
			{
				$title = $evaluation->txt($name.'_title');
				$description = $evaluation->txt($name.'_description');
				$postvar = get_class($evaluation).'_'.$name;

				switch($param->type)
				{
					case ilExteStatParam::TYPE_BOOLEAN:
						$input = new ilCheckboxInputGUI($title, $postvar);
						$input->setChecked($param->value);
						break;
					case ilExteStatParam::TYPE_FLOAT:
						$input = new ilNumberInputGUI($title, $postvar);
						$input->allowDecimals(true);
						$input->setValue($param->value);
						break;
					case ilExteStatParam::TYPE_INT:
					default:
						$input = new ilNumberInputGUI($title, $postvar);
						$input->allowDecimals(false);
						$input->setValue($param->value);
						break;
				}
				$input->setInfo($description);
				$select_input->addSubItem($input);
			}

		}

		$form->setTitle($this->plugin->txt($a_type == 'test' ? 'test_evaluation_settings' : 'question_evaluation_settings'));
		$form->addCommandButton($a_type == 'test' ? "saveTestSettings" : "saveQuestionSettings", $lng->txt("save"));
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
			foreach ($this->config->getEvaluationClasses($a_type) as $class => $value)
			{
				$new_value = $form->getInput($class);
				if ($new_value)
				{
					$this->config->writeAvailability($class, $new_value);

					/** @var ilExteEvalBase $evaluation */
					$evaluation = new $class($this->plugin);
					foreach ($evaluation->getParams() as $name => $param)
					{
						$postvar = $class.'_'.$name;
						$this->config->writeParameter($class, $name, $form->getInput($postvar));
					}
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
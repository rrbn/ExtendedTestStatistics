<?php

require_once ('Modules/Test/classes/class.ilObjTest.php');

/**
 * Extended Test Statistic Page GUI
 *
 * @author Fred Neumann <fred.neumann@fau.de>
 * @version $Id$
 *
 * @ilCtrl_IsCalledBy ilExtendedTestStatisticsPageGUI: ilUIPluginRouterGUI
 */
class ilExtendedTestStatisticsPageGUI
{
	/** @var ilCtrl $ctrl */
	protected $ctrl;

	/** @var ilTemplate $tpl */
	protected $tpl;

	/** @var ilExtendedTestStatisticsPlugin $plugin */
	protected $plugin;

	/** @var ilObjTest $testObj */
	protected $testObj;

	/** @var ilExtendedTestStatistics $statObj */
	protected $statObj;

	/**
	 * ilExtendedTestStatisticsPageGUI constructor.
	 */
	public function __construct()
	{
		global $ilCtrl, $tpl, $lng;

		$this->ctrl = $ilCtrl;
		$this->tpl = $tpl;

		$lng->loadLanguageModule('assessment');

		$this->plugin = ilPlugin::getPluginObject(IL_COMP_SERVICE, 'UIComponent', 'uihk', 'ExtendedTestStatistics');
		$this->plugin->includeClass('class.ilExtendedTestStatistics.php');

		$this->testObj = new ilObjTest($_GET['ref_id']);
		$this->statObj = new ilExtendedTestStatistics($this->testObj, $this->plugin);
	}

	/**
	* Handles all commands, default is "show"
	*/
	public function executeCommand()
	{
		/** @var ilAccessHandler $ilAccess */
		/** @var ilErrorHandling $ilErr */
		global $ilAccess, $ilErr, $lng;

		if (!$ilAccess->checkAccess('write','',$this->testObj->getRefId()))
		{
            ilUtil::sendFailure($lng->txt("permission_denied"), true);
            ilUtil::redirect("goto.php?target=tst_".$this->testObj->getRefId());
		}

		$this->ctrl->saveParameter($this, 'ref_id');
		$cmd = $this->ctrl->getCmd('showTestOverview');

		switch ($cmd)
		{
			case "showTestOverview":
            case "showTestDetails":
			case "showQuestionsOverview":
            case "showQuestionDetails":
                $this->prepareOutput();
                $this->$cmd();
                break;
			case "exportEvaluations":
				$this->$cmd();
				break;
			default:
                ilUtil::sendFailure($lng->txt("permission_denied"), true);
                ilUtil::redirect("goto.php?target=tst_".$this->testObj->getRefId());
				break;
		}
	}

	/**
	 * Get the plugin object
	 * @return ilExtendedTestStatisticsPlugin|null
	 */
	public function getPlugin()
	{
		return $this->plugin;
	}

    /**
     * Get the statistics object
     * @return     ilExtendedTestStatistics|null
     */
    public function getStatisticsObject()
    {
        return $this->statObj;
    }

	/**
	 * Prepare the test header, tabs etc.
	 */
	protected function prepareOutput()
	{
		/** @var ilLocatorGUI $ilLocator */
		/** @var ilLanguage $lng */
		global $ilLocator, $lng;

		$this->ctrl->setParameterByClass('ilObjTestGUI', 'ref_id',  $this->testObj->getRefId());
		$ilLocator->addRepositoryItems($this->testObj->getRefId());
		$ilLocator->addItem($this->testObj->getTitle(),$this->ctrl->getLinkTargetByClass('ilObjTestGUI'));

		$this->tpl->getStandardTemplate();
		$this->tpl->setLocator();
		$this->tpl->setTitle($this->testObj->getPresentationTitle());
		$this->tpl->setDescription($this->testObj->getLongDescription());
		$this->tpl->setTitleIcon(ilObject::_getIcon('', 'big', 'tst'), $lng->txt('obj_tst'));
	}

	/**
	 * Show the test overview
	 */
	protected function showTestOverview()
	{
		$this->setOverviewToolbar(ilExtendedTestStatistics::LEVEL_TEST);

		/** @var  ilExteStatTestOverviewTableGUI $tableGUI */
		$this->plugin->includeClass('tables/class.ilExteStatTableGUI.php');
		$tableGUI = ilExteStatTableGUI::_create('ilExteStatTestOverviewTableGUI', $this, 'showTestOverview');
		$tableGUI->prepareData();

		$this->tpl->setContent($tableGUI->getHTML());
		$this->tpl->show();
	}

    /**
     * Show the detailed evaluation for a test
     */
    protected function showTestDetails()
    {
		$this->setDetailsToolbar('showTestOverview');
        $this->ctrl->saveParameter($this, 'details');

        $evaluation = $this->statObj->getEvaluation($_GET['details']);

		/** @var  ilExteStatDetailsTableGUI $tableGUI */
		$this->plugin->includeClass('tables/class.ilExteStatTableGUI.php');
		$tableGUI = ilExteStatTableGUI::_create('ilExteStatDetailsTableGUI', $this, 'showTestDetails');
		$tableGUI->prepareData($evaluation->getDetails());
		$tableGUI->setTitle($evaluation->getShortTitle());
		$tableGUI->setDescription($evaluation->getDescription());

		$this->tpl->setContent($tableGUI->getHTML());
		$this->tpl->show();
    }

    /**
     * Show the questions overview
     */
	protected function showQuestionsOverview()
	{
		$this->setOverviewToolbar(ilExtendedTestStatistics::LEVEL_QUESTION);

		/** @var  ilExteStatQuestionsOverviewTableGUI $tableGUI */
		$this->plugin->includeClass('tables/class.ilExteStatTableGUI.php');
        $tableGUI = ilExteStatTableGUI::_create('ilExteStatQuestionsOverviewTableGUI', $this, 'showQuestionsOverview');
        $tableGUI->prepareData();

        $this->tpl->setContent($tableGUI->getHTML());
        $this->tpl->show();
	}


    /**
     * Show the detailed evaluation for a question
     */
    protected function showQuestionDetails()
    {
		$this->setDetailsToolbar('showQuestionsOverview');
        $this->ctrl->saveParameter($this, 'details');
        $this->ctrl->saveParameter($this, 'qid');

        $evaluation = $this->statObj->getEvaluation($_GET['details']);

		/** @var  ilExteStatDetailsTableGUI $tableGUI */
		$this->plugin->includeClass('tables/class.ilExteStatTableGUI.php');
		$tableGUI = ilExteStatTableGUI::_create('ilExteStatDetailsTableGUI', $this, 'showQuestionDetails');
		$tableGUI->prepareData($evaluation->getDetails($_GET['qid']));
		$tableGUI->setTitle($evaluation->getShortTitle());
		$tableGUI->setDescription($evaluation->getDescription());

        $this->tpl->setContent($tableGUI->getHTML());
        $this->tpl->show();
    }

	/**
	 * Set the Toolbar for the overview page
	 * @param string	$level
	 */
	protected function setOverviewToolbar($level)
	{
		/** @var ilToolbarGUI $ilToolbar */
		global $ilToolbar, $lng;

		$ilToolbar->setFormName('etstat_toolbar');
		$ilToolbar->setFormAction($this->ctrl->getFormAction($this));

		require_once 'Services/Form/classes/class.ilSelectInputGUI.php';
		$export_type = new ilSelectInputGUI($lng->txt('exp_eval_data'), 'export_type');
		$options = array(
			'excel_overview' => $this->plugin->txt('exp_type_excel_overviews'),
			'excel_details' => $this->plugin->txt('exp_type_excel_details'),
		);
		$export_type->setOptions($options);

		$ilToolbar->addInputItem($export_type, true);
		require_once 'Services/UIComponent/Button/classes/class.ilSubmitButton.php';
		$button = ilSubmitButton::getInstance();
		$button->setCommand('exportEvaluations');
		$button->setCaption('export');
		$button->getOmitPreventDoubleSubmission();
		$ilToolbar->addButtonInstance($button);
	}

	/**
	 * Set the Toolbar for the details page
	 * @param string  $backCmd
	 */
	protected function setDetailsToolbar($backCmd)
	{
		/** @var ilToolbarGUI $ilToolbar */
		global $ilToolbar, $lng;

		$ilToolbar->setFormName('etstat_toolbar');
		$ilToolbar->setFormAction($this->ctrl->getFormAction($this));

		require_once 'Services/UIComponent/Button/classes/class.ilSubmitButton.php';
		$button = ilSubmitButton::getInstance();
		$button->setCommand($backCmd);
		$button->setCaption('back');
		$button->getOmitPreventDoubleSubmission();
		$ilToolbar->addButtonInstance($button);

	}

	/**
	 * Export the evaluations
	 */
	protected function exportEvaluations()
	{
		switch ($_POST['export_type'])
		{
			case 'excel_overview':
			case 'excel_details':
				$this->plugin->includeClass("export/class.ilExteStatExportExcel.php");
				require_once('Modules/Test/classes/class.ilTestExportFilename.php');

				$export = new ilExteStatExportExcel($this->plugin, $this->statObj, $_POST['export_type'] == 'excel_details');
				$export->buildExportFile(new ilTestExportFilename($this->testObj));
				break;
		}
	}
}
?>
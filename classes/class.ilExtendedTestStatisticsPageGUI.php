<?php
// Copyright (c) 2017 Institut fuer Lern-Innovation, Friedrich-Alexander-Universitaet Erlangen-Nuernberg, GPLv3, see LICENSE

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

		if (!$ilAccess->checkAccess('tst_statistics','',$this->testObj->getRefId()))
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
				if ($this->prepareOutput())
				{
					$this->$cmd();
				}
                break;
			case "exportEvaluations":
			case "deliverExportFile":
			case "selectEvaluatedPass":
			case "flushCache":
				$this->$cmd();
				break;
			case "applyFilter":
			case "resetFilter":
				if ($this->prepareOutput())
				{
					$this->showQuestionsOverview();
				}
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
	 * Get the test object id (needed for table filter)
	 * @return int
	 */
	public function getId()
	{
		return $this->testObj->getId();
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
		$this->tpl->addCss($this->plugin->getStyleSheetLocation('exte_stat.css'));

		if ($this->statObj->getSourceData()->getTestType() == ilExteEvalBase::TEST_TYPE_DYNAMIC)
		{
			ilUtil::sendFailure($this->plugin->txt('not_for_dynamic_test'));
			$this->tpl->show();
			return false;
		}

		return true;
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

		$legendGUI = ilExteStatTableGUI::_create('ilExteStatLegendTableGUI', $this, 'showTestOverview');
		$this->tpl->setContent($tableGUI->getHTML() . $legendGUI->getHTML());
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
		$chartHTML = '';
        if ($evaluation->providesChart())
        {
            $chart = $evaluation->getChart();
            $chartHTML = $chart->getHTML();
        }

        $customHTML = '';
        if ($evaluation->providesHTML())
        {
        	$customHTML = $evaluation->getCustomHTML();
        }

		/** @var  ilExteStatDetailsTableGUI $tableGUI */
		$this->plugin->includeClass('tables/class.ilExteStatTableGUI.php');
		$tableGUI = ilExteStatTableGUI::_create('ilExteStatDetailsTableGUI', $this, 'showTestDetails');
		$tableGUI->prepareData($evaluation->getDetails());
		$tableGUI->setTitle($evaluation->getShortTitle());
		$tableGUI->setDescription($evaluation->getDescription());

		$legendGUI = ilExteStatTableGUI::_create('ilExteStatLegendTableGUI', $this, 'showTestDetails');
		$this->tpl->setContent($customHTML . $chartHTML . $tableGUI->getHTML() . $legendGUI->getHTML());
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

		if ($this->ctrl->getCmd() == 'applyFilter')
		{
			$tableGUI->resetOffset();
			$tableGUI->writeFilterToSession();
		}
		elseif (($this->ctrl->getCmd() == 'applyFilter'))
		{
			$tableGUI->resetOffset();
			$tableGUI->resetFilter();
		}

		$tableGUI->prepareData();

		$legendGUI = ilExteStatTableGUI::_create('ilExteStatLegendTableGUI', $this, 'showQuestionsOverview');
		$this->tpl->setContent($tableGUI->getHTML() . $legendGUI->getHTML());
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
        $chartHTML = '';
        if ($evaluation->providesChart())
        {
            $chart = $evaluation->getChart($_GET['qid']);
            $chartHTML = $chart->getHTML();
        }

        /** @var  ilExteStatDetailsTableGUI $tableGUI */
		$this->plugin->includeClass('tables/class.ilExteStatTableGUI.php');
		$tableGUI = ilExteStatTableGUI::_create('ilExteStatDetailsTableGUI', $this, 'showQuestionDetails');
		$tableGUI->prepareData($evaluation->getDetails($_GET['qid']));
		$tableGUI->setTitle($this->statObj->getSourceData()->getQuestion($_GET['qid'])->question_title);
		$tableGUI->setDescription($evaluation->getTitle());

		$legendGUI = ilExteStatTableGUI::_create('ilExteStatLegendTableGUI', $this, 'showQuestionDetails');
        $this->tpl->setContent($chartHTML . $tableGUI->getHTML() . $legendGUI->getHTML());
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
		$export_type = new ilSelectInputGUI($lng->txt('type'), 'export_type');
		$options = array(
			'excel_overview' => $this->plugin->txt('exp_type_excel_overviews'),
			'excel_details' => $this->plugin->txt('exp_type_excel_details'),
			'csv_test' => $this->plugin->txt('exp_type_csv_test'),
			'csv_questions' => $this->plugin->txt('exp_type_csv_questions'),
		);
		$export_type->setOptions($options);
		$export_type->setValue($this->plugin->getUserPreference('export_type', 'excel_overview'));
		$ilToolbar->addInputItem($export_type, true);

		require_once 'Services/UIComponent/Button/classes/class.ilSubmitButton.php';
		$button = ilSubmitButton::getInstance();
		$button->setCommand('exportEvaluations');
		$button->setCaption('export');
		$button->getOmitPreventDoubleSubmission();
		$ilToolbar->addButtonInstance($button);

		$ilToolbar->addSeparator();

		$this->plugin->includeClass('models/class.ilExteStatSourceData.php');
		require_once 'Services/Form/classes/class.ilSelectInputGUI.php';
		$pass_selection = new ilSelectInputGUI($this->plugin->txt('evaluated_pass'), 'evaluated_pass');
		$options = array(
			ilExteStatSourceData::PASS_SCORED => $this->plugin->txt('pass_scored'),
			ilExteStatSourceData::PASS_BEST => $this->plugin->txt('pass_best'),
			ilExteStatSourceData::PASS_LAST => $this->plugin->txt('pass_last'),
		);
		$pass_selection->setOptions($options);
		$pass_selection->setValue($this->plugin->getUserPreference('evaluated_pass', ilExteStatSourceData::PASS_SCORED));
		$ilToolbar->addInputItem($pass_selection, true);

		require_once 'Services/UIComponent/Button/classes/class.ilSubmitButton.php';
		$button = ilSubmitButton::getInstance();
		$button->setCommand('selectEvaluatedPass');
		$button->setCaption('select');
		$button->getOmitPreventDoubleSubmission();
		$ilToolbar->addButtonInstance($button);

		$ilToolbar->addSeparator();

		$button = ilSubmitButton::getInstance();
		$button->setCommand('flushCache');
		$button->setCaption($this->plugin->txt('flush_cache'), false);
		$button->getOmitPreventDoubleSubmission();
		$ilToolbar->addButtonInstance($button);



		require_once 'Services/Form/classes/class.ilHiddenInputGUI.php';
		$levelField = new ilHiddenInputGUI('level');
		$levelField->setValue($level);
		$ilToolbar->addInputItem($levelField);
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
		$this->plugin->includeClass("export/class.ilExteStatExport.php");

		// set the parameters based on the selection
		$this->plugin->setUserPreference('export_type', ilUtil::secureString($_POST['export_type']));
		switch ($_POST['export_type'])
		{
			case 'csv_test':
				$name = 'test_statistics';
				$suffix = 'csv';
				$type = ilExteStatExport::TYPE_CSV;
				$level = ilExtendedTestStatistics::LEVEL_TEST;
				$details = false;
				break;

			case 'csv_questions':
				$name = 'questions_statistics';
				$suffix = 'csv';
				$type = ilExteStatExport::TYPE_CSV;
				$level = ilExtendedTestStatistics::LEVEL_QUESTION;
				$details = false;
				break;

			case 'excel_details':
				$name = 'detailed_statistics';
				$suffix = 'xlsx';
				$type = ilExteStatExport::TYPE_EXCEL;
				$level = '';
				$details = true;
				break;

			case 'excel_overview':
			default:
				$name = 'statistics';
				$suffix = 'xlsx';
				$type = ilExteStatExport::TYPE_EXCEL;
				$level = '';
				$details = false;
				break;
		}

		// add a suffix for the pass selection
		$this->plugin->includeClass('models/class.ilExteStatSourceData.php');
		switch ($this->plugin->getUserPreference('evaluated_pass'))
		{
			case ilExteStatSourceData::PASS_LAST:
				$name .= '_last_pass';
				break;
			case ilExteStatSourceData::PASS_BEST:
				$name .= '_best_pass';
				break;
		}

		// write the export file
		require_once('Modules/Test/classes/class.ilTestExportFilename.php');
		$filename = new ilTestExportFilename($this->testObj);
		$export = new ilExteStatExport($this->plugin, $this->statObj, $type, $level, $details);
		$export->buildExportFile($filename->getPathname($suffix, $name));

		// build the success message with download link for the file
		$this->ctrl->setParameter($this, 'name', $name);
		$this->ctrl->setParameter($this, 'suffix', $suffix);
		$this->ctrl->setParameter($this, 'time', $filename->getTimestamp());
		$link = $this->ctrl->getLinkTarget($this, 'deliverExportFile');
		ilUtil::sendSuccess(sprintf($this->plugin->txt('export_written'), $link), true);
		$this->ctrl->clearParameters($this);

		// show the screen from which the export was started
		switch ($_POST['level'])
		{
			case ilExtendedTestStatistics::LEVEL_QUESTION:
				$this->ctrl->redirect($this, 'showQuestionsOverview');
				break;
			default:
				$this->ctrl->redirect($this, 'showTestOverview');
		}
	}

	/**
	 * Deliver a previously generated export file
	 */
	protected function deliverExportFile()
	{
		// sanitize parameters
		$name = preg_replace("/[^a-z_]/", '', $_GET['name']);
		$suffix = preg_replace("/[^a-z]/", '', $_GET['suffix']);
		$time = preg_replace("/[^0-9]/", '', $_GET['time']);

		require_once('Modules/Test/classes/class.ilTestExportFilename.php');
		$filename = new ilTestExportFilename($this->testObj);
		$path = $filename->getPathname($suffix, $name);
		$path = str_replace($filename->getTimestamp(), $time, $path);

		if (is_file($path))
		{
			ilUtil::deliverFile($path, basename($path));
		}
		else
		{
			ilUtil::sendFailure($this->plugin->txt('export_not_found'), true);
			$this->ctrl->redirect($this);
		}
	}

	/**
	 * Set the evaluated pass
	 */
	protected function selectEvaluatedPass()
	{
		$this->plugin->setUserPreference('evaluated_pass', ilUtil::secureString($_POST['evaluated_pass']));

		// show the screen from which the export was started
		switch ($_POST['level'])
		{
			case ilExtendedTestStatistics::LEVEL_QUESTION:
				$this->ctrl->redirect($this, 'showQuestionsOverview');
				break;
			default:
				$this->ctrl->redirect($this, 'showTestOverview');
		}

	}

	/**
	 * Flush the cache
	 */
	protected function flushCache()
	{
		$this->statObj->flushCache();

		ilUtil::sendSuccess($this->plugin->txt('cache_flushed'), true);

		// show the screen from which the export was started
		switch ($_POST['level'])
		{
			case ilExtendedTestStatistics::LEVEL_QUESTION:
				$this->ctrl->redirect($this, 'showQuestionsOverview');
				break;
			default:
				$this->ctrl->redirect($this, 'showTestOverview');
		}
	}
}

<?php

/* Copyright (c) 2016 Institut fuer Lern-Innovation, GPLv3, see docs/LICENSE */

include_once("./Services/UIComponent/classes/class.ilUIHookPluginGUI.php");

/**
 * User interface hook class
 *
 * @author Fred Neumann <fred.neumann@fau.de>
 * @version $Id$
 * @ingroup ServicesUIComponent
 */
class ilExtendedTestStatisticsUIHookGUI extends ilUIHookPluginGUI
{
	/**
	 * Modify GUI objects, before they generate ouput
	 *
	 * @param string $a_comp component
	 * @param string $a_part string that identifies the part of the UI that is handled
	 * @param string $a_par array of parameters (depend on $a_comp and $a_part)
	 */
	function modifyGUI($a_comp, $a_part, $a_par = array())
	{
		/** @var ilCtrl $ilCtrl */
		/** @var ilTabsGUI $ilTabs */
		global $ilCtrl, $ilTabs;

		switch ($a_part)
		{
			// case 'tabs':
			case 'sub_tabs':

				if ($ilCtrl->getCmdClass() == 'iltestevaluationgui'
					and in_array($ilCtrl->getCmd(), array('outEvaluation','eval_a','singleResults')))
				{
                    $ilTabs->removeSubTab('tst_results_aggregated');
					$ilCtrl->saveParameterByClass('ilExtendedTestStatisticsPageGUI','ref_id');

					// we need to use the deprecated method because evaluation sub tabs work with automatic activation
					// with addSubTab the new sub tabs would always be activated
					$ilTabs->addSubTabTarget(
						$this->plugin_object->txt('test_results'), // text is also the aubtab id
						$ilCtrl->getLinkTargetByClass(array('ilUIPluginRouterGUI','ilExtendedTestStatisticsPageGUI'), 'showTestOverview'),
						array('showTestOverview','showTestDetails'), // commands to be recognized for activation
						'ilExtendedTestStatisticsPageGUI', 	// cmdClass to be recognized activation
						'', 								// frame
						false, 								// manual activation
						true								// text is direct, not a language var
					);

					$ilTabs->addSubTabTarget(
						$this->plugin_object->txt('questions_results'), // text is also the aubtab id
						$ilCtrl->getLinkTargetByClass(array('ilUIPluginRouterGUI','ilExtendedTestStatisticsPageGUI'), 'showQuestionsOverview'),
						array('showQuestionsOverview', 'showQuestionDetails'), 	// commands to be recognized for activation
						'ilExtendedTestStatisticsPageGUI', 	// cmdClass to be recognized activation
						'', 								// frame
						false, 								// manual activation
						true								// text is direct, not a language var
					);

					// save the tabs for reuse on the plugin pages
					// (these do not have the test gui as parent)
					// not nice, but effective
					$_SESSION['ExtendedTestStatistics']['TabTarget'] = $ilTabs->target;
					$_SESSION['ExtendedTestStatistics']['TabSubTarget'] = $ilTabs->sub_target;
				}

				if ($ilCtrl->getCmdClass()  == 'ilextendedteststatisticspagegui')
				{
					// reuse the tabs that were saved from the test gui
					if (isset($_SESSION['ExtendedTestStatistics']['TabTarget']))
					{
						$ilTabs->target = $_SESSION['ExtendedTestStatistics']['TabTarget'];
					}
					if (isset($_SESSION['ExtendedTestStatistics']['TabSubTarget']))
					{
						$ilTabs->sub_target = $_SESSION['ExtendedTestStatistics']['TabSubTarget'];
					}

					// this works because the tabs are rendered after the sub tabs
					$ilTabs->activateTab('statistics');
				}

				break;

			default:
				break;
		}
	}

}
?>
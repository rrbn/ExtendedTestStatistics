<?php
/* Copyright (c) 1998-2013 ILIAS open source, Extended GPL, see docs/LICENSE */


include_once('./Services/Table/classes/class.ilTable2GUI.php');

/**
 * Class ilExteStatTestOverviewTableGUI
 */
class ilExteStatTestOverviewTableGUI extends ilTable2GUI
{
	/**
	 * Constructor
	 *
	 * @access public
	 * @param
	 * @return
	 */
	public function __construct($a_parent_obj, $a_parent_cmd)
	{
		parent::__construct($a_parent_obj, $a_parent_cmd);

		global $lng, $ilCtrl;

		$this->lng = $lng;
		$this->ctrl = $ilCtrl;
		$this->plugin = $a_plugin;
	
		$this->setFormName('test_overview');
		$this->setTitle($this->lng->txt('tst_results_aggregated'));
		$this->setStyle('table', 'fullwidth');
		$this->addColumn($this->lng->txt("title"));
		$this->addColumn($this->lng->txt("value"));
		$this->addColumn($this->lng->txt("comment"));
		$this->addColumn($this->lng->txt("actions"));

		$this->setRowTemplate("tpl.il_exte_stat_test_overview_row.html", $a_parent_obj->getPlugin()->getDirectory());
		$this->setFormAction($this->ctrl->getFormAction($a_parent_obj, $a_parent_cmd));
		
		$this->disable('sort');
		$this->enable('header');
		$this->disable('select_all');
	}

	/**
	 * fill row 
	 *
	 * @access public
	 * @param
	 * @return
	 */
	public function fillRow($data)
	{
		/** @var ilExteStatValue $value */
		$value = $data['value'];

		$this->tpl->setVariable('TITLE', $data['title']);
		$this->tpl->setVariable('VALUE', $value->value);
		$this->tpl->setVariable('COMMNENT', $value->comment);
	}
}
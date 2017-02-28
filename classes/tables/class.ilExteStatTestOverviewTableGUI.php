<?php
/* Copyright (c) 1998-2013 ILIAS open source, Extended GPL, see docs/LICENSE */

/**
 * Class ilExteStatTestOverviewTableGUI
 */
class ilExteStatTestOverviewTableGUI extends ilExteStatTableGUI
{
    /**
	 * Constructor
	 */
	public function __construct($a_parent_obj, $a_parent_cmd)
	{
        $this->setId('ilExteStatTestOverview');
        $this->setPrefix('ilExteStatTestOverview');

        parent::__construct($a_parent_obj, $a_parent_cmd);

		$this->setFormName('test_overview');
		$this->setTitle($this->lng->txt('tst_results_aggregated'));
		$this->setStyle('table', 'fullwidth');
		$this->addColumn($this->lng->txt("title"));
		$this->addColumn($this->lng->txt("value"));
		$this->addColumn($this->lng->txt("comment"));
		$this->addColumn('');

		$this->setRowTemplate("tpl.il_exte_stat_test_overview_row.html", $this->plugin->getDirectory());
		$this->setFormAction($this->ctrl->getFormAction($a_parent_obj, $a_parent_cmd));
		
		$this->disable('sort');
		$this->enable('header');
		$this->disable('select_all');

		$this->setEnableNumInfo(false);
		$this->setExternalSegmentation(true);
	}

    /**
     * Prepare the data to be shown
     */
    public function prepareData()
    {
        global $lng;

        $data = array();

        /** @var ilExteStatValue  $value */
        foreach ($this->statObj->getSourceData()->getBasicTestValues() as $value_id => $value)
        {
            array_push($data,
                array(
                    'title' => $lng->txt($value_id),
                    'description' => '',
                    'value' => $value,
                    'details' => null
                ));
        }

		/**
		 * @var string $class
		 * @var  ilExteEvalTest|ilExteEvalQuestion $evaluation
		 */
		foreach ($this->statObj->getEvaluations() as $class => $evaluation)
        {
            array_push($data,
                array(
                    'title' => $evaluation->getTitle(),
                    'description' => $evaluation->getDescription(),
                    'value' => $evaluation->providesValue() ? $evaluation->getValue() : null,
                    'details' => $evaluation->providesDetails() ? $class : null
                ));
        }

		$this->setLimit(count($data));
        $this->setData($data);
    }


	/**
	 * fill row 
	 *
	 * @access public
	 * @param	array	$data
	 */
	protected function fillRow($data)
	{
		$title = ilExteStatValue::_create($data['title'],ilExteStatValue::TYPE_TEXT,0,$data['description']);
		$value = isset($data['value']) ? $data['value'] : new ilExteStatValue();

        $this->valueGUI->setShowComment(true);
		$this->tpl->setVariable('TITLE',$this->valueGUI->getHTML($title));
        $this->valueGUI->setShowComment(false);
		$this->tpl->setVariable('VALUE', $this->valueGUI->getHTML($value));
		$this->tpl->setVariable('COMMENT', $value->comment);

		if (!empty($data['details']))
		{
			$this->ctrl->setParameter($this->parent_obj, 'details', $data['details']);
			$this->tpl->setCurrentBlock('link');
			$this->tpl->setVariable('LINK_NAME', $this->ctrl->getLinkTarget($this->parent_obj, 'showTestDetails'));
			$this->tpl->setVariable('LINK_TXT', $this->plugin->txt('show_details'));
			$this->tpl->parseCurrentBlock();
		}
	}
}
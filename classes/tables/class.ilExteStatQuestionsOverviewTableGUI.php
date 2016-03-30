<?php
/* Copyright (c) 1998-2013 ILIAS open source, Extended GPL, see docs/LICENSE */


include_once('./Services/Table/classes/class.ilTable2GUI.php');

/**
 * Class ilExteStatQuestionsOverviewTableGUI
 */
class ilExteStatQuestionsOverviewTableGUI extends ilTable2GUI
{
    /**
     * @var ilExtendedTestStatistics|null
     */
    protected $statObj;

    /**
     * @var ilExteStatValueGUI
     */
    protected $valueGUI;


    /**
	 * Constructor
	 * @param   ilExtendedTestStatisticsPageGUI $a_parent_obj
     * @param   string                          $a_parent_cmd
	 * @return
	 */
	public function __construct($a_parent_obj, $a_parent_cmd)
	{
        global $lng, $ilCtrl;

        $this->lng  = $lng;
        $this->ctrl = $ilCtrl;
        $this->plugin = $a_parent_obj->getPlugin();
        $this->statObj = $a_parent_obj->getStatisticsObject();

        $this->plugin->includeClass('views/class.ilExteStatValueGUI.php');
        $this->valueGUI = new ilExteStatValueGUI($this->plugin);
        $this->valueGUI->setShowComment(true);


        $this->setId('ilExteStatQuestionsOverview');
        $this->setPrefix('ilExteStatQuestionsOverview');

        parent::__construct($a_parent_obj, $a_parent_cmd);

        $this->setFormName('questions_overview');
		$this->setTitle($this->plugin->txt('questions_results'));
		$this->setStyle('table', 'fullwidth');

		$this->addColumn($this->lng->txt("question_id"), 'qid');
		$this->addColumn($this->lng->txt("question_title"), 'title');
        foreach ($this->getSelectableColumns() as $colid => $settings)
        {
            if ($this->isColumnSelected($colid))
            {
                $this->addColumn($settings['txt'], in_array($colid, array('type_label','answers','points','percentage')) ? $colid : '');
            }
        }
        $this->addColumn('');

		$this->setRowTemplate("tpl.il_exte_stat_questions_overview_row.html", $a_parent_obj->getPlugin()->getDirectory());
		$this->setFormAction($this->ctrl->getFormAction($a_parent_obj, $a_parent_cmd));

		$this->setDefaultOrderField("title");
		$this->setDefaultOrderDirection("asc");
		$this->enable('sort');
		$this->enable('header');
		$this->disable('select_all');
	}

    /**
     * Get selectable columns
     */
    public function getSelectableColumns()
    {
        // basic question values
       $columns = array(
           'type_label' => array('txt' => $this->lng->txt("question_type"), 'default' => false),
           'answers' => array('txt' => $this->lng->txt("answers"), 'default' => true),
           'points' => array('txt' => $this->lng->txt("points"), 'default' => false),
           'percentage' => array('txt' => $this->lng->txt("percentage"), 'default' => false),
       );

       foreach ($this->statObj->getEvaluations(ilExtendedTestStatistics::PROVIDES_VALUE) as $id => $evaluation)
       {
           $columns[$id] = array('txt' => $evaluation->getShortTitle(), 'default'=> false);
       }
       return $columns;
    }

    /**
     * Prepare the data to be shown
     * This only adds the basic questrion values that will be used for filtering and sorting
     * The more complex evaluations are only applied for the filled rows of the page
     */
    public function prepareData()
    {
        $data = array();
        foreach ($this->statObj->getSourceData()->getBasicQuestionValues() as $question_id => $values)
        {
            $row = array();

            /** @var ilExteStatValue  $value */
            foreach ($values as $value_id => $value)
            {
                $row[$value_id] = $value->value;
            }
            $data[] = $row;
        }
        $this->setData($data);
    }


    /**
	 * Should this field be sorted numeric?
	 * @return    boolean        numeric ordering; default is false
	 */
	function numericOrdering($a_field)
	{
		switch($a_field)
		{
			case 'points':
			case 'qid':
			case 'percentage':
				return true;

			default:
				return false;
		}
	}

	/**
	 * fill row
	 * @access public
	 * @param
	 * @return
	 */
	public function fillRow($data)
	{
        $this->tpl->setCurrentBlock('column');
        $this->tpl->setVariable('CONTENT', $data['qid']);
        $this->tpl->parseCurrentBlock();

        $this->tpl->setCurrentBlock('column');
        $this->tpl->setVariable('CONTENT', $data['title']);
        $this->tpl->parseCurrentBlock();

        foreach ($this->getSelectedColumns() as $colid)
        {
            $content ='';
            switch ($colid)
            {
                // basic question values
                case 'type_label':
                case 'answers':
                    $content = ilUtil::prepareFormOutput($data[$colid]);
                    break;
                case 'points':
                    $content = sprintf('%.2f', $data['points_reached']) . ' ' . strtolower($this->lng->txt('of')) . ' ' . sprintf('%.2f', $data['points_max']);
                    break;
                case 'percentage':
                    $content = sprintf('%.2f', $data['percentage']) . '%';
                    break;

                // values from evaluations
                default:
                    $evaluation = $this->statObj->getEvaluation($colid);
                    if (isset($evaluation) && $evaluation::_isQuestionTypeAllowed($data['type']) && $evaluation::_providesValue())
                    {
                        $value = $evaluation->calculateValue($data['qid']);
                        $content = $this->valueGUI->getHTML($value);
                    }
                    break;
            }
            $this->tpl->setCurrentBlock('column');
            $this->tpl->setVariable('CONTENT', $content);
            $this->tpl->parseCurrentBlock();
        }

        // evaluations with details
        $details = $this->statObj->getEvaluations(ilExteEvalBase::_providesDetails(), $data['type']);

        if (!empty($details))
        {
            // show action menu
            include_once './Services/UIComponent/AdvancedSelectionList/classes/class.ilAdvancedSelectionListGUI.php';
            $list = new ilAdvancedSelectionListGUI();
            $list->setSelectionHeaderClass('small');
            $list->setItemLinkClass('small');
            $list->setId('actl_'.$data['qid'].'_'.$this->getId());
            $list->setListTitle($this->plugin->txt('show_details'));

            foreach($details as $evaluation)
            {
                $this->ctrl->setParameter($this->parent_obj, 'qid', $data['qid']);
                $this->ctrl->setParameter($this->parent_obj, 'details', $evaluation::_getId());

                $list->addItem($evaluation->getTitle(), '', $this->ctrl->getLinkTarget($this->parent_obj,'showQuestionDetails'));
            }
            $content = $list->getHTML();
        }
        else
        {
            $content = '';
        }

        $this->tpl->setCurrentBlock('column');
        $this->tpl->setVariable('CONTENT', $content);
        $this->tpl->parseCurrentBlock();

    }
}
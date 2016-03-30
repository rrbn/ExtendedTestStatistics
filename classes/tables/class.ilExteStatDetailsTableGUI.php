<?php
/* Copyright (c) 1998-2013 ILIAS open source, Extended GPL, see docs/LICENSE */


include_once('./Services/Table/classes/class.ilTable2GUI.php');

/**
 * Class ilExteStatDetailsTableGUI
 */
class ilExteStatDetailsTableGUI extends ilTable2GUI
{
    /**
     * @var ilExteStatDetails
     */
    protected $details;

    /**
     * @var ilExteStatValueGUI
     */
    protected $valueGUI;

    /**
	 * Constructor
	 *
	 * @access public
	 * @param   object      parent gui object
	 * @return  string      current command
	 */
	public function __construct($a_parent_obj, $a_parent_cmd)
	{
        global $lng, $ilCtrl;

        $this->lng = $lng;
        $this->ctrl = $ilCtrl;
        $this->plugin = $a_parent_obj->getPlugin();
        $this->parent_obj = $a_parent_obj;
        $this->parent_cmd = $a_parent_cmd;

        $this->plugin->includeClass('views/class.ilExteStatValueGUI.php');
        $this->valueGUI = new ilExteStatValueGUI($this->plugin);
        $this->valueGUI->setShowComment(true);

        $this->setStyle('table', 'fullwidth');
        $this->setRowTemplate("tpl.il_exte_stat_details_row.html", $this->plugin->getDirectory());
        $this->setFormAction($this->ctrl->getFormAction($a_parent_obj, $a_parent_cmd));

        $this->enable('header');
        $this->disable('select_all');
    }


    /**
     * Prepare the data to be shown
     * @param ilExteStatDetails $a_details
     */
    public function prepareData(ilExteStatDetails $a_details)
    {
        $this->details = $a_details;

        $this->setId('ilExteStatDetails_'.$this->details->id);
        $this->setPrefix('ilExteStatDetails_'.$this->details->id);
        $this->setFormName('ilExteStatDetails_'.$this->details->id);

        // we have to call the parent constructor here
        // because it needs the ids to determine the sorting
        parent::__construct($this->parent_obj, $this->parent_cmd);

        // Header and columns
        if(!empty($this->details->title))
        {
            $this->setTitle($this->details->title);
        }
        if (!empty($this->details->description))
        {
            $this->setDescription($this->details->description);
        }
        foreach ($this->details->columns as $column)
        {
            $this->addColumn($column->title, $column->sort ? 'sort_'.$column->name : '', '', false, '', $column->comment);
        }

        // Row data
        $data = array();
        foreach ($this->details->rows as $rownum => $values)
        {
            foreach ($this->details->columns as $column)
            {
                if (!empty($column->sort) && isset($values[$column->name]))
                {
                    // add scalar value for sorting
                    $values['sort_'.$column->name] = $values[$column->name]->value;
                }
            }
            $data[] = $values;
        }
        $this->setData($data);
    }

    /**
     * Should this field be sorted numeric?
     * @return    boolean        numeric ordering; default is false
     */
    function numericOrdering($a_field)
    {
        foreach ($this->details->columns as $column)
        {
            if ($column->name == $a_field)
            {
                if ($column->sort == ilExteStatColumn::SORT_NUMBER)
                {
                    return true;
                }
                else
                {
                    return false;
                }
            }
        }
    }


    /**
	 * fill row 
	 */
	protected function fillRow($data)
	{
        foreach ($this->details->columns as $column)
        {
            if (isset($data[$column->name]))
            {
                $content = $this->valueGUI->getHTML($data[$column->name]);
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
}
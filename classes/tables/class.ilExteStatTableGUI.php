<?php

include_once('./Services/Table/classes/class.ilTable2GUI.php');

abstract class ilExteStatTableGUI extends ilTable2GUI
{
	/**
	 * @var object $parent_obj
	 */
	protected $parent_obj;

	/**
	 * @var string $parent_cmd
	 */
	protected $parent_cmd;

	/**
	 * @var ilExtendedTestStatisticsPlugin|null
	 */
	protected $plugin;

	/**
	 * @var ilExtendedTestStatistics|null
	 */
	protected $statObj;

	/**
	 * @var ilExteStatValueGUI
	 */
	protected $valueGUI;


	/**
	 * ilExteStatTableGUI constructor.
	 * @param object	$a_parent_obj
	 * @param string 	$a_parent_cmd
	 */
	public function __construct($a_parent_obj, $a_parent_cmd)
	{
		global $lng, $ilCtrl;

		$this->lng = $lng;
		$this->ctrl = $ilCtrl;
		$this->parent_obj = $a_parent_obj;
		$this->parent_cmd = $a_parent_cmd;
		$this->plugin = $a_parent_obj->getPlugin();
		$this->statObj = $a_parent_obj->getStatisticsObject();


		if (!isset($this->valueGUI))
		{
			$this->plugin->includeClass('views/class.ilExteStatValueGUI.php');
			$this->valueGUI = new ilExteStatValueGUI($this->plugin);
			$this->valueGUI->setShowComment(true);
		}

		if (isset($this->id))
		{
			// wait for a second call if id is set after creation
			parent::__construct($a_parent_obj, $a_parent_cmd);
		}
	}

	/**
	 * @param string	$a_class
	 * @param object	$a_parent_obj
	 * @param string	$a_parent_cmd
	 * @return ilExteStatTableGUI
	 */
	public static function _create($a_class, $a_parent_obj, $a_parent_cmd)
	{
		/** @var ilExtendedTestStatisticsPlugin $plugin */
		$plugin = $a_parent_obj->getPlugin();
		$plugin->includeClass('tables/class.'.$a_class.'.php');

		return new $a_class($a_parent_obj, $a_parent_cmd);
	}
}
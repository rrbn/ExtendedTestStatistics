<?php

/**
 * GUI for showing statistical values
 */
class ilExteStatValueGUI
{
	/**
	 * @var ilExtendedTestStatisticsPlugin
	 */
	protected $plugin;

	/**
	 * @var bool	comments should be shown as tooltip
	 */
	protected $show_comment = true;

	/**
	 * ilExteStatValueGUI constructor.
	 * @param ilExtendedTestStatisticsPlugin		the plugin object
	 */
	public function __construct($a_plugin)
	{
		$this->plugin = $a_plugin;
	}

	/**
	 * Set whether comments should be shown
	 * @param bool $a_show_comment
	 */
	public function setShowComment($a_show_comment)
	{
		$this->show_comment = $a_show_comment;
	}

	/**
	 * Get the rendered HTML for a value
	 * @param ilExteStatValue $value
	 * @return string
	 */
	public function getHTML(ilExteStatValue $value)
	{
		global $lng;

		$template = $this->plugin->getTemplate('tpl.il_exte_stat_value.html');

		// alert
		if ($value->alert != ilExteStatValue::ALERT_NONE)
		{
			$template->setVariable('SRC_ALERT', $this->plugin->getImagePath('alert_'.$value->alert.'.svg'));
			if (isset($value->value) and $value->type == ilExteStatValue::TYPE_ALERT)
			{
				$template->setVariable('ALT_ALERT', ilUtil::prepareFormOutput($value->value));
			}
			else
			{
				$template->setVariable('ALT_ALERT', $this->plugin->txt('alert_'.$value->alert));
			}
		}

		// value
		if (isset($value->value))
		{
			switch ($value->type)
			{
				case ilExteStatValue::TYPE_ALERT:
					// alert is already set
					break;

				case ilExteStatValue::TYPE_TEXT:
					$template->setVariable('VALUE', ilUtil::prepareFormOutput($value->value));
					break;

				case ilExteStatValue::TYPE_NUMBER:
					$template->setVariable('VALUE', ilUtil::prepareFormOutput(round($value->value, $value->precision)));
					break;

				case ilExteStatValue::TYPE_DURATION:
					$diff_seconds = $value->value;
					$diff_hours    = floor($diff_seconds/3600);
					$diff_seconds -= $diff_hours   * 3600;
					$diff_minutes  = floor($diff_seconds/60);
					$diff_seconds -= $diff_minutes * 60;
					$template->setVariable('VALUE', sprintf("%02d:%02d:%02d", $diff_hours, $diff_minutes, $diff_seconds));
					break;

				case ilExteStatValue::TYPE_DATETIME:
					if ($value->value instanceof ilDateTime)
					{
						$template->setVariable('VALUE', ilDatePresentation::formatDate($value->value));
					}
					break;
				case ilExteStatValue::TYPE_PERCENTAGE:
					$template->setVariable('VALUE', round($value->value, $value->precision). '%');
					break;

				case ilExteStatValue::TYPE_BOOLEAN:
					$template->setVariable('VALUE', $value->value ? $lng->txt('yes') : $lng->txt('no'));
					break;
			}
		}

		// comment
		if ($this->show_comment && !empty($value->comment))
		{
			$comment_id = rand(100000,999999);
			require_once("Services/UIComponent/Tooltip/classes/class.ilTooltipGUI.php");
			ilTooltipGUI::addTooltip('ilExteStatComment'.$comment_id, $value->comment);
			$template->setVariable('COMMENT_ID', $comment_id);
		}

		return $template->get();
	}
}
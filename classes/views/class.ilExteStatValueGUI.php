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
	 * @param ilExtendedTestStatisticsPlugin	$a_plugin
	 */
	public function __construct($a_plugin)
	{
		$this->plugin = $a_plugin;
		$this->plugin->includeClass('models/class.ilExteStatValue.php');
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

		// value
		$content = null;
		$comment = null;
		$sign = null;
		$align = 'left';

		// value
			switch ($value->type)
			{
				case ilExteStatValue::TYPE_ALERT:
					// alert is separately set
					$content = '';
					$align = 'left';
					break;

				case ilExteStatValue::TYPE_TEXT:
					$content =  $this->textDisplay($value->value);
					$align = 'left';
					break;

				case ilExteStatValue::TYPE_NUMBER:
					$content = sprintf('%01.'.$value->precision.'f', round($value->value, $value->precision));
					$align = 'right';
					break;

				case ilExteStatValue::TYPE_DURATION:
					$diff_seconds = $value->value;
					$diff_hours    = floor($diff_seconds/3600);
					$diff_seconds -= $diff_hours   * 3600;
					$diff_minutes  = floor($diff_seconds/60);
					$diff_seconds -= $diff_minutes * 60;

					$content = sprintf("%02d:%02d:%02d", $diff_hours, $diff_minutes, $diff_seconds);
					$align = 'right';
					break;

				case ilExteStatValue::TYPE_DATETIME:
					if ($value->value instanceof ilDateTime)
					{
						$content = ilDatePresentation::formatDate($value->value);
						$align = 'right';
					}
					break;

				case ilExteStatValue::TYPE_PERCENTAGE:
					$content = sprintf('%01.'.$value->precision.'f', round($value->value, $value->precision)). '%';
					$align = 'right';
					break;

				case ilExteStatValue::TYPE_BOOLEAN:
					$content = ($value->value ? $lng->txt('yes') : $lng->txt('no'));
					$align = 'left';
					break;

				default:
					$content = '';
					$align = 'left';
			}


		// revert casting null to 0 etc.
		if (!isset($value->value))
		{
			$content = '';
		}

		// alert and comment
		if ($value->alert != ilExteStatValue::ALERT_NONE)
		{
			$sign = $value->alert;
			$comment = $this->plugin->txt('alert_'.$value->alert);
		}
		if ($this->show_comment && !empty($value->comment))
		{
			$sign = !empty($sign) ? $sign : 'comment';
			$comment = $value->comment;
		}

		// render cell
		switch ($align)
		{
			case 'right':
				$this->renderSign($template, $sign, 'ilExteStatSignRight', $comment);
				$this->renderContent($template, $content, 'ilExteStatValueRight', $value->uncertain);
				break;
			case 'left':
			default:
				$this->renderContent($template, $content, 'ilExteStatValueLeft', $value->uncertain);
				$this->renderSign($template, $sign, 'ilExteStatSignLeft', $comment);
				break;
		}

		return $template->get();
	}

	/**
	 * Render the value content
	 * @param ilTemplate	$template
	 * @param string		$content
	 * @param string		$class
	 * @param bool			$uncertain
	 */
	protected function renderContent($template, $content, $class, $uncertain = false)
	{
		$template->setCurrentBlock($uncertain ? 'uncertain_value' : 'value');
		$template->setVariable('CONTENT', $content);
		$template->parseCurrentBlock();

		$template->setCurrentBlock('cell');
		$template->setVariable('CLASS', $class);
		$template->parseCurrentBlock();
	}

	/**
	 * Render an alert sign or comment
	 * @param ilTemplate $template
	 * @param string	$sign
	 * @param string	$class
	 * @param string 	$comment
	 */
	protected function renderSign($template, $sign, $class, $comment = "")
	{
		if (!empty($sign) && !empty($comment))
		{
			$comment_id = rand(100000,999999);
			require_once("Services/UIComponent/Tooltip/classes/class.ilTooltipGUI.php");
			ilTooltipGUI::addTooltip('ilExteStatComment'.$comment_id, $comment);

			$template->setCurrentBlock($sign);
			$template->setVariable('COMMENT_ID', $comment_id);
			$template->parseCurrentBlock();
		}

		$template->setCurrentBlock('cell');
		$template->setVariable('CLASS', $class);
		$template->parseCurrentBlock();
	}

	/**
	 * Get legend data
	 * @return array	[ ['value' => ilExteStatValue, 'description' => string], ...]
	 */
	public function getLegendData()
	{
		global $lng;

		$data = array (
			array(
				'value' => ilExteStatValue::_create('', ilExteStatValue::TYPE_TEXT, 0, '', ilExteStatValue::ALERT_GOOD),
				'description' => $this->plugin->txt('legend_alert_good')
			),
			array(
				'value' => ilExteStatValue::_create('', ilExteStatValue::TYPE_TEXT, 0, '', ilExteStatValue::ALERT_MEDIUM),
				'description' => $this->plugin->txt('legend_alert_medium')
			),
			array(
				'value' => ilExteStatValue::_create('', ilExteStatValue::TYPE_TEXT, 0, '', ilExteStatValue::ALERT_BAD),
				'description' => $this->plugin->txt('legend_alert_bad')
			),
			array(
				'value' => ilExteStatValue::_create('', ilExteStatValue::TYPE_TEXT, 0, '', ilExteStatValue::ALERT_UNKNOWN),
				'description' => $this->plugin->txt('legend_alert_unknown')
			),
			array(
				'value' => ilExteStatValue::_create($lng->txt('value'), ilExteStatValue::TYPE_TEXT, 0, '', ilExteStatValue::ALERT_NONE, true),
				'description' => $this->plugin->txt('legend_uncertain')
			),
			array(
				'value' => ilExteStatValue::_create($lng->txt('value'), ilExteStatValue::TYPE_TEXT, 0, $lng->txt('comment')),
				'description' => $this->plugin->txt('legend_comment')
			)
		);

		return $data;
	}


	/**
	 * Prepare a string value to be displayed in HTML
	 * @param $text
	 * @return mixed|string
	 */
	protected function textDisplay($text)
	{
		// these would be deleted by the template engine
		$text = str_replace('{','&#123;', $text);
		$text = str_replace('}','&#125;', $text);

		$text = preg_replace('/<span class="latex">(.*)<\/span>/','[tex]$1[/tex]', $text);
		$text = ilUtil::secureString($text, false);
		$text = ilUtil::insertLatexImages($text);

		return $text;
	}
}
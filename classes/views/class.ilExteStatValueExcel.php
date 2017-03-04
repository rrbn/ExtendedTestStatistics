<?php

/**
 * Excel representation of a statistical values
 */
class ilExteStatValueExcel
{
	const COLOR_GOOD = 'A0FFA0';
	const COLOR_MEDIUM = 'FFFFA0';
	const COLOR_BAD = 'FFA0A0';
	const COLOR_UNKNOWN = 'CCCCCC';
	const COLOR_NONE = 'FFFFFF';

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
	}

	/**
	 * Fill a cell with the value
	 * @param PHPExcel_Cell	$cell
	 * @param ilExteStatValue $value
	 */
	public function writeInCell($cell, ilExteStatValue $value)
	{
		$numberFormat = $cell->getStyle()->getNumberFormat();
		$alignment = $cell->getStyle()->getAlignment();

		// value
		if (isset($value->value))
		{
			switch ($value->type)
			{
				case ilExteStatValue::TYPE_ALERT:
					$cell->setValueExplicit($value->value, PHPExcel_Cell_DataType::TYPE_STRING);
					$numberFormat->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_TEXT);
					break;

				case ilExteStatValue::TYPE_TEXT:
					$cell->setValueExplicit($value->value, PHPExcel_Cell_DataType::TYPE_STRING);
					$numberFormat->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_TEXT);
					break;

				case ilExteStatValue::TYPE_NUMBER:
					$cell->setValueExplicit($value->value, PHPExcel_Cell_DataType::TYPE_NUMERIC);
					$numberFormat->setFormatCode($value->precision == 0 ?
						PHPExcel_Style_NumberFormat::FORMAT_NUMBER : PHPExcel_Style_NumberFormat::FORMAT_NUMBER_00);
					$alignment->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
					break;

				case ilExteStatValue::TYPE_DURATION:
					$cell->setValueExplicit($value->value/86400, PHPExcel_Cell_DataType::TYPE_NUMERIC);
					$numberFormat->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_DATE_TIME4);
					$alignment->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
					break;

				case ilExteStatValue::TYPE_DATETIME:
					if ($value->value instanceof ilDateTime)
					{
						$cell->setValue(PHPExcel_Shared_Date::PHPToExcel($value->value->getUnixTime()));
						$numberFormat->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_DATE_DATETIME);
						$alignment->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
						break;
					}
					break;
				case ilExteStatValue::TYPE_PERCENTAGE:
					$cell->setValueExplicit($value->value/100, PHPExcel_Cell_DataType::TYPE_NUMERIC);
					$numberFormat->setFormatCode($value->precision == 0 ?
						PHPExcel_Style_NumberFormat::FORMAT_PERCENTAGE : PHPExcel_Style_NumberFormat::FORMAT_PERCENTAGE_00);
					$alignment->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
					break;

				case ilExteStatValue::TYPE_BOOLEAN:
					$cell->setValueExplicit((bool) $value->value, PHPExcel_Cell_DataType::TYPE_BOOL);
					$numberFormat->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER);
					$alignment->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
					break;
			}
		}

		// alert color
		if ($value->alert != ilExteStatValue::ALERT_NONE)
		{
			switch($value->alert)
			{
				case ilExteStatValue::ALERT_GOOD:
					$color = self::COLOR_GOOD;
					break;
				case ilExteStatValue::ALERT_MEDIUM:
					$color = self::COLOR_MEDIUM;
					break;
				case ilExteStatValue::ALERT_BAD:
					$color = self::COLOR_BAD;
					break;
				case ilExteStatValue::ALERT_UNKNOWN:
					$color = self::COLOR_UNKNOWN;
					break;
				default:
					$color = self::COLOR_NONE;
			}

			$cell->getStyle()->applyFromArray(array('fill' => array(
				'type' => PHPExcel_Style_Fill::FILL_SOLID,
				'color' => array('rgb' => $color),
			)));
		}
	}

	/**
	 * Get the Excel comment for the value
	 * @param ilExteStatValue $value
	 * @return PHPExcel_Comment
	 */
	public function getComment(ilExteStatValue $value)
	{
		return self::createComment((string) $value->comment);
	}

	/**
	 * Create an excel comment from a text
	 * @param $text
	 * @return	PHPExcel_Comment
	 */
	public static function createComment($text)
	{
		$comment = new PHPExcel_Comment();
		$richText = new PHPExcel_RichText();
		$extElement = new PHPExcel_RichText_TextElement($text);
		$richText->addText($extElement);
		$comment->setText($richText);
		$comment->setHeight('150pt');
		$comment->setWidth('200pt');
		return $comment;
	}
}
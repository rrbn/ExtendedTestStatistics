<?php
/**
 * Copyright (c) 2016 Institut fuer Lern-Innovation, Friedrich-Alexander-Universitaet Erlangen-Nuernberg
 * GPLv2, see LICENSE
 */

/**
 * ilExtendedTestStatisticsExport Export
 *
 * @author Fred Neumann <fred.neumann@ili.fau.de>
 * @author Jesus Copado <jesus.copado@ili.fau.de>
 *
 */
class ilExtendedTestStatisticsExport
{

	/**
	 * @var ilExtendedTestStatisticsPlugin
	 */
	protected $plugin;

	/**
	 * @var ilExtendedTestStatistics
	 */
	protected $statObj;

	/**
	 * @var string
	 */
	protected $level;

	/**
	 * ilExtendedTestStatisticsExport constructor.
	 * @param ilExtendedTestStatisticsPlugin	$plugin
	 * @param ilExtendedTestStatistics			$statObj
	 * @param string	$level
	 */
	public function __construct($plugin, $statObj, $level)
	{
		$this->statObj = $statObj;
		$this->plugin  = $plugin;
		$this->level = $level;

		$this->statObj->loadSourceData();
		$this->statObj->loadEvaluations($level);
	}


	/**
	 * @param ilTestExportFilename $file_name
	 */
	public function buildExportFile(ilTestExportFilename $file_name)
	{
		//Creating Files with Charts using PHPExcel
		require_once $this->plugin->getDirectory(). '/classes/export/PHPExcel-1.8/Classes/PHPExcel.php';
		$objPHPExcel = new PHPExcel();

		// Create the first sheet with test statistics
		if ($this->level = ilExtendedTestStatistics::LEVEL_TEST)
		{
			$this->fillTestOverviewWorksheet($objPHPExcel->getActiveSheet());
		}

		$name = ($this->level == ilExtendedTestStatistics::LEVEL_TEST) ? 'test_statistics' : 'questions_statistics';
		$path = $file_name->getPathname('xlsx', $name);

		// Save XSLX file
		ilUtil::makeDirParents(dirname($path));
		$objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
		$objWriter->save($path);

		//Deliver file
		ilUtil::deliverFile($path, basename($path));
	}


	/**
	 * @param PHPExcel_Worksheet	$worksheet
	 */
	protected function fillTestOverviewWorksheet($worksheet)
	{
		global $lng;

		$worksheet->setTitle($this->plugin->txt('test_results'));

		$data = array();

		/** @var ilExteStatValue  $value */
		foreach ($this->statObj->getSourceData()->getBasicTestValues() as $value_id => $value)
		{
			array_push($data,
				array(
					'title' => $lng->txt($value_id),
					'description' => '',
					'value' => $value,
				));
		}

		/**
		 * @var string $class
		 * @var  ilExteEvalTest|ilExteEvalQuestion $evaluation
		 */
		foreach ($this->statObj->getEvaluations(ilExtendedTestStatistics::PROVIDES_VALUE) as $class => $evaluation)
		{
			array_push($data,
				array(
					'title' => $evaluation->getTitle(),
					'description' => $evaluation->getDescription(),
					'value' => $evaluation->getValue()
				));
		}

		$rownum = 0;
		$comments = array();
		foreach ($data as $row)
		{
			$rownum++;

			// title
			$worksheet->setCellValueExplicit('A'.$rownum, $row['title'],PHPExcel_Cell_DataType::TYPE_STRING);
			if (!empty($row['description']))
			{
				$comment = new PHPExcel_Comment();
				$text = new PHPExcel_RichText();
				$text->addText(new PHPExcel_RichText_TextElement($row['description']));
				$comment->setText($text);
				$comments['A'.$rownum] = $comment;
			}

			/** @var ilExteStatValue $value */
			$value = $row['value'];
			$worksheet->setCellValue('B'.$rownum, $value->value);
			if (!empty($value->comment))
			{
				$comment = new PHPExcel_Comment();
				$text = new PHPExcel_RichText();
				$text->addText(new PHPExcel_RichText_TextElement($value->comment));
				$comment->setText($text);
				$comments['B'.$rownum] = $comment;
			}
		}
		$worksheet->setComments($comments);
		$this->adjustSizes($worksheet);
	}


	/**
	 * @param PHPExcel_Worksheet	$worksheet
	 */
	protected function adjustSizes($worksheet)
	{
		foreach (range('A', $worksheet->getHighestColumn()) as $columnID)
		{
			$worksheet->getColumnDimension($columnID)->setAutoSize(true);
		}
	}
}
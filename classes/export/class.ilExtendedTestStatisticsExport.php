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
class ilExtendedTestStatisticsExport extends ilTestExportPlugin
{
	/**
	 * @return string Plugin Name
	 */
	function getPluginName()
	{
		return 'ExtendedTestStatistics';
	}

	/**
	 * @return string
	 */
	protected function getFormatIdentifier()
	{
		return 'statistics.xlsx';
	}

	/**
	 * @return string
	 */
	public function getFormatLabel()
	{
		return $this->txt('il_exte_stat_label');
	}

	/**
	 * @param ilTestExportFilename $file_name
	 */
	protected function buildExportFile(ilTestExportFilename $file_name)
	{

		// Creating Files with Charts using PHPExcel
		require_once './Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/ExtendedTestStatistics/classes/export/PHPExcel-1.8/Classes/PHPExcel.php';

		$objPHPExcel = new PHPExcel();

		// Create the first sheet with test statistics
		$test_worksheet = $objPHPExcel->getActiveSheet();

		$this->createFrameTestSheet($test_worksheet);

		$this->fillInDataTestSheet($test_worksheet);

		$this->calculateSummaryTestSheet($test_worksheet);

		// Create the second sheet with question statistics
		$question_worksheet = $objPHPExcel->getActiveSheet();

		$this->createFrameQuestionSheet($question_worksheet);

		$this->fillInDataQuestionSheet($question_worksheet);

		$this->calculateSummaryQuestionSheet($question_worksheet);

		// Save XSLX file
		ilUtil::makeDirParents ( dirname ( $file_name->getPathname ( 'xlsx', 'statistics' ) ) );
		$objWriter = PHPExcel_IOFactory::createWriter ( $objPHPExcel, 'Excel2007' );
		$objWriter->setIncludeCharts ( TRUE );
		$objWriter->save ( str_replace ( __FILE__, $file_name->getPathname ( 'xlsx', 'statistics' ), __FILE__ ) );
	}

	protected function createFrameTestSheet(PHPExcel_Worksheet $test_worksheet)
	{

	}

	protected function fillInDataTestSheet(PHPExcel_Worksheet $test_worksheet)
	{

	}

	protected function calculateSummaryTestSheet(PHPExcel_Worksheet $test_worksheet)
	{

	}

	protected function createFrameQuestionSheet(PHPExcel_Worksheet $question_worksheet)
	{

	}

	protected function fillInDataQuestionSheet(PHPExcel_Worksheet $question_worksheet)
	{

	}

	protected function calculateSummaryQuestionSheet(PHPExcel_Worksheet $question_worksheet)
	{

	}

}
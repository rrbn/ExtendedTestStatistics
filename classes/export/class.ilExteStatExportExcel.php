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
class ilExteStatExportExcel
{
	protected $headerStyle = array(
		'font' => array(
			'bold' => true
		),
		'fill' => array(
			'type' => 'solid',
			'color' => array('rgb' => 'EEEEEE'),
		)
	);


	/**
	 * @var ilExtendedTestStatisticsPlugin
	 */
	protected $plugin;

	/**
	 * @var ilExtendedTestStatistics
	 */
	protected $statObj;

	/**
	 * @var bool
	 */
	protected $withDetails = false;

	/**
	 * @var ilExteStatValueExcel
	 */
	protected $valView;


	/**
	 * ilExtendedTestStatisticsExport constructor.
	 * @param ilExtendedTestStatisticsPlugin	$plugin
	 * @param ilExtendedTestStatistics			$statObj
	 * @param bool							$withDetails
	 */
	public function __construct($plugin, $statObj, $withDetails = false)
	{
		$this->statObj = $statObj;
		$this->plugin  = $plugin;
		$this->withDetails = $withDetails;

		$this->plugin->includeClass('views/class.ilExteStatValueExcel.php');
		$this->valView = new ilExteStatValueExcel($this->plugin);
	}


	/**
	 * @param ilTestExportFilename $file_name
	 */
	public function buildExportFile(ilTestExportFilename $file_name)
	{
		//Creating Files with Charts using PHPExcel
		require_once $this->plugin->getDirectory(). '/classes/export/PHPExcel-1.8/Classes/PHPExcel.php';
		$excelObj = new PHPExcel();

		// Create the first sheets with test and questions statistics
		$this->fillTestOverview( $excelObj->getActiveSheet());
		$this->fillQuestionsOverview($excelObj->createSheet());

		if ($this->withDetails)
		{
			/** @var  ilExteEvalTest $evaluation */
			foreach ($this->statObj->getEvaluations(
				ilExtendedTestStatistics::LEVEL_TEST,
				ilExtendedTestStatistics::PROVIDES_DETAILS) as $class => $evaluation)
			{
				$this->addTestDetailsSheet($excelObj, $evaluation);
			}
		}

		$excelObj->setActiveSheetIndex(0);

		$name = 'statistics';
		$path = $file_name->getPathname('xlsx', $name);

		// Save XSLX file
		ilUtil::makeDirParents(dirname($path));
		$writerObj = PHPExcel_IOFactory::createWriter($excelObj, 'Excel2007');
		$writerObj->save($path);

		//Deliver file
		ilUtil::deliverFile($path, basename($path));
	}


	/**
	 * Fill the test overview sheet
	 * @param PHPExcel_Worksheet	$worksheet
	 */
	protected function fillTestOverview($worksheet)
	{
		global $lng;

		$data = array();
		/** @var ilExteStatValue[]  $values */
		$values = $this->statObj->getSourceData()->getBasicTestValues();
		foreach ($this->statObj->getSourceData()->getBasicTestValuesList() as $def)
		{
			array_push($data,
				array(
					'title' => $def['title'],
					'description' => $def['description'],
					'value' => $values[$def['id']],
					'details' => null
				));
		}

		/** @var  ilExteEvalTest $evaluation */
		foreach ($this->statObj->getEvaluations(
			ilExtendedTestStatistics::LEVEL_TEST,
			ilExtendedTestStatistics::PROVIDES_VALUE) as $class => $evaluation)
		{
			array_push($data,
				array(
					'title' => $evaluation->getTitle(),
					'description' => $evaluation->getDescription(),
					'value' => $evaluation->getValue()
				));
		}

		// Debug value formats
		if ($this->plugin->debugFormats())
		{
			foreach (ilExteStatValue::getTestValues() as $value)
			{
				array_push($data,
					array(
						'title' => $value->comment,
						'description' => '',
						'value' => $value,
					));
			}
		}

		$rownum = 0;
		$comments = array();
		foreach ($data as $row)
		{
			$rownum++;

			// title
			$cell = $worksheet->getCell('A'.$rownum);
			$cell->setValueExplicit($row['title'],PHPExcel_Cell_DataType::TYPE_STRING);
			$cell->getStyle()->applyFromArray($this->headerStyle);
			if (!empty($row['description']))
			{
				$comments['A'.$rownum] = ilExteStatValueExcel::createComment($row['description']);
			}

			/** @var ilExteStatValue $value */
			$value = $row['value'];
			$cell = $worksheet->getCell('B'.$rownum);
			$cell->getStyle()->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
			$this->valView->writeInCell($cell, $value);
			if (!empty($value->comment))
			{
				$comments['B'.$rownum] = $this->valView->getComment($value);
			}
		}

		$worksheet->setTitle($this->plugin->txt('test_results'));
		$worksheet->setComments($comments);
		$this->adjustSizes($worksheet);
	}

	/**
	 * Fill the questions overview sheet
	 * @param PHPExcel_Worksheet	$worksheet
	 */
	protected function fillQuestionsOverview($worksheet)
	{
		global $lng;

		$header = $this->statObj->getSourceData()->getBasicQuestionValuesList();

		/** @var  ilExteEvalQuestion $evaluation */
		$evaluations = array();
		foreach ($this->statObj->getEvaluations(
			ilExtendedTestStatistics::LEVEL_QUESTION,
			ilExtendedTestStatistics::PROVIDES_VALUE) as $class => $evaluation)
		{
			$header[] = array(
				'id' => $class,
				'title' => $evaluation->getShortTitle(),
				'description' => $evaluation->getDescription(),
			);
			$evaluations[$class] = $evaluation;
		}

		$comments = array();
		$mapping = array();
		foreach ($header as $column => $def)
		{
			$letter = PHPExcel_Cell::stringFromColumnIndex($column);
			$mapping[$def['id']] = $letter;
			$coordinate = $letter.'1';
			$cell = $worksheet->getCell($coordinate);
			$cell->setValueExplicit($def['title'], PHPExcel_Cell_DataType::TYPE_STRING);
			$cell->getStyle()->applyFromArray($this->headerStyle);
			if (!empty($def['description']))
			{
				$comments[$coordinate] = ilExteStatValueExcel::createComment($def['description']);
			}
		}

		$row = 2;
		foreach ($this->statObj->getSourceData()->getBasicQuestionValues() as $question_id => $values)
		{
			/**@var  ilExteStatValue $value */
			foreach ($values as $id => $value)
			{
				if (isset($mapping[$id]))
				{
					$coordinate = $mapping[$id].(string) $row;
					$cell = $worksheet->getCell($coordinate);
					$this->valView->writeInCell($cell, $value);
					if (!empty($value->comment))
					{
						$comments[$coordinate] = $this->valView->getComment($value);
					}
				}
			}

			/** @var  ilExteEvalQuestion $evaluation */
			foreach ($evaluations as $class => $evaluation)
			{
				$coordinate = $mapping[$class].(string) $row;
				$cell = $worksheet->getCell($coordinate);
				$value =  $evaluation->getValue($question_id);
				$this->valView->writeInCell($cell, $value);
				if (!empty($value->comment))
				{
					$comments[$coordinate] = $this->valView->getComment($value);
				}
			}

			$row++;
		}

		$worksheet->setTitle($this->plugin->txt('questions_results'));
		$worksheet->setComments($comments);
		$this->adjustSizes($worksheet, range('A', 'C'));
		$worksheet->freezePane('A2');
	}


	/**
	 * Add a sheet with details for the test
	 * @param PHPExcel	$excelObj
	 * @param ilExteEvalTest $evaluation
	 */
	protected function addTestDetailsSheet($excelObj, $evaluation)
	{
		$worksheet = $excelObj->createSheet();
		$worksheet->setTitle($evaluation->getTitle());

		$details = $evaluation->getDetails();
		if (empty($details->rows))
		{
			$worksheet->setCellValue('A1', $details->getEmptyMessage());
			return;
		}

		$col = 0;
		$comments = array();
		$mapping = array();
		foreach ($details->columns as $column)
		{
			$letter = PHPExcel_Cell::stringFromColumnIndex($col);
			$mapping[$column->name] = $letter;
			$coordinate = $letter.'1';
			$cell = $worksheet->getCell($coordinate);
			$cell->setValueExplicit($column->title, PHPExcel_Cell_DataType::TYPE_STRING);
			$cell->getStyle()->applyFromArray($this->headerStyle);
			if (!empty($column->comment))
			{
				$comments[$coordinate] = ilExteStatValueExcel::createComment($column->comment);
			}
			$col++;
		}

		$row = 2;
		foreach ($details->rows as $coldata)
		{
			/**@var  ilExteStatValue $value */
			foreach ($coldata as $name => $value)
			{
				if (isset($mapping[$name]))
				{
					$coordinate = $mapping[$name].(string) $row;
					$cell = $worksheet->getCell($coordinate);
					$this->valView->writeInCell($cell, $value);
					if (!empty($value->comment))
					{
						$comments[$coordinate] = $this->valView->getComment($value);
					}
				}
			}
			$row++;
		}

		$worksheet->setComments($comments);
		$this->adjustSizes($worksheet);
	}


	/**
	 * @param PHPExcel_Worksheet	$worksheet
	 */
	protected function adjustSizes($worksheet, $range = null)
	{
		$range = isset($range) ? $range : range('A', $worksheet->getHighestColumn());
		foreach ($range as $columnID)
		{
			$worksheet->getColumnDimension($columnID)->setAutoSize(true);
		}
	}
}
<?php
/**
 * Copyright (c) 2016 Institut fuer Lern-Innovation, Friedrich-Alexander-Universitaet Erlangen-Nuernberg
 * GPLv2, see LICENSE
 */

/**
 * Extended Test Statistics Export
 *
 * @author Fred Neumann <fred.neumann@ili.fau.de>
 * @author Jesus Copado <jesus.copado@ili.fau.de>
 *
 */
class ilExteStatExport
{
	const TYPE_EXCEL = 'excel';
	const TYPE_CSV = 'csv';

	protected $headerStyle = array(
		'font' => array(
			'bold' => true
		),
		'fill' => array(
			'type' => 'solid',
			'color' => array('rgb' => 'EEEEEE'),
		)
	);

	protected $questionStyle = array(
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


	/** @var  string Writer Type ('Excel2007' or 'CSV') */
	protected $type;



	/** @var string Evaluation Level ('test' or 'questions') */
	protected $level;

	/**
	 * @var bool
	 */
	protected $details = false;


	/**
	 * @var ilExteStatValueExcel
	 */
	protected $valView;


	/**
	 * Constructor.
	 * @param ilExtendedTestStatisticsPlugin	$plugin
	 * @param ilExtendedTestStatistics			$statObj
	 * @param string							$type
	 * @param string							$level
	 * @param bool								$details
	 */
	public function __construct($plugin, $statObj, $type = self::TYPE_EXCEL, $level = '', $details = false)
	{
		$this->statObj = $statObj;
		$this->plugin  = $plugin;
		$this->type = $type;
		$this->level = $level;
		$this->details = $details;

		$this->plugin->includeClass('views/class.ilExteStatValueExcel.php');
		$this->valView = new ilExteStatValueExcel($this->plugin);
	}


	/**
	 * Build an Excel Export file
	 * @param string	$path	full path of the file to create
	 */
	public function buildExportFile($path)
	{
		//Creating Files with Charts using PHPExcel
		require_once $this->plugin->getDirectory(). '/classes/export/PHPExcel-1.8/Classes/PHPExcel.php';
		$excelObj = new PHPExcel();

		// Create the overview sheet(s)
		switch ($this->level)
		{
			case ilExtendedTestStatistics::LEVEL_TEST:
				$this->fillTestOverview($excelObj->getActiveSheet());
				break;

			case ilExtendedTestStatistics::LEVEL_QUESTION:
				$this->fillQuestionsOverview($excelObj->getActiveSheet());
				break;

			default:
				if ($this->type == self::TYPE_EXCEL)
				{
					$this->fillTestOverview($excelObj->getActiveSheet());
					$this->fillQuestionsOverview($excelObj->createSheet());
				}
		}

		// Create the details worksheets
		if ($this->type == self::TYPE_EXCEL && $this->details == true)
		{
			if (empty($this->level || $this->level == ilExtendedTestStatistics::LEVEL_TEST))
			{
				/** @var  ilExteEvalTest $evaluation */
				foreach ($this->statObj->getEvaluations(
					ilExtendedTestStatistics::LEVEL_TEST,
					ilExtendedTestStatistics::PROVIDES_DETAILS) as $class => $evaluation)
				{
					$this->addTestDetailsSheet($excelObj, $evaluation);
				}
			}

			if (empty($this->level || $this->level == ilExtendedTestStatistics::LEVEL_QUESTION))
			{
				/** @var  ilExteEvalQuestion $evaluation */
				foreach ($this->statObj->getEvaluations(
					ilExtendedTestStatistics::LEVEL_QUESTION,
					ilExtendedTestStatistics::PROVIDES_DETAILS) as $class => $evaluation)
				{
					$this->addQuestionsDetailsSheet($excelObj, $evaluation);
				}
			}
		}

		$excelObj->setActiveSheetIndex(0);

		// Save the file
		ilUtil::makeDirParents(dirname($path));
		switch ($this->type)
		{
			case self::TYPE_EXCEL:
				/** @var PHPExcel_Writer_Excel2007 $writerObj */
				$writerObj = PHPExcel_IOFactory::createWriter($excelObj, 'Excel2007');
				$writerObj->save($path);
				break;
			case self::TYPE_CSV:
				/** @var PHPExcel_Writer_CSV $writerObj */
				$writerObj = PHPExcel_IOFactory::createWriter($excelObj, 'CSV');
				$writerObj->setDelimiter(';');
				$writerObj->setEnclosure('"');
				$writerObj->save($path);
		}
	}


	/**
	 * Fill the test overview sheet
	 * @param PHPExcel_Worksheet	$worksheet
	 */
	protected function fillTestOverview($worksheet)
	{
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
		$header = $this->statObj->getSourceData()->getBasicQuestionValuesList();

		/** @var  ilExteEvalQuestion $evaluation */
		$evaluations = array();
		foreach ($this->statObj->getEvaluations(
			ilExtendedTestStatistics::LEVEL_QUESTION,
			ilExtendedTestStatistics::PROVIDES_VALUE) as $class => $evaluation)
		{
			$header[$class] = array(
				'title' => $evaluation->getShortTitle(),
				'description' => $evaluation->getDescription(),
			);
			$evaluations[$class] = $evaluation;
		}

		$comments = array();
		$mapping = array();
		$col = 0;
		foreach ($header as $name => $def)
		{
			if (!empty($def['test_types'] && !in_array($this->statObj->getSourceData()->getTestType(), $def['test_types'])))
			{
				continue;
			}
			$letter = PHPExcel_Cell::stringFromColumnIndex($col++);
			$mapping[$name] = $letter;
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
		$worksheet->setTitle($evaluation->getShortTitle());

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
	 * Add a sheet with details for the test
	 * @param PHPExcel	$excelObj
	 * @param ilExteEvalQuestion $evaluation
	 */
	protected function addQuestionsDetailsSheet($excelObj, $evaluation)
	{
		global $lng;

		$worksheet = $excelObj->createSheet();
		$worksheet->setTitle($evaluation->getShortTitle());

		if (!$evaluation->isTestTypeAllowed())
		{
			$worksheet->setCellValue('A1', $evaluation->getMessageNotAvailableForQuestionType());
			return;
		}

		$columns = array();
		$mapping = array();
		$comments = array();
		$columns['_question_id'] = ilExteStatColumn::_create('_question_id', $lng->txt('question_id'));
		$columns['_question_title'] = ilExteStatColumn::_create('_question_title', $lng->txt('question_title'));
		$mapping['_question_id'] = 'A';
		$mapping['_question_title'] = 'B';

		$row = 2;
		$col = 2;
		foreach($this->statObj->getSourceData()->getBasicQuestionValues() as $question_id => $questionValues)
		{
			$details = $evaluation->getDetails($question_id);
			if (!empty($details->rows))
			{
				// question id
				$cell = $worksheet->getCell('A'.$row);
				$cell->getStyle()->applyFromArray($this->questionStyle);
				$this->valView->writeInCell($cell, $questionValues['question_id']);

				// question title
				$cell = $worksheet->getCell('B'.$row);
				$cell->getStyle()->applyFromArray($this->questionStyle);
				$this->valView->writeInCell($cell, $questionValues['question_title']);

				// add columns that are not yet defined
				foreach($details->columns as $column)
				{
					if (!isset($columns[$column->name]))
					{
						$letter = PHPExcel_Cell::stringFromColumnIndex($col);
						$columns[$column->name] = $column;
						$mapping[$column->name] = $letter;
						$col++;
					}
				}
				//write lines of the evaluation
				foreach ($details->rows as $rowValues)
				{
					foreach ($rowValues as $name => $value)
					{
						$coordinate = $mapping[$name].$row;
						$cell = $worksheet->getCell($coordinate);
						$this->valView->writeInCell($cell, $value);
						if (!empty($value->comment))
						{
							$comments[$coordinate] = $this->valView->getComment($value);
						}
					}
					$row++;
				}
			}
		}

		// write the header row with column titles
		// can be written when all columns are known
		foreach ($columns as $column)
		{
			$coordinate = $coordinate = $mapping[$column->name].'1';
			$cell = $worksheet->getCell($coordinate);
			$cell->setValueExplicit($column->title, PHPExcel_Cell_DataType::TYPE_STRING);
			$cell->getStyle()->applyFromArray($this->headerStyle);
			if (!empty($column->comment))
			{
				$comments[$coordinate] = ilExteStatValueExcel::createComment($column->comment);
			}
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
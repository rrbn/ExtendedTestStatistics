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
		$this->withDetails = false;

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
		$this->fillQuestionsOverview( $excelObj->createSheet());

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
		foreach ($this->statObj->getSourceData()->getBasicTestValues() as $value_id => $value)
		{
			array_push($data,
				array(
					'title' => $lng->txt($value_id),
					'description' => '',
					'value' => $value,
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
				$comments['A'.$rownum] = $this->createComment($row['description']);
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

		$header = $this->getQuestionOverviewHeader();

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
			if (!empty($def['comment']))
			{
				$comments[$coordinate] = $this->createComment($def['comment']);
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
			foreach ($this->statObj->getEvaluations(
				ilExtendedTestStatistics::LEVEL_QUESTION,
				ilExtendedTestStatistics::PROVIDES_VALUE) as $class => $evaluation)
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
	 * Get the selectable columns with basic question data
	 * @return array
	 */
	public function getQuestionOverviewHeader()
	{
		global $lng;

		$header = array(
			array(
				'id' => 'question_id',
				'title' => $lng->txt('question_id'),
				'comment' => '',
			),
			array(
				'id' => 'question_title',
				'title' => $lng->txt('question_title'),
				'comment' => '',
			),
			array(
				'id' => 'question_type_label',
				'title' => $this->plugin->txt('question_type'),
				'comment' => '',
			),
			array(
				'id' => 'assigned_count',
				'title' => $this->plugin->txt('assigned_count'),
				'comment' => $this->plugin->txt('assigned_count_description'),
			),
			array(
				'id' => 'answers_count',
				'title' => $this->plugin->txt('answers_count'),
				'comment' => $this->plugin->txt('answers_count_description'),
			),
			array(
				'id' => 'maximum_points',
				'title' => $this->plugin->txt('max_points'),
				'comment' => ''
			),
			array(
				'id' => 'average_points',
				'title' => $this->plugin->txt('average_points'),
				'comment' => $this->plugin->txt('average_points_description'),
			),
			array(
				'id' => 'average_percentage',
				'title' => $this->plugin->txt('average_percentage'),
				'comment' => $this->plugin->txt('average_percentage_description'),
			)
		);

		/** @var  ilExteEvalQuestion $evaluation */
		foreach ($this->statObj->getEvaluations(
			ilExtendedTestStatistics::LEVEL_QUESTION,
			ilExtendedTestStatistics::PROVIDES_VALUE) as $class => $evaluation)
		{
			$header[] = array(
				'id' => $class,
				'title' => $evaluation->getShortTitle(),
				'comment' => $evaluation->getDescription(),
			);
		}

		return $header;
	}

	/**
	 * @param $text
	 * @return	PHPExcel_Comment
	 */
	protected function createComment($text)
	{
		$comment = new PHPExcel_Comment();
		$richText = new PHPExcel_RichText();
		$extElement = new PHPExcel_RichText_TextElement($text);
		$richText->addText($extElement);
		$comment->setText($richText);
		$comment->setHeight('100pt');
		$comment->setWidth('200pt');
		return $comment;
	}

	/**
	 * @param PHPExcel_Worksheet	$worksheet
	 * @param string[] $titles
	 */
	protected function fillTitleRow($worksheet, $titles = array())
	{
		$column = 0;
		foreach ($titles as $title)
		{
			$cell = $worksheet->getCellByColumnAndRow($column, 1);
			$cell->setValueExplicit($title, PHPExcel_Cell_DataType::TYPE_STRING);
			$cell->getStyle()->applyFromArray($this->headerStyle);
			$column++;
		}
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
<?php
/**
 * Copyright (c) 2016 Institut fuer Lern-Innovation, Friedrich-Alexander-Universitaet Erlangen-Nuernberg
 * GPLv2, see LICENSE
 */
require_once 'Modules/Test/classes/class.ilTestExportPlugin.php';


/**
 * ilExtendedTestStatisticsExport Export
 *
 * @author Fred Neumann <fred.neumann@ili.fau.de>
 * @author Jesus Copado <jesus.copado@ili.fau.de>
 *
 */
class ilExtendedTestStatisticsExport
{


	private $plugin;

	private $test_object;

	/**
	 * @var ilExtendedTestStatistics
	 */
	private $test_statistics;

	/**
	 * ilExtendedTestStatisticsExport constructor.
	 */
	public function __construct($plugin, $test_object)
	{
		$this->setPlugin($plugin);
		$this->setTestObject($test_object);

		$this->getPlugin()->includeClass("class.ilExtendedTestStatistics.php");

		$test_statistics = new ilExtendedTestStatistics($this->getTestObject(), $this->getPlugin());
		$test_statistics->loadSourceData();
		$test_statistics->loadEvaluations(ilExtendedTestStatistics::LEVEL_TEST);
		$this->setTestStatistics($test_statistics);
	}


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
		return 'statistics2.xlsx';
	}

	/**
	 * @return string
	 */
	public function getFormatLabel()
	{
		return $this->getPlugin()->txt('il_exte_stat_label');
	}


	/**
	 * @param ilTestExportFilename $file_name
	 */
	public function buildExportFile(ilTestExportFilename $file_name)
	{
		//Creating Files with Charts using PHPExcel
		require_once './Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/ExtendedTestStatistics/classes/export/PHPExcel-1.8/Classes/PHPExcel.php';
		$objPHPExcel = new PHPExcel();

		// Create the first sheet with test statistics
		$test_worksheet = $objPHPExcel->getActiveSheet();

		$this->createFrameTestSheet($test_worksheet);

		$this->fillInDataTestSheet($test_worksheet);

		$this->calculateSummaryTestSheet($test_worksheet);
		/**
		 *
		 *
		 * $this->calculateSummaryTestSheet($test_worksheet);
		 *
		 * // Create the second sheet with question statistics
		 * $question_worksheet = $objPHPExcel->getActiveSheet();
		 *
		 * $this->createFrameQuestionSheet($question_worksheet);
		 *
		 * $this->fillInDataQuestionSheet($question_worksheet);
		 *
		 * $this->calculateSummaryQuestionSheet($question_worksheet);
		 **/
		// Save XSLX file
		ilUtil::makeDirParents(dirname($file_name->getPathname('xlsx', 'statistics')));
		$objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
		$objWriter->setIncludeCharts(TRUE);
		$objWriter->save(str_replace(__FILE__, $file_name->getPathname('xlsx', 'statistics'), __FILE__));

		//Deliver file
		ilUtil::deliverFile($file_name->getPathname('xlsx', 'statistics'), 'test_statistik.xls', 'xlsx');
	}

	protected function createFrameTestSheet(&$test_worksheet)
	{
		$test_worksheet->setTitle('Auswertung');
		/*
		 * Grunddaten des Tests
		 * Titel, Datum, Fragenanzahl, TN-Anzahl
		 *
		 * B1-C4
		 */
		$test_worksheet->setCellValue('B1', $this->getPlugin()->txt('title_of_test'));
		$test_worksheet->setCellValue('C1', $this->getTestObject()->getTitle());

		$exportDate = date("Y-m-d H:i:s");
		$test_worksheet->setCellValue('B2', $this->getPlugin()->txt('date_of_export'));
		$test_worksheet->setCellValue('C2', $exportDate);

		$test_worksheet->setCellValue('B3', $this->getPlugin()->txt('number_of_qst_and_part'));
		$test_worksheet->setCellValue('C3', 'xxx');

		$test_worksheet->setCellValue('B4', $this->getTestObject()->lng->txt("tst_stat_result_total_participants"));
		$test_worksheet->setCellValue('C4', 'xxx');

		/*
		 * Grunddaten der Fragen
		 * TN-Nummer, Max. Punkte, Punkte, Mittelwert, Varianz, Standardabweichung
		 *
		 * A6-F6
		 */

		$headerRow = array();
		array_push($headerRow, $this->getPlugin()->txt('name_user_name'));
		array_push($headerRow, $this->getPlugin()->txt('max_points'));
		array_push($headerRow, $this->getPlugin()->txt('points'));
		array_push($headerRow, $this->getPlugin()->txt('mean'));
		array_push($headerRow, $this->getPlugin()->txt('variance'));
		array_push($headerRow, $this->getPlugin()->txt('std_deviation'));
		$test_worksheet->fromArray($headerRow, null, 'A6', true);

		$styleArray = array('borders' => array('allborders' => array('style' => PHPExcel_Style_Border::BORDER_THIN)), 'fill' => array('type' => PHPExcel_Style_Fill::FILL_SOLID, 'color' => array('rgb' => '87cefa')));
		$test_worksheet->getStyle('B1:C4')->applyFromArray($styleArray);
		unset($styleArray);

		//Breite der Spalten automatisch anpassen
		foreach (range('A', $test_worksheet->getHighestColumn()) as $columnID)
		{
			$test_worksheet->getColumnDimension($columnID)->setAutoSize(true);
		}

		$test_worksheet->setCellValue('G5', $this->getPlugin()->txt('tasks'));
		$test_worksheet->freezePane('G7');

		/*
		 * ENDE der Grunddaten: A1 bis F6
		 * Aufgabentitel in Zeile (G)6 ff.
		 * Aufgabenwerte ab G7 ff.
		 */
	}

	protected function fillInDataTestSheet(&$test_worksheet)
	{
		$data = &$this->getTestObject()->getCompleteEvaluationData(TRUE, NULL, NULL);

		// Anzahl der Teilnehmer
		$numberOfParticipants = $data->getStatistics()->getStatistics()->count();
		$test_worksheet->setCellValue('C4', $numberOfParticipants);

		/*
		 * Aufgaben nach ID geordnet auflisten ab G6
		 *
		 * Sonderfall: TN haben nicht nur unterschiedliche Aufgabenreihenfolge, sondern auch (t.w.) andere Aufgaben
		 * Sonderfall vom Sonderfall: TN können unterschiedliche maximale Punktzahlen haben
		 */
		$allQuestions = array();
		foreach ($data->getParticipants() as $active_id => $userdata)
		{

			// Nur der bewertete Durchlauf soll genutzt werden
			$pass = 0;
			if ($this->getTestObject()->getPassScoring() == SCORE_BEST_PASS)
			{
				$pass = $data->getParticipant($active_id)->getBestPass();
			} else
			{ //der letzte Durchlauf
				$pass = $data->getParticipant($active_id)->getLastPass();
			}

			if (is_object($data->getParticipant($active_id)) && is_array($data->getParticipant($active_id)->getQuestions($pass)))
			{
				$participantsQuestions = $data->getParticipant($active_id)->getQuestions($pass);
				$questionAssoziation = array();

				foreach ($participantsQuestions as $question)
				{

					$titelAndID = preg_replace("/<.*?>/", "", $data->getQuestionTitle($question ["id"]) . " (ID=" . $question ["id"] . ")");
					$id = $question ["id"];

					$questionAssoziation[$id] = $titelAndID;

				}
				$allQuestions = $allQuestions + $questionAssoziation;
			}
		}
		ksort($allQuestions);
		/*
		 * $allQuestions ist ein assoziatives Array nach dem Muster:
		 * (integer)question_id -> (string)Fragetitel+ID
		 * Enthalten sind duplikatreduziert alle Fragen des Tests in aufsteigender ID-Reihenfolge
		 */
		$test_worksheet->fromArray($allQuestions, null, 'G6', true);

		//Anzahl der Aufgaben in den Kopfbereich eintragen
		$test_worksheet->setCellValue('C3', count($questionAssoziation) . '/' . count($allQuestions));


		/*
		 * Aufgabenspalten mit Rohwerten besetzen
		 *
		 */
		$rowCount = 7; //Erste freie Zeile nach den verschiedenen Kopfzeilen
		$maxColumn = $test_worksheet->getHighestColumn(); //Startspalte ist immer G
		$maxColumn++;
		$participantNumber = 1;
		foreach ($data->getParticipants() as $active_id => $userdata)
		{

			//Teilnehmernummer
			$test_worksheet->setCellValue('A' . $rowCount, $userdata->getName() . ' (' . $userdata->getLogin() . ')');
			$participantNumber++;

			/*
			 * Max. erreichbare Punkte
			 * Eigene Spalte, da bei Zufallstests nicht zwangsläufig bei jedem identisch
			 */
			$maxPoints = $data->getParticipant($active_id)->getMaxpoints();
			$test_worksheet->setCellValue('B' . $rowCount, $maxPoints);

			//Erreichte Punkte
			$reachedPoints = $data->getParticipant($active_id)->getReached();
			$test_worksheet->setCellValue('C' . $rowCount, $reachedPoints);

			//Mittelwert = Erreichte Punkte / Anzahl Aufgaben
			$test_worksheet->setCellValue('D' . $rowCount, '=C' . $rowCount . '/' . count($questionAssoziation));

			//Varianz = Mittelwert - Mittelwert * Mittelwert
			$test_worksheet->setCellValue('E' . $rowCount, '=VARP(G' . $rowCount . ':' . $test_worksheet->getHighestColumn() . $rowCount . ')');

			//Standardabweichung = Wurzel(Varianz)
			$test_worksheet->setCellValue('F' . $rowCount, '=sqrt(E' . $rowCount . ')');

			// Nur der bewertete Durchlauf soll genutzt werden
			$pass = 0;
			if ($this->getTestObject()->getPassScoring() == SCORE_BEST_PASS)
			{
				$pass = $data->getParticipant($active_id)->getBestPass();
			} else
			{ //der letzte Durchlauf
				$pass = $data->getParticipant($active_id)->getLastPass();
			}

			$atLeastOneAnsweredQueston = false;
			if (is_object($data->getParticipant($active_id)) && is_array($data->getParticipant($active_id)->getQuestions($pass)))
			{
				$participantsQuestions = $data->getParticipant($active_id)->getQuestions($pass);

				foreach ($participantsQuestions as $question)
				{

					for ($column = 'G'; $column != ($maxColumn); $column++)
					{
						$question_data = $data->getParticipant($active_id)->getPass($pass)->getAnsweredQuestionByQuestionId($question ["id"]);

						$titleFromSheet = $test_worksheet->getCell($column . '6')->getValue();
						$titelFromObject = preg_replace("/<.*?>/", "", $data->getQuestionTitle($question ["id"]) . " (ID=" . $question ["id"] . ")");

						//error_log($titleFromSheet.':'.$titelFromObject);
						//$boolean = $titleFromSheet === $titelFromObject;
						//error_log($boolean);


						if ($titleFromSheet === $titelFromObject)
						{
							$cell = $test_worksheet->getCell($column . $rowCount);
							$cell->setValue($question_data ["reached"]);

							//Sonderfall: Nutzer hat keine einzige Frage beantwortet Teil 1/2
							if ($question_data ["reached"] != null)
							{
								$atLeastOneAnsweredQueston = true;
							}

						}
					}
				}
			}

			//Sonderfall: Nutzer hat keine einzige Frage beantwortet Teil 2/2
			if (!$atLeastOneAnsweredQueston)
			{
				$test_worksheet->setCellValue('C' . $rowCount, $this->getPlugin()->txt('aborted'));
				$test_worksheet->setCellValue('D' . $rowCount, null);
				$test_worksheet->setCellValue('E' . $rowCount, null);
				$test_worksheet->setCellValue('F' . $rowCount, null);
			}
			$rowCount++;
		}
	}

	protected function calculateSummaryTestSheet(&$test_worksheet)
	{
		$lastRowOfRawData = $test_worksheet->getHighestRow();
		$lastColumnRawData = $test_worksheet->getHighestColumn();

		$styleGreen = array('fill' => array('type' => PHPExcel_Style_Fill::FILL_SOLID, 'color' => array('rgb' => '00FF00')));

		$styleRed = array('fill' => array('type' => PHPExcel_Style_Fill::FILL_SOLID, 'color' => array('rgb' => 'FF0000')));

		$maxColumn = $lastColumnRawData;
		$maxColumn++;

		$counter = 11;
		foreach($this->getTestStatistics()->getEvaluations() as $evaluation){
			//Name
			$test_worksheet->setCellValue('B' . ($lastRowOfRawData + $counter), $evaluation->getTitle());
			//Value
			$value = $evaluation->calculateValue();
			$test_worksheet->setCellValue('C' . ($lastRowOfRawData + $counter), $value->value);
			$counter++;
		}
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

	/**
	 * @return mixed
	 */
	public function getPlugin()
	{
		return $this->plugin;
	}

	/**
	 * @param mixed $plugin
	 */
	public function setPlugin($plugin)
	{
		$this->plugin = $plugin;
	}


	/**
	 * @return mixed
	 */
	public function getTestObject()
	{
		return $this->test_object;
	}

	/**
	 * @param mixed $test_object
	 */
	public function setTestObject($test_object)
	{
		$this->test_object = $test_object;
	}

	/**
	 * @return ilExtendedTestStatistics
	 */
	public function getTestStatistics()
	{
		return $this->test_statistics;
	}

	/**
	 * @param ilExtendedTestStatistics $test_statistics
	 */
	public function setTestStatistics($test_statistics)
	{
		$this->test_statistics = $test_statistics;
	}




}
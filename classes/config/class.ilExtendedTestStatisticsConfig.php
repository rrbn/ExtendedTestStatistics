<?php

/**
 * Copyright (c) 2016 Institut fÃ¼r Lern-Innovation, Friedrich-Alexander-UniversitÃ¤t Erlangen-NÃ¼rnberg
 * GPLv2, see LICENSE
 */

/**
 * Extended Test Statistics plugin config class
 *
 * @author Fred Neumann <fred.neumann@ili.fau.de>
 * @author Jesus Copado <jesus.copado@ili.fau.de>
 *
 */
class ilExtendedTestStatisticsConfig
{

	/**
	 * ilExtendedTestStatisticsConfig constructor.
	 * @param ilPlugin $a_plugin_object
	 */
	public function __construct($a_plugin_object = "")
	{
		$this->plugin = $a_plugin_object;
	}

	public function deleteConfig($evaluation_name)
	{
		global $ilDB;

		$sql = "DELETE FROM etstat_settings WHERE evaluation_name = '" . $evaluation_name . "'";
		$result = $ilDB->manipulate($sql);

		return $result;
	}

	public function insertConfig($evaluation_name, $value)
	{
		global $ilDB;

		$insert_query = $ilDB->insert("etstat_settings", array("evaluation_name" => array("text", $evaluation_name), "value" => array("text", $value)));

		return $insert_query->result;
	}

	public function getEvaluationClasses($a_type = "")
	{
		global $ilDB;

		$this->plugin->includeClass("abstract/class.ilExteEvalBase.php");
		$this->plugin->includeClass("abstract/class.ilExteEvalQuestion.php");
		$this->plugin->includeClass("abstract/class.ilExteEvalTest.php");

		//Step 1: Read from the database
		$db_class_names = array();
		$database_select = $ilDB->query("SELECT * FROM etstat_settings");

		while ($evaluations_db_row = $ilDB->fetchAssoc($database_select))
		{
			if (strpos($evaluations_db_row["evaluation_name"], "ilExteEvalQuestion") === 0)
			{
				$db_class_names["Questions"][$evaluations_db_row["evaluation_name"]] = $evaluations_db_row["value"];
			} elseif (strpos($evaluations_db_row["evaluation_name"], "ilExteEvalTest") === 0)
			{
				$db_class_names["Tests"][$evaluations_db_row["evaluation_name"]] = $evaluations_db_row["value"];
			}
		}

		//Step 2: Read from class files
		$evaluations_class_names = array();
		$class_files = glob('./Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/ExtendedTestStatistics/classes/evaluations/class.*.php');
		if (!empty($class_files))
		{
			foreach ($class_files as $file)
			{
				$parts = explode('.', basename($file));

				//Include and instantiate class
				$this->plugin->includeClass("evaluations/class." . $parts[1] . ".php");
				$class = new $parts[1](1, $this->plugin);

				if ($class instanceof ilExteEvalQuestion)
				{
					//If is on the DB
					if (isset($db_class_names["Questions"][$parts[1]]))
					{
						$evaluations_class_names["Questions"][$parts[1]] = $db_class_names["Questions"][$parts[1]];
					} else
					{
						//Insert into the DB
						$this->insertConfig($parts[1], "admin");
						$evaluations_class_names["Questions"][$parts[1]] = "admin";
					}
				}

				if ($class instanceof ilExteEvalTest)
				{
					//If is on the DB
					if (isset($db_class_names["Tests"][$parts[1]]))
					{
						$evaluations_class_names["Tests"][$parts[1]] = $db_class_names["Tests"][$parts[1]];
					} else
					{
						//Insert into the DB
						$this->insertConfig($parts[1], "admin");
						$evaluations_class_names["Tests"][$parts[1]] = "admin";
					}
				}
			}
		}


		if ($a_type == "test")
		{
			return $evaluations_class_names["Tests"];
		} elseif ($a_type == "question")
		{
			return $evaluations_class_names["Questions"];
		} else
		{
			return array_merge($evaluations_class_names["Tests"], $evaluations_class_names["Questions"]);
		}

	}

}
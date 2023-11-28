<?php
// Copyright (c) 2017 Institut fuer Lern-Innovation, Friedrich-Alexander-Universitaet Erlangen-Nuernberg, GPLv3, see LICENSE

/**
 * Extended Test Statistics plugin config class
 *
 * @author Fred Neumann <fred.neumann@ili.fau.de>
 * @author Jesus Copado <jesus.copado@ili.fau.de>
 *
 */
class ilExtendedTestStatisticsConfig
{
	/* Availabilities */
	const FOR_ADMIN = 'admin';
	const FOR_USER = 'user';
	const FOR_NONE = 'none';

	/**
	 * @var array	$params		evaluation parameters: 	class => parameter => value
	 */
	protected $params;

	/**
	 * ilExtendedTestStatisticsConfig constructor.
	 * @param ilPlugin|string $a_plugin_object
	 */
	public function __construct($a_plugin_object = "")
	{
		$this->plugin = $a_plugin_object;
	}

	/**
	 * Get the availability options that can be chosen
	 * @return array
	 */
	public function getAvailabilityOptions()
	{
		return array(
			self::FOR_ADMIN => $this->plugin->txt("evaluation_available_for_admins"),
			self::FOR_USER => $this->plugin->txt("evaluation_available_for_users"),
			self::FOR_NONE => $this->plugin->txt("evaluation_available_for_none"));
	}

	/**
	 * Read the availability settings from the database
	 * @return array	classname => availability
	 */
	protected function readAvailabilities()
	{
		global $ilDB;

		$result = $ilDB->query("SELECT * FROM etstat_settings");
		$availabilities = array();
		while ($row = $ilDB->fetchAssoc($result))
		{
			$availabilities[$row["evaluation_name"]] = $row["value"];
		}
		return $availabilities;
	}

	/**
	 * Write the availability of an evaluation to the database
	 * @param string $evaluation_name	classname of the evaluation
	 * @param string $value				availability (admin, user, none)
	 */
	public function writeAvailability($evaluation_name, $value)
	{
		global $ilDB;

		$ilDB->replace('etstat_settings',
			array('evaluation_name' => array('text', $evaluation_name)),
			array('value' => array("text", $value))
		);
	}

	/**
	 * Get the available evaluation classes
	 * @param string $a_type	evaluation type (test or question)
	 * @return array			classname => availability (admin, user or none)
	 */
	public function getEvaluationClasses($a_type = "")
	{
		$this->plugin->includeClass("abstract/class.ilExteEvalBase.php");
		$this->plugin->includeClass("abstract/class.ilExteEvalQuestion.php");
		$this->plugin->includeClass("abstract/class.ilExteEvalTest.php");

		$return_classes = array(
			'question' => array(),
			'test' => array()
		);

		// read the availability settings of evaluations from the database
		$availabilities = $this->readAvailabilities();

		// get evaluation classes (builtin and hooked evaluations)
		$classes = array_merge(
			$this->getIncludedClasses($this->plugin->getDirectory() .'/classes/evaluations/class.*.php'),
			$this->getIncludedClasses('./Customizing/global/plugins/Modules/Test/Evaluations/*/classes/class.*.php')
		);

		foreach ($classes as $class)
		{
			if (isset($availabilities[$class]))
			{
				$availability = $availabilities[$class];
			}
			else
			{
				// take admin access as default and write this setting
				$availability = self::FOR_ADMIN;
				$this->writeAvailability($class, $availability);
			}

			// check the base class
			if (is_subclass_of($class, 'ilExteEvalQuestion'))
			{
				$return_classes['question'][$class] = $availability;
			}
			elseif (is_subclass_of($class, 'ilExteEvalTest'))
			{
				$return_classes['test'][$class] = $availability;
			}
		}

		switch($a_type)
		{
			case 'test':
			case 'question':
				return $return_classes[$a_type];
			default:
				return array_merge($return_classes['test'], $return_classes['question']);
		}
	}

	/**
	 * Include classes from a file pattern and get their names
	 * @param	string		$pattern	file pattern (relative to installation directory)
	 * @return string[]		class names
	 */
	protected function getIncludedClasses($pattern)
	{
		$class_names = array();
		$class_files = glob($pattern);
		if (!empty($class_files))
		{
			foreach ($class_files as $file)
			{
				require_once($file);
				$parts = explode('.', basename($file));
				$class_name = $parts[1];
				$class_names[] = $class_name;
			}
		}
		return $class_names;
	}


	/**
	 * Get all stored parameters for an evaluation class
	 * @param string	$evaluation_name		class name of the evaluation
	 * @return array							parameter_name => value
	 */
	public function getEvaluationParameters($evaluation_name)
	{
		global $ilDB;

		if (!isset($this->params))
		{
			$this->params = array();
			$query = "SELECT * FROM etstat_params";
			$res = $ilDB->query($query);
			while($row = $ilDB->fetchAssoc($res))
			{
				$this->params[$row['evaluation_name']][$row['parameter_name']] = $row['value'];
			}
		}

		return (array) $this->params[$evaluation_name];
	}

	/**
	 * Write a parameter value
	 * @param string	$evaluation_name
	 * @param string	$parameter_name
	 * @param mixed		$value
	 */
	public function writeParameter($evaluation_name, $parameter_name, $value)
	{
		global $ilDB;
		$ilDB->replace('etstat_params',
			array('evaluation_name' => array('text', $evaluation_name),
				'parameter_name'=> array('text', $parameter_name)),
			array('value' => array('text', (string) $value))
		);
	}
}

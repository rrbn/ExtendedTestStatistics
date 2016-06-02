<#1>
<?php
/**
 * Copyright (c) 2016 Institut fuer Lern-Innovation, Friedrich-Alexander-Universitaet Erlangen-Nuernberg
 * GPLv2, see LICENSE
 *
 *
 * Database creation script.
 *
 * @author Fred Neumann <fred.neumann@ili.fau.de>
 * @author Jesus Copado <jesus.copado@ili.fau.de>
 *
 * $Id$
 */
/*
 * Create the new table for evaluation settings
 */
global $ilDB;

if (!$ilDB->tableExists('etstat_settings'))
{
    $fields = array(
        'evaluation_name' => array(
            'type' => 'text',
            'length' => 255,
            'notnull' => true,
        ),
        'value' => array(
            'type' => 'text',
            'length' => 8,
            'notnull' => true,
            'default' => 'admin'
        )
    );
    $ilDB->createTable("etstat_settings", $fields);
    $ilDB->addPrimaryKey("etstat_settings", array("evaluation_name"));
}
?>
<#2>
<?php
/*
 * Creation of values with current evaluations in the plugin
 */
if ($ilDB->tableExists('etstat_settings'))
{
	include_once("./Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/ExtendedTestStatistics/classes/abstract/class.ilExteEvalBase.php");
	include_once("./Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/ExtendedTestStatistics/classes/abstract/class.ilExteEvalQuestion.php");
	include_once("./Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/ExtendedTestStatistics/classes/abstract/class.ilExteEvalTest.php");

	$classnames = array();
	$classfiles = glob('./Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/ExtendedTestStatistics/classes/evaluations/class.*.php');
	if (!empty($classfiles)) {
		foreach ($classfiles as $file) {
			require_once($file);
			$parts = explode('.', basename($file));
				$classnames[] = $parts[1];
		}
	}

	foreach($classnames as $evaluation_name){
		$check = $ilDB->queryF("SELECT * FROM etstat_settings WHERE evaluation_name = %s", array('text'), array($evaluation_name));
		if($check->numRows()==0){
			$ilDB->insert("etstat_settings", array(
				"evaluation_name" => array("text", $evaluation_name),
				"value" => array("text", "admin")));
		}
	}
}
?>

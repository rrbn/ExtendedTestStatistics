<#1>
<?php
// Copyright (c) 2017 Institut fuer Lern-Innovation, Friedrich-Alexander-Universitaet Erlangen-Nuernberg, GPLv3, see LICENSE

/**
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
    // nothig todo
?>
<#3>
<?php
if (!$ilDB->tableExists('etstat_params'))
{
    $fields = array(
        'evaluation_name' => array(
            'type' => 'text',
            'length' => 255,
            'notnull' => true,
        ),
        'parameter_name' => array(
            'type' => 'text',
            'length' => 255,
            'notnull' => true,
        ),
        'value' => array(
            'type' => 'text',
            'length' => 8,
            'notnull' => false,
            'default' => null
        )
    );
    $ilDB->createTable("etstat_params", $fields);
    $ilDB->addPrimaryKey("etstat_params", array("evaluation_name", "parameter_name"));
}
?>

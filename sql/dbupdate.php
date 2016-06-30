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
 * EMPTY
 */
?>

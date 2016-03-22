<?php

include_once("./Services/UIComponent/classes/class.ilUserInterfaceHookPlugin.php");
 
/**
 * Basic plugin file
 *
 * @author Fred Neumann <fred.neumann@fau.de>
 * @version $Id$
 *
 */
class ilExtendedTestStatisticsPlugin extends ilUserInterfaceHookPlugin
{
	public function getPluginName()
	{
		return "ExtendedTestStatistics";
	}

	public function logBacktrace($a_limit = 0)
	{
		/** @var ilLog $ilLog */
		global $ilLog;

		$bt = debug_backtrace();
		$cnt = 0;
		foreach ($bt as $t)
		{
			if ($cnt != 0 && ($a_limit == 0 || $cnt <= $a_limit))
			{
				$ilLog->write($t["file"].", ".$t["function"]." [".$t["line"]."]");
			}
			$cnt++;
		}
	}
}

?>

<?php
/* Copyright (c) 1998-2017 ILIAS open source, Extended GPL, see docs/LICENSE */

include_once("./Services/UIComponent/classes/class.ilUserInterfaceHookPlugin.php");

/**
 * Plugin to import question pools from "Quasar" to ILIAS.
 *
 * @author Jesús López <lopez@leifos.de>
 * @version $Id$
 *
 */
class ilQuasarQPImporterPlugin extends ilUserInterfaceHookPlugin
{
	function getPluginName()
	{
		return "QuasarQPImporter";
	}
}

?>
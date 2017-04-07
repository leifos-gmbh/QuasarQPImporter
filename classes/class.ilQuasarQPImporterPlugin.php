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
	private static $instance = null;

	const CTYPE = 'Services';
	const CNAME = 'UIComponent';
	const SLOT_ID = 'uihk';
	const PNAME = 'QuasarQPImporter';
	const PARSER_DIR = "xsl";
	const PARSER_FILE = "qti_parser.xsl";

	/**
	 * Get singleton instance
	 * @return \ilQuasarQPImporterPlugin
	 */
	public static function getInstance()
	{
		//global $ilPluginAdmin;

		if(self::$instance)
		{
			return self::$instance;
		}
		include_once './Services/Component/classes/class.ilPluginAdmin.php';
		return self::$instance = ilPluginAdmin::getPluginObject(
			self::CTYPE,
			self::CNAME,
			self::SLOT_ID,
			self::PNAME
		);
	}

	function getPluginName()
	{
		return self::PNAME;
	}

	function getParserFilePath()
	{
		return parent::getDirectory()."/".self::PARSER_DIR."/".self::PARSER_FILE;
	}
}

?>
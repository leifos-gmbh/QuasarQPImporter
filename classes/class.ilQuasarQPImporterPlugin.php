<?php declare(strict_types=1);
/* Copyright (c) 1998-2017 ILIAS open source, Extended GPL, see docs/LICENSE */


/**
 * Plugin to import question pools from "Quasar" to ILIAS.
 *
 * @author Jesús López <lopez@leifos.de>
 * @version $Id$
 *
 */
class ilQuasarQPImporterPlugin extends ilUserInterfaceHookPlugin
{
	private static ?ilQuasarQPImporterPlugin $instance = null;

	protected const CTYPE = 'Services';
    protected const CNAME = 'UIComponent';
	protected const SLOT_ID = 'uihk';
	protected const PNAME = 'QuasarQPImporter';
	protected const PARSER_DIR = "xsl";
	protected const PARSER_FILE = "qti_parser.xsl";


	public static function getInstance() : ilQuasarQPImporterPlugin
	{

		if(self::$instance)
		{
			return self::$instance;
		}

		return self::$instance = ilPluginAdmin::getPluginObject(
			self::CTYPE,
			self::CNAME,
			self::SLOT_ID,
			self::PNAME
		);
	}

	public function getPluginName()
	{
		return self::PNAME;
	}

	public function getParserFilePath() : string
    {
		return parent::getDirectory()."/".self::PARSER_DIR."/".self::PARSER_FILE;
	}
}

?>
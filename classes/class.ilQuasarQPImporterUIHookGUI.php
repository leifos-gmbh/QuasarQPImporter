<?php
/* Copyright (c) 1998-2017 ILIAS open source, Extended GPL, see docs/LICENSE */

include_once("./Services/UIComponent/classes/class.ilUIHookPluginGUI.php");

/**
 * User interface hook class
 *
 * @author Jesús López <lopez@leifos.com>
 * @version $Id$
 * @ingroup ServicesUIComponent
 * @ilCtrl_IsCalledBy ilQuasarQPImporterUIHookGUI: ilUIPluginRouterGUI,
 * @ilCtrl_Calls ilQuasarQPImporterUIHookGUI: ilObjQuestionPoolGUI
 */
class ilQuasarQPImporterUIHookGUI extends ilUIHookPluginGUI
{
	protected $quasar_file; //original quasar file  /data/[client name]/qpl_quasar/[file name]/file
	protected $quasar_full_path; //original quasar full path  /data/[client name]/qpl_quasar/[file name]
	protected $qpl_directory; // question pool directory    /data/[client name]/qpl_data/qpl_import/[file name]
	protected $qpl_file_name;// original file name sanitized
	protected $time;//
	protected $import_directory; //path to import base directory /data/[client name]/qpl_data/qpl_import
	protected $ref_id;

	function executeCommand()
	{
		$cmd = "importFileQuasar";
		$this->$cmd();
	}

	/**
	 * Modify HTML output of GUI elements. Modifications modes are:
	 * - ilUIHookPluginGUI::KEEP (No modification)
	 * - ilUIHookPluginGUI::REPLACE (Replace default HTML with your HTML)
	 * - ilUIHookPluginGUI::APPEND (Append your HTML to the default HTML)
	 * - ilUIHookPluginGUI::PREPEND (Prepend your HTML to the default HTML)
	 *
	 * @param string $a_comp component
	 * @param string $a_part string that identifies the part of the UI that is handled
	 * @param string $a_par array of parameters (depend on $a_comp and $a_part)
	 *
	 * @return array array with entries "mode" => modification mode, "html" => your html
	 */
	function getHTML($a_comp, $a_part, $a_par = array())
	{
		global $lng;

		if(strpos($a_par['tpl_id'],"tpl.property_form.html")
			and $_REQUEST["cmd"] == "create"
			and $_REQUEST["new_type"] == "qpl"
			and strpos($a_par['html'],"searchSource")
			and $_REQUEST["cpfl"] == ""
		)
		{
			$html = "</div></form></div></div></div>"; // <-- We need this to close the previous form";

			$html .= "<div class='il_VAccordionInnerContainer'>
						<div class='il_VAccordionToggleDef'>
							<div class='il_VAccordionHead' style=''>
								<div style='padding:3px;'>
									<div class='small'>
										<h3 class='ilBlockHeader' style='font-weight:normal;'>
											".$lng->txt("ui_uihk_quaimp_option_title")."
										</h3>
									</div>
								</div>
							</div>
						</div>
						<div class='il_VAccordionContentDef ilAccHideContent'>
						<div class='il_VAccordionContent' style=''>";

			$html .= $this->initImportQuasarForm();
			$html .= "</div></div>";

			return array("mode" => ilUIHookPluginGUI::APPEND, "html" => $html);

		}

	}

	/**
	 * Form html
	 * @return string
	 */
	function initImportQuasarForm()
	{
		global $lng,$ilCtrl;

		include_once("Services/Form/classes/class.ilPropertyFormGUI.php");

		$form = new ilPropertyFormGUI();

		$form->setTarget("_top");

		$form->setFormAction($ilCtrl->getFormActionByClass(array("ilUIPluginRouterGUI","ilQuasarQPImporterUIHookGUI"),"importFileQuasar"));

		include_once("./Services/Form/classes/class.ilFileInputGUI.php");
		$fi = new ilFileInputGUI($lng->txt("import_file"), "importfileQuasar");
		$fi->setSuffixes(array("xml"));
		$fi->setRequired(true);
		$form->addItem($fi);

		$ref = new ilHiddenInputGUI("ref_id");
		$ref->setValue($_GET['ref_id']);
		$form->addItem($ref);

		$form->addCommandButton("importFileQuasar", $lng->txt("import"));
		$form->addCommandButton("cancel", $lng->txt("cancel"));

		return $form->getHTML();
	}

	/**
	 * MAIN METHOD create import files
	 */
	function importFileQuasar()
	{
		global $ilCtrl;

		$this->time = time();

		//upload file in a temporary directory
		if($this->uploadQuasarFile())
		{
			include_once("./Modules/TestQuestionPool/classes/class.ilObjQuestionPool.php");

			$this->import_directory = ilObjQuestionPool::_createImportDirectory();

			//here the questions/answers xml
			if($this->parseQuasarXML())
			{
				$this->createQplFile();
				$this->createManifestFile();
				$this->createExportFile();
				$this->processImport();
			}
		}

		$ilCtrl->redirectByClass(array("ilUIPluginRouterGUI","ilObjQuestionPoolGUI"),"uploadQplObject");

	}

	/**
	 * Upload Quasar file into temporary directories.
	 */
	function uploadQuasarFile()
	{
		global $lng;

		$this->ref_id = $_POST['ref_id'];

		if ($_FILES["importfileQuasar"]["error"] > UPLOAD_ERR_OK)
		{
			ilUtil::sendFailure($lng->txt("error_upload"));
			return false;
		}

		$file = pathinfo($_FILES["importfileQuasar"]["name"]);

		$this->qpl_file_name = ilUtil::_sanitizeFilemame($file['filename']);

		$base_quasar_dir = ilUtil::getDataDir()."/qpl_quasar";
		$this->quasar_full_path = $base_quasar_dir."/".$this->qpl_file_name;

		$this->quasar_file = $this->quasar_full_path."/".ilUtil::_sanitizeFilemame($file['basename']);

		//create quasar directories
		if(!$this->createPath(array($base_quasar_dir, $this->quasar_full_path)))
		{
			return false;
		}

		//upload quasar file
		if(!ilUtil::moveUploadedFile($_FILES["importfileQuasar"]["tmp_name"], $file["filename"], $this->quasar_file))
		{
			return false;
		}

		return true;

	}

	/**
	 * Create directories in client_data_dir/qpl_data/qpl_[filename]/
	 * @return bool
	 */
	function createQPLDirectories()
	{
		$this->qpl_directory = $this->import_directory."/".$this->time."__0__qpl_".$this->qpl_file_name;

		$directories = array (
			$this->qpl_directory,
			$this->qpl_directory."/objects",
			$this->qpl_directory."/Modules",
			$this->qpl_directory."/Modules/TestQuestionPool",
			$this->qpl_directory."/Modules/TestQuestionPool/set_1",
		);

		return $this->createPath($directories);
	}

	/**
	 * Read Quasar xml and create ILIAS xml.
	 */
	function parseQuasarXML()
	{
		global $ilUser, $lng;

		$logger = ilLoggerFactory::getLogger('root');

		$xml = new DOMDocument();
		$logger->warning($this->quasar_file);
		$xml->load($this->quasar_file);

		$xsl = new DOMDocument();
		$xsl->load(ilQuasarQPImporterPlugin::getInstance()->getParserFilePath());

		$proc = new XSLTProcessor();
		$proc->importStylesheet($xsl);


		$proc->setParameter('', 'ilias_version', ILIAS_VERSION);
		$proc->setParameter('', 'user', $ilUser->getFullname());

		$new = $proc->transformToXml($xml);

		if($this->createQPLDirectories())
		{
			if(file_put_contents($this->qpl_directory."/".$this->time."__0__qti_".$this->qpl_file_name.".xml",$new) !== 'false')
			{
				return true;
			}
		}

		ilUtil::sendFailure($lng->txt("ui_uihk_quaimp_error_parse"));
		return false;
	}

	/**
	 * Manifest xml creation
	 */
	function createManifestFile()
	{
		include_once "./Services/Xml/classes/class.ilXmlWriter.php";
		$manifest_writer = new ilXmlWriter();
		$manifest_writer->xmlHeader();
		$manifest_writer->xmlStartTag(
			'Manifest',
			array(
				"MainEntity" => "qpl",
				"Title" => $this->qpl_file_name,
				"TargetRelease" => ILIAS_VERSION_NUMERIC,
				"InstallationId" => IL_INST_ID,
				"InstallationUrl" => ILIAS_HTTP_PATH));

		$manifest_writer->xmlStartTag(
			'ExportFile',
			array(
				"Component" => "Modules/TestQuestionPool",
				"Path" => "Modules/TestQuestionPool/set_1/export.xml"));  // Forced set_1
		$manifest_writer->xmlEndTag('ExportFile');

		$manifest_writer->xmlEndTag('Manifest');

		$manifest_writer->xmlDumpFile($this->qpl_directory."/manifest.xml", false);

		/*
		// same using simpleXML
		$manifestXML = new SimpleXMLElement("<Manifest></Manifest>");
		$manifestXML->addAttribute("MainEntity", "qpl");
		$manifestXML->addAttribute("Title", $this->qpl_file_name);
		$manifestXML->addAttribute("TargetRelease", ILIAS_VERSION_NUMERIC);
		$manifestXML->addAttribute("InstallationId", IL_INST_ID);
		$manifestXML->addAttribute("InstallationUrl", ILIAS_WEB_DIR);

		$newsIntro = $manifestXML->addChild('ExportFile');
		$newsIntro->addAttribute("Component","Modules/TestQuestionPool");
		$newsIntro->addAttribute("Path","Modules/TestQuestionPool/set_1/export.xml");

		$manifestXML->saveXML($this->qpl_directory."/manifest.xml");
		*/
	}

	/**
	 * XML questions creation.
	 */
	function createQplFile()
	{
		$xml = new SimpleXMLElement('<ContentObject></ContentObject>');
		$xml->addAttribute("Type", "Questionpool_Test");

		$metadata = $xml->addChild("Metadata");

		$general = $metadata->addChild("General");
		$general->addAttribute("Structure","Hierarchical");

		$identifier = $general->addChild("Identifier");
		$identifier->addAttribute("Catalog","ILIAS");
		$identifier->addAttribute("Entry", $this->qpl_file_name);

		$title = $general->addChild("Title");
		$title->addAttribute("Language","en"); // Forced "en" 

		$description = $general->addChild("Description");
		$description->addAttribute("Language", "en"); // Forced "en"

		$keyword= $general->addChild("Keyword");
		$keyword->addAttribute("Language", "en"); // Forced "en"

		$settings = $xml->addChild("Settings");

		$settings->addChild("ShowTaxonomies","0"); //Forced "0"
		$settings->addChild("NavTaxonomy","0"); //Forced "0"
		$settings->addChild("SkillService","0"); //Forced "0"

		$xml->saveXML($this->qpl_directory."/".$this->time."__0__qpl_".$this->qpl_file_name.".xml");

	}

	/**
	 * export.xml creation
	 */
	function createExportFile()
	{
		include_once "./Services/Xml/classes/class.ilXmlWriter.php";

		$export_writer = new ilXmlWriter();
		$export_writer->xmlHeader();

		$attribs = array("InstallationId" => IL_INST_ID,
			"InstallationUrl" => ILIAS_HTTP_PATH,
			"Entity" => "qpl", "SchemaVersion" => "4.1.0", "TargetRelease" => ILIAS_VERSION_NUMERIC,
			"xmlns:xsi" => "http://www.w3.org/2001/XMLSchema-instance",
			"xmlns:exp" => "http://www.ilias.de/Services/Export/exp/4_1",
			"xsi:schemaLocation" => "http://www.ilias.de/Services/Export/exp/4_1 ".ILIAS_HTTP_PATH."/xml/ilias_export_4_1.xsd"
		);

		$export_writer->xmlStartTag('exp:Export', $attribs);
		$export_writer->xmlStartTag('exp:ExportItem', array("Id" =>$this->qpl_file_name));

		$export_writer->xmlEndTag('exp:ExportItem');
		$export_writer->xmlEndTag('exp:Export');

		$export_writer->xmlDumpFile($this->qpl_directory."/Modules/TestQuestionPool/set_1/export.xml", false);  // Forced set_1

	}

	/**
	 * Create objects from the xml.
	 */
	function processImport()
	{
		global $lng;

		$qpl_directory_path = $this->import_directory.'/'.$this->time."__0__qpl_".$this->qpl_file_name;

		//$xml_file = $qpl_directory_path."/".$this->time."__0__qpl_".$this->qpl_file_name.".xml";
		$qti_file = $qpl_directory_path."/".$this->time."__0__qti_".$this->qpl_file_name.".xml";

		// start verification of QTI files
		include_once "./Services/QTI/classes/class.ilQTIParser.php";
		$qtiParser = new ilQTIParser($qti_file, IL_MO_VERIFY_QTI, 0, "");
		$result = $qtiParser->startParsing();
		$founditems =& $qtiParser->getFoundItems();

		if (count($founditems) == 0)
		{
			// nothing found
			$this->deleteImportDirectories();

			ilUtil::sendFailure($lng->txt("qpl_import_no_items"), true);
		}

		$complete = 0;
		$incomplete = 0;
		foreach ($founditems as $item)
		{
			if (strlen($item["type"]))
			{
				$complete++;
			}
			else
			{
				$incomplete++;
			}
		}

		if ($complete == 0)
		{
			$this->deleteImportDirectories();

			ilUtil::sendFailure($lng->txt("qpl_import_non_ilias_files"), true);
			return false;
		}
		$newObj = new ilObjQuestionPool(0, true);
		$newObj->setType("qpl");
		$newObj->setTitle($this->qpl_file_name);
		$newObj->setDescription("questionpool import");
		$newObj->create(true);
		$newObj->createReference();
		$newObj->putInTree($this->ref_id);
		$newObj->setPermissions($this->ref_id);
		// this is not required anymore?
		//$newObj->notify("new",$this->ref_id,$_GET["parent_non_rbac_id"],$this->ref_id,$newObj->getRefId());

		include_once("./Services/Export/classes/class.ilImport.php");
		$imp = new ilImport($this->ref_id);
		$map = $imp->getMapping();
		$map->addMapping("Modules/TestQuestionPool", "qpl", "new_id", $newObj->getId());

		$imp->importFromDirectory($this->qpl_directory, "qpl", "Modules/TestQuestionPool");

		$this->deleteImportDirectories();

		ilUtil::sendSuccess($lng->txt("object_imported"),true);
		ilUtil::redirect("ilias.php?ref_id=".$newObj->getRefId().
			"&baseClass=ilObjQuestionPoolGUI");

	}

	/**
	 * @param array $a_directories
	 * @return bool
	 */
	function createPath(array $a_directories)
	{
		global $lng;

		foreach ($a_directories as $dir)
		{
			ilUtil::makeDir($dir);
			if(!is_writable($dir))
			{
				ilUtil::sendFailure($lng->txt("ui_uihk_quaimp_directory_no_writable",$dir));
				return false;
			}
		}

		return true;
	}

	/**
	 * delete residual directories and its content
	 */
	function deleteImportDirectories()
	{
		// delete import directory and its content
		ilUtil::delDir($this->import_directory);

		//delete original quasar XML and its directory.
		ilUtil::delDir($this->quasar_full_path);
	}

}
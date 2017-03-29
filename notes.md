 
## PARSE QUASAR TO ILIAS
    
In the Quasar XML we don't have few values that should be defined with a default value.

Default values defined in Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/QuasarQPImporter/xsl/qti_parser.xsl

    Parameter                   |     Value
    ------------------------------------------------------------------------------
    externalId                  |     Q-ID          (from quasar xml)
    
    feedback_setting            |     1       (If after import we create export files: Quest. Single Ans. takes value 2 and Multiple Ans. takes value 1)
    
    duration                    |   P0Y0M0DT0H1M0S  (1 minute)
    
    shuffle                     |    yes
    
    decvar                      |    [emtpy]        (<decvar></decvar>)
    
    respcondition continue      |    Yes            (<respcondition continue="Yes">)


Item feedback tag: (Are always empty only changing the N value? ) From where can I take this value?? is it really needed?

    <itemfeedback ident="response_N" view="All">
        <flow_mat>
            <material>
                <mattext texttype="text/plain"></mattext>
            </material>
        </flow_mat>
    </itemfeedback>


#### In the plugin PHP code we have to define also a set of values:

- Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/QuasarQPImporter/classes/class.ilQuasarQPImporterUIHookGUI.php

    
        Parameter/Path                              |     Value
        -----------------------------------------------------------------------------
        export.xml path                             |    Modules/TestQuestionPool/set_1/export.xml       
    
        createExportFile                            |   Forced ShemaVersion to 4.1.0
    
        $title = $general->addChild("Title");       |   "eng"
        $title->addAttribute("Language","en"); 
        + Descritption and keyword also
        
        $settings->addChild("ShowTaxonomies","0")   |    0
        $settings->addChild("NavTaxonomy","0");     |    0
        $settings->addChild("SkillService","0");    |    0
        
	
## Files allowed

This plugin accepts 2 different XML structures in the input files. There are mainly two relevant differences:

Main question node:

   - Option A:
    
    <Question-Data_APPROVED_Export>

   - Option B:
   
    <FinalReport_APPROVED_Q-Data_x005F_xx_ALL>
   
Question identifier:
   
   - Option A:
    
            <Q-ID>
    
   - Option B:
    
            <Questions.Q-ID>


## Possible error in the quasar file: QUASAR_Question-Set_BAS-SUR_XML_v1.0_2016.xml

This file contains one question with no correlation between the <Ctrl_Used> value and the number of answers given.
Question number: <Questions.Q-ID>641</Questions.Q-ID> Ctr_Used is 5 and the question has only 4 answers.

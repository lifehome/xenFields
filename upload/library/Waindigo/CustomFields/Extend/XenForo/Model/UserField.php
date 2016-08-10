<?php

/**
 *
 * @see XenForo_Model_UserField
 */
class Waindigo_CustomFields_Extend_XenForo_Model_UserField extends XFCP_Waindigo_CustomFields_Extend_XenForo_Model_UserField
{

    /**
     *
     * @see XenForo_Model_UserField::prepareUserField()
     */
    public function prepareUserField(array $field, $getFieldChoices = false, $fieldValue = null, $valueSaved = true)
    {
        if ($getFieldChoices && (isset($field['field_choices_callback_class']) && $field['field_choices_callback_class']) &&
             (isset($field['field_choices_callback_method']) && $field['field_choices_callback_method'])) {
            try {
                $field['fieldChoices'] = call_user_func(
                    array(
                        $field['field_choices_callback_class'],
                        $field['field_choices_callback_method']
                    ), $field);
                $getFieldChoices = false;
            } catch (Exception $e) {
                // do nothing
            }
        }

        return parent::prepareUserField($field, $getFieldChoices, $fieldValue, $valueSaved);
    }

    /**
     *
     * @see XenForo_Model_UserField::getUserFieldChoices()
     */
    public function getUserFieldChoices($fieldId, $choices, $master = false)
    {
        $choices = parent::getUserFieldChoices($fieldId, $choices, $master);

        $xenOptions = XenForo_Application::get('options');

        if ($xenOptions->waindigo_customFields_sortChoicesAlphabetically) {
            asort($choices);
        }

        return $choices;
    }

    /**
     *
     * @see XenForo_Model_UserField::prepareUserFieldConditions()
     */
    public function prepareUserFieldConditions(array $conditions, array &$fetchOptions)
    {
        $db = $this->_getDb();
        $sqlConditions = array();

        if (!empty($conditions['field_choices_class_id'])) {
            $sqlConditions[] = 'user_field.field_choices_class_id = ' . $db->quote(
                $conditions['field_choices_class_id']);
        }

        if (!empty($conditions['addon_id'])) {
            $sqlConditions[] = 'user_field.addon_id = ' . $db->quote($conditions['addon_id']);
        }

        if (!empty($conditions['isSearchAdvancedUser'])) {
            $sqlConditions[] = 'user_field.search_advanced_user_waindigo = 1';
        }

        $userFieldConditions = parent::prepareUserFieldConditions($conditions, $fetchOptions);

        if (empty($sqlConditions)) {
            return $userFieldConditions;
        }

        $sqlConditions[] = $userFieldConditions;
        return $this->getConditionsForClause($sqlConditions);
    }

    /**
     *
     * @see XenForo_Model_UserField::verifyUserFieldValue()
     */
    public function verifyUserFieldValue(array $field, &$value, &$error = '')
    {
        if (($field['field_type'] == 'radio' || $field['field_type'] == 'select' || $field['field_type'] == 'checkbox' ||
             $field['field_type'] == 'multiselect') &&
             (isset($field['field_choices_callback_class']) && $field['field_choices_callback_class']) &&
             (isset($field['field_choices_callback_method']) && $field['field_choices_callback_method'])) {
            $field['field_choices'] = serialize(
                call_user_func(
                    array(
                        $field['field_choices_callback_class'],
                        $field['field_choices_callback_method']
                    )));
        }

        $field['custom_field_type'] = 'user';

        if ($field['field_type'] == 'callback') {
            $field['old_field_type'] = 'callback';
            $field['field_type'] = 'textarea';
        }
        
        $verify = parent::verifyUserFieldValue($field, $value, $error);
        
        if (!empty($field['old_field_type'])) {
            $field['field_type'] = $field['old_field_type'];
            unset($field['old_field_type']);
        }
        
        return $verify;
    }

    /**
     *
     * @see XenForo_Model_UserField::getUserFieldTypes()
     */
    public function getUserFieldTypes()
    {
        $userFieldTypes = parent::getUserFieldTypes();

        $userFieldTypes['callback'] = array(
            'value' => 'callback',
            'label' => new XenForo_Phrase('php_callback')
        );

        return $userFieldTypes;
    }

    /**
     *
     * @return array [field type] => type group
     */
    public function getUserFieldTypeMap()
    {
        $userFieldTypeMap = parent::getUserFieldTypeMap();

        $userFieldTypeMap['callback'] = 'text';

        return $userFieldTypeMap;
    }

    /**
     * Gets the XML representation of a field, including customized templates.
     *
     * @param array $field
     *
     * @return DOMDocument
     */
    public function getFieldXml(array $field)
    {
        $document = new DOMDocument('1.0', 'utf-8');
        $document->formatOutput = true;

        $rootNode = $document->createElement('field');
        $this->_appendFieldXml($rootNode, $field);
        $document->appendChild($rootNode);

        $templatesNode = $document->createElement('templates');
        $rootNode->appendChild($templatesNode);
        $this->getModelFromCache('Waindigo_CustomFields_Model_Template')->appendTemplatesFieldXml($templatesNode,
            $field);

        $adminTemplatesNode = $document->createElement('admin_templates');
        $rootNode->appendChild($adminTemplatesNode);
        $this->getModelFromCache('Waindigo_CustomFields_Model_AdminTemplate')->appendAdminTemplatesFieldXml(
            $adminTemplatesNode, $field);

        $phrasesNode = $document->createElement('phrases');
        $rootNode->appendChild($phrasesNode);
        $this->getModelFromCache('XenForo_Model_Phrase')->appendPhrasesFieldXml($phrasesNode, $field);

        return $document;
    }

    /**
     * Appends the add-on field XML to a given DOM element.
     *
     * @param DOMElement $rootNode Node to append all elements to
     * @param string $addOnId Add-on ID to be exported
     */
    public function appendFieldsAddOnXml(DOMElement $rootNode, $addOnId)
    {
        $document = $rootNode->ownerDocument;

        $fields = $this->getUserFields(array(
            'addon_id' => $addOnId
        ));
        foreach ($fields as $field) {
            $fieldNode = $document->createElement('field');
            $this->_appendFieldXml($fieldNode, $field);
            $rootNode->appendChild($fieldNode);
        }
    }

    /**
     *
     * @param DOMElement $rootNode
     * @param array $field
     */
    protected function _appendFieldXml(DOMElement $rootNode, $field)
    {
        $document = $rootNode->ownerDocument;

        $rootNode->setAttribute('export_callback_method', $field['export_callback_method']);
        $rootNode->setAttribute('export_callback_class', $field['export_callback_class']);
        $rootNode->setAttribute('field_callback_method', $field['field_callback_method']);
        $rootNode->setAttribute('field_callback_class', $field['field_callback_class']);
        $rootNode->setAttribute('field_choices_callback_class', $field['field_choices_callback_class']);
        $rootNode->setAttribute('field_choices_callback_method', $field['field_choices_callback_method']);
        $rootNode->setAttribute('display_callback_method', $field['display_callback_method']);
        $rootNode->setAttribute('display_callback_class', $field['display_callback_class']);
        $rootNode->setAttribute('max_length', $field['max_length']);
        $rootNode->setAttribute('match_callback_method', $field['match_callback_method']);
        $rootNode->setAttribute('match_callback_class', $field['match_callback_class']);
        $rootNode->setAttribute('match_regex', $field['match_regex']);
        $rootNode->setAttribute('match_type', $field['match_type']);
        $rootNode->setAttribute('field_type', $field['field_type']);
        $rootNode->setAttribute('display_order', $field['display_order']);
        $rootNode->setAttribute('field_id', $field['field_id']);
        $rootNode->setAttribute('addon_id', $field['addon_id']);

        $titleNode = $document->createElement('title');
        $rootNode->appendChild($titleNode);
        $titleNode->appendChild(
            XenForo_Helper_DevelopmentXml::createDomCdataSection($document,
                new XenForo_Phrase('user_field_' . $field['field_id'])));

        $descriptionNode = $document->createElement('description');
        $rootNode->appendChild($descriptionNode);
        $descriptionNode->appendChild(
            XenForo_Helper_DevelopmentXml::createDomCdataSection($document,
                new XenForo_Phrase('user_field_' . $field['field_id'] . '_desc')));

        $displayTemplateNode = $document->createElement('display_template');
        $rootNode->appendChild($displayTemplateNode);
        $displayTemplateNode->appendChild(
            XenForo_Helper_DevelopmentXml::createDomCdataSection($document, $field['display_template']));

        $fieldChoicesNode = $document->createElement('field_choices');
        $rootNode->appendChild($fieldChoicesNode);
        if ($field['field_choices']) {
            $fieldChoices = unserialize($field['field_choices']);
            foreach ($fieldChoices as $fieldChoiceValue => $fieldChoiceText) {
                $fieldChoiceNode = $document->createElement('field_choice');
                $fieldChoiceNode->setAttribute('value', $fieldChoiceValue);
                $fieldChoiceNode->appendChild(
                    XenForo_Helper_DevelopmentXml::createDomCdataSection($document, $fieldChoiceText));
                $fieldChoicesNode->appendChild($fieldChoiceNode);
            }
        }
    }

    /**
     * Imports a field XML file.
     *
     * @param SimpleXMLElement $document
     * @param string $fieldGroupId
     * @param integer $overwriteFieldId
     *
     * @return array List of cache rebuilders to run
     */
    public function importFieldXml(SimpleXMLElement $document, $displayGroup = 0, $overwriteFieldId = 0)
    {
        if ($document->getName() != 'field') {
            throw new XenForo_Exception(new XenForo_Phrase('provided_file_is_not_valid_field_xml'), true);
        }

        $fieldId = (string) $document['field_id'];
        if ($fieldId === '') {
            throw new XenForo_Exception(new XenForo_Phrase('provided_file_is_not_valid_field_xml'), true);
        }

        $phraseModel = $this->_getPhraseModel();

        $overwriteField = array();
        if ($overwriteFieldId) {
            $overwriteField = $this->getUserFieldById($overwriteFieldId);
        }

        $db = $this->_getDb();
        XenForo_Db::beginTransaction($db);

        $dw = XenForo_DataWriter::create('XenForo_DataWriter_UserField');
        if (isset($overwriteField['field_id'])) {
            $dw->setExistingData($overwriteFieldId);
        } else {
            if ($overwriteFieldId) {
                $dw->set('field_id', $overwriteFieldId);
            } else {
                $dw->set('field_id', $fieldId);
            }
        }

        $dw->bulkSet(
            array(
                'display_order' => $document['display_order'],
                'field_type' => $document['field_type'],
                'match_type' => $document['match_type'],
                'match_regex' => $document['match_regex'],
                'match_callback_class' => $document['match_callback_class'],
                'match_callback_method' => $document['match_callback_method'],
                'max_length' => $document['max_length'],
                'display_callback_class' => $document['display_callback_class'],
                'display_callback_method' => $document['display_callback_method'],
                'field_choices_callback_class' => $document['field_choices_callback_class'],
                'field_choices_callback_method' => $document['field_choices_callback_method'],
                'field_callback_class' => $document['field_callback_class'],
                'field_callback_method' => $document['field_callback_method'],
                'export_callback_class' => $document['export_callback_class'],
                'export_callback_method' => $document['export_callback_method'],
                'display_template' => XenForo_Helper_DevelopmentXml::processSimpleXmlCdata($document->display_template)
            ));

        /* @var $addOnModel XenForo_Model_AddOn */
        $addOnModel = XenForo_Model::create('XenForo_Model_AddOn');
        $addOn = $addOnModel->getAddOnById($document['addon_id']);
        if (!empty($addOn)) {
            $dw->set('addon_id', $addOn['addon_id']);
        }

        $dw->setExtraData(Waindigo_CustomFields_DataWriter_ThreadField::DATA_TITLE,
            XenForo_Helper_DevelopmentXml::processSimpleXmlCdata($document->title));
        $dw->setExtraData(Waindigo_CustomFields_DataWriter_ThreadField::DATA_DESCRIPTION,
            XenForo_Helper_DevelopmentXml::processSimpleXmlCdata($document->description));

        $fieldChoices = XenForo_Helper_DevelopmentXml::fixPhpBug50670($document->field_choices->field_choice);

        foreach ($fieldChoices as $fieldChoice) {
            if ($fieldChoice && $fieldChoice['value']) {
                $fieldChoicesCombined[(string) $fieldChoice['value']] = XenForo_Helper_DevelopmentXml::processSimpleXmlCdata(
                    $fieldChoice);
            }
        }

        if (isset($fieldChoicesCombined))
            $dw->setFieldChoices($fieldChoicesCombined);

        $dw->save();

        $this->getModelFromCache('Waindigo_CustomFields_Model_Template')->importTemplatesFieldXml($document->templates);
        $this->getModelFromCache('Waindigo_CustomFields_Model_AdminTemplate')->importAdminTemplatesFieldXml(
            $document->admin_templates);
        $phraseModel->importPhrasesXml($document->phrases, 0);

        XenForo_Db::commit($db);

        if (XenForo_Application::$versionId < 1020000) {
            return array(
                'Template',
                'Phrase',
                'AdminTemplate'
            );
        }
        XenForo_Application::defer('Atomic',
            array(
                'simple' => array(
                    'Phrase',
                    'TemplateReparse',
                    'Template',
                    'AdminTemplateReparse',
                    'AdminTemplate'
                )
            ), 'customFieldRebuild', true);
        return true;
    }

    /**
     * Imports the add-on fields XML.
     *
     * @param SimpleXMLElement $xml XML element pointing to the root of the data
     * @param string $addOnId Add-on to import for
     * @param integer $maxExecution Maximum run time in seconds
     * @param integer $offset Number of elements to skip
     *
     * @return boolean integer on completion; false if the XML isn't correct;
     * integer otherwise with new offset value
     */
    public function importFieldsAddOnXml(SimpleXMLElement $xml, $addOnId, $maxExecution = 0, $offset = 0)
    {
        $db = $this->_getDb();

        XenForo_Db::beginTransaction($db);

        $startTime = microtime(true);

        $fields = XenForo_Helper_DevelopmentXml::fixPhpBug50670($xml->field);

        $current = 0;
        $restartOffset = false;
        foreach ($fields as $field) {
            $current++;
            if ($current <= $offset) {
                continue;
            }

            $fieldId = (string) $field['field_id'];

            if (!$field['addon_id']) {
                $field->addAttribute('addon_id', $addOnId);
            }

            $this->importFieldXml($field, 0, $fieldId);

            if ($maxExecution && (microtime(true) - $startTime) > $maxExecution) {
                $restartOffset = $current;
                break;
            }
        }

        XenForo_Db::commit($db);

        return ($restartOffset ? $restartOffset : true);
    }
}
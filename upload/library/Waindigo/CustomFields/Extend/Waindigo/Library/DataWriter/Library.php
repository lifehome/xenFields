<?php

/**
 *
 * @see Waindigo_Library_DataWriter_Library
 */
class Waindigo_CustomFields_Extend_Waindigo_Library_DataWriter_Library extends XFCP_Waindigo_CustomFields_Extend_Waindigo_Library_DataWriter_Library
{

    const DATA_THREAD_FIELD_DEFINITIONS = 'threadFields';

    /**
     * The custom fields to be updated.
     * Use setCustomFields to manage these.
     *
     * @var array
     */
    protected $_updateCustomFields = array();

    /**
     *
     * @see Waindigo_Library_DataWriter_Library::_getFields()
     */
    protected function _getFields()
    {
        $fields = parent::_getFields();

        $fields['xf_library']['custom_fields'] = array(
            'type' => self::TYPE_SERIALIZED,
            'default' => ''
        );
        $fields['xf_library']['required_fields'] = array(
            'type' => self::TYPE_SERIALIZED,
            'default' => ''
        );

        return $fields;
    }

    /**
     *
     * @see Waindigo_Library_DataWriter_Library::_preSave()
     */
    protected function _preSave()
    {
        if (isset($GLOBALS['Waindigo_Library_ControllerAdmin_Library'])) {
            /* @var $controller Waindigo_Library_ControllerAdmin_Library */
            $controller = $GLOBALS['Waindigo_Library_ControllerAdmin_Library'];

            $customFields = $controller->getInput()->filterSingle('custom_fields', XenForo_Input::ARRAY_SIMPLE);
            $customFieldsShown = $controller->getInput()->filterSingle('custom_fields_shown', XenForo_Input::STRING,
                array(
                    'array' => true
                ));
            $this->setCustomFields($customFields, $customFieldsShown);

            $requiredFields = $controller->getInput()->filterSingle('required_fields', XenForo_Input::ARRAY_SIMPLE);
            $this->set('required_fields', serialize($requiredFields));
        }

        parent::_preSave();
    }

    /**
     *
     * @see Waindigo_Library_DataWriter_Library::_postSave()
     */
    protected function _postSave()
    {
        if (isset($GLOBALS['Waindigo_Library_ControllerAdmin_Library'])) {
            /* @var $controller Waindigo_Library_ControllerAdmin_Library */
            $controller = $GLOBALS['Waindigo_Library_ControllerAdmin_Library'];

            $fieldIds = $controller->getInput()->filterSingle('available_fields', XenForo_Input::STRING,
                array(
                    'array' => true
                ));
            $this->_getThreadFieldModel()->updateThreadFieldForumAssociationByForum($this->get('node_id'), $fieldIds);

            $templates = $controller->getInput()->filter(
                array(
                    'thread_header' => XenForo_Input::STRING,
                    'thread_footer' => XenForo_Input::STRING
                ));

            $headerName = '_header_node.' . $this->get('node_id');
            $footerName = '_footer_node.' . $this->get('node_id');

            $oldTemplates = $this->_getTemplateModel()->getTemplatesInStyleByTitles(
                array(
                    $headerName,
                    $footerName
                ));

            /* @var $templateWriter XenForo_DataWriter_Template */
            $templateWriter = XenForo_DataWriter::create('XenForo_DataWriter_Template');
            if (isset($oldTemplates[$headerName])) {
                $templateWriter->setExistingData($oldTemplates[$headerName]);
            }
            $templateWriter->set('title', $headerName);
            $templateWriter->set('style_id', 0);
            $templateWriter->set('template', $templates['thread_header']);
            $templateWriter->save();

            /* @var $templateWriter XenForo_DataWriter_Template */
            $templateWriter = XenForo_DataWriter::create('XenForo_DataWriter_Template');
            if (isset($oldTemplates[$footerName])) {
                $templateWriter->setExistingData($oldTemplates[$footerName]);
            }
            $templateWriter->set('title', $footerName);
            $templateWriter->set('style_id', 0);
            $templateWriter->set('template', $templates['thread_footer']);
            $templateWriter->save();

            $this->_updateCustomFields = unserialize($this->get('custom_fields'));
        }

        $this->updateCustomFields();

        parent::_postSave();
    }

    /**
     *
     * @param array $fieldValues
     * @param array $fieldsShown
     */
    public function setCustomFields(array $fieldValues, array $fieldsShown = null)
    {
        if ($fieldsShown === null) {
            // not passed - assume keys are all there
            $fieldsShown = array_keys($fieldValues);
        }

        $fieldModel = $this->_getThreadFieldModel();
        $fields = $this->_getThreadFieldDefinitions();
        $callbacks = array();

        if ($this->get('node_id') && !$this->_importMode) {
            $existingValues = $fieldModel->getDefaultThreadFieldValues($this->get('node_id'));
        } else {
            $existingValues = array();
        }

        $finalValues = array();

        foreach ($fieldsShown as $fieldId) {
            if (!isset($fields[$fieldId])) {
                continue;
            }

            $field = $fields[$fieldId];
            if ($field['field_type'] == 'callback') {
                if (isset($fieldValues[$fieldId])) {
                    if (is_array($fieldValues[$fieldId])) {
                        $fieldValues[$fieldId] = serialize($fieldValues[$fieldId]);
                        $callbacks[] = $fieldId;
                    }
                }
                $field['field_type'] = 'textbox';
            }
            $multiChoice = ($field['field_type'] == 'checkbox' || $field['field_type'] == 'multiselect');

            if ($multiChoice) {
                // multi selection - array
                $value = (isset($fieldValues[$fieldId]) && is_array($fieldValues[$fieldId])) ? $fieldValues[$fieldId] : array();
            } else {
                // single selection - string
                $value = (isset($fieldValues[$fieldId]) ? strval($fieldValues[$fieldId]) : '');
            }

            $existingValue = (isset($existingValues[$fieldId]) ? $existingValues[$fieldId] : null);

            if (!$this->_importMode) {
                $error = '';
                $valid = $fieldModel->verifyThreadFieldValue($field, $value, $error);
                if (!$valid) {
                    $this->error($error, "custom_field_$fieldId");
                    continue;
                }
            }

            foreach ($callbacks as $callbackFieldId) {
                if (isset($fieldValues[$callbackFieldId])) {
                    if (is_array($fieldValues[$callbackFieldId])) {
                        $value = unserialize($value);
                    }
                }
            }

            if ($value !== $existingValue) {
                $finalValues[$fieldId] = $value;
            }
        }

        $this->_updateCustomFields = $finalValues + $this->_updateCustomFields;
        $this->set('custom_fields', $finalValues + $existingValues);
    }

    public function updateCustomFields()
    {
        if ($this->_updateCustomFields) {
            $nodeId = $this->get('node_id');

            foreach ($this->_updateCustomFields as $fieldId => $value) {
                if (is_array($value)) {
                    $value = serialize($value);
                }
                $this->_db->query(
                    '
					UPDATE xf_forum_field
					SET field_value = ?
					WHERE node_id = ? AND field_id = ?
				',
                    array(
                        $value,
                        $nodeId,
                        $fieldId
                    ));
            }
        }
    }

    /**
     * Fetch (and cache) thread field definitions
     *
     * @return array
     */
    protected function _getThreadFieldDefinitions()
    {
        $fields = $this->getExtraData(self::DATA_THREAD_FIELD_DEFINITIONS);

        if (is_null($fields)) {
            $fields = $this->_getThreadFieldModel()->getThreadFields();

            $this->setExtraData(self::DATA_THREAD_FIELD_DEFINITIONS, $fields);
        }

        return $fields;
    }

    /**
     *
     * @return Waindigo_CustomFields_Model_ThreadField
     */
    protected function _getThreadFieldModel()
    {
        return $this->getModelFromCache('Waindigo_CustomFields_Model_ThreadField');
    }

    /**
     *
     * @return XenForo_Model_Template
     */
    protected function _getTemplateModel()
    {
        return $this->getModelFromCache('XenForo_Model_Template');
    }
}
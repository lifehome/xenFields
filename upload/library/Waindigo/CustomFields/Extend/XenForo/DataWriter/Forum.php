<?php

/**
 *
 * @see XenForo_DataWriter_Forum
 */
class Waindigo_CustomFields_Extend_XenForo_DataWriter_Forum extends XFCP_Waindigo_CustomFields_Extend_XenForo_DataWriter_Forum
{

    const DATA_THREAD_FIELD_DEFINITIONS = 'threadFields';

    const DATA_SOCIAL_FORUM_FIELD_DEFINITIONS = 'socialForumFields';

    /**
     * The custom fields to be updated.
     * Use setCustomFields/setCustomSocialForumFields to manage these.
     *
     * @var array
     */
    protected $_updateCustomFields = array();

    protected $_updateCustomSocialForumFields = array();

    /**
     *
     * @see XenForo_DataWriter_Forum::_getFields()
     */
    protected function _getFields()
    {
        $fields = parent::_getFields();

        $fields['xf_forum']['custom_fields'] = array(
            'type' => self::TYPE_SERIALIZED,
            'default' => ''
        );
        $fields['xf_forum']['required_fields'] = array(
            'type' => self::TYPE_SERIALIZED,
            'default' => ''
        );
        $fields['xf_forum']['custom_social_forum_fields'] = array(
            'type' => self::TYPE_SERIALIZED,
            'default' => ''
        );
        $fields['xf_forum']['required_social_forum_fields'] = array(
            'type' => self::TYPE_SERIALIZED,
            'default' => ''
        );

        return $fields;
    }

    /**
     *
     * @see XenForo_DataWriter_Forum::_preSave()
     */
    protected function _preSave()
    {
        if (isset($GLOBALS['XenForo_ControllerAdmin_Forum'])) {
            /* @var $controller XenForo_ControllerAdmin_Forum */
            $controller = $GLOBALS['XenForo_ControllerAdmin_Forum'];

            $customFields = $controller->getInput()->filterSingle('custom_fields', XenForo_Input::ARRAY_SIMPLE);
            $customFieldsShown = $controller->getInput()->filterSingle('custom_fields_shown', XenForo_Input::STRING,
                array(
                    'array' => true
                ));
            $this->setCustomFields($customFields, $customFieldsShown);

            $requiredFields = $controller->getInput()->filterSingle('required_fields', XenForo_Input::ARRAY_SIMPLE);
            $this->set('required_fields', serialize($requiredFields));
        }

        if (isset($GLOBALS['Waindigo_SocialGroups_ControllerAdmin_SocialCategory'])) {
            /* @var $controller Waindigo_SocialGroups_ControllerAdmin_SocialCategory */
            $controller = $GLOBALS['Waindigo_SocialGroups_ControllerAdmin_SocialCategory'];

            $customSocialForumFields = $controller->getInput()->filterSingle('custom_social_forum_fields',
                XenForo_Input::ARRAY_SIMPLE);
            $customSocialForumFieldsShown = $controller->getInput()->filterSingle('custom_social_forum_fields_shown',
                XenForo_Input::STRING, array(
                    'array' => true
                ));
            $this->setCustomSocialForumFields($customSocialForumFields, $customSocialForumFieldsShown);

            $requiredSocialForumFields = $controller->getInput()->filterSingle('required_social_forum_fields',
                XenForo_Input::ARRAY_SIMPLE);
            $this->set('required_social_forum_fields', serialize($requiredSocialForumFields));
        }

        parent::_preSave();
    }

    /**
     *
     * @see XenForo_DataWriter_Forum::_postSave()
     */
    protected function _postSave()
    {
        if (isset($GLOBALS['XenForo_ControllerAdmin_Forum'])) {
            /* @var $controller XenForo_ControllerAdmin_Forum */
            $controller = $GLOBALS['XenForo_ControllerAdmin_Forum'];

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

        if (isset($GLOBALS['Waindigo_SocialGroups_ControllerAdmin_SocialCategory'])) {
            /* @var $controller Waindigo_SocialGroups_ControllerAdmin_SocialCategory */
            $controller = $GLOBALS['Waindigo_SocialGroups_ControllerAdmin_SocialCategory'];

            $fieldIds = $controller->getInput()->filterSingle('available_social_forum_fields', XenForo_Input::STRING,
                array(
                    'array' => true
                ));
            $this->_getSocialForumFieldModel()->updateSocialForumFieldCategoryAssociationByCategory(
                $this->get('node_id'), $fieldIds);

            $templates = $controller->getInput()->filter(
                array(
                    'social_forum_header' => XenForo_Input::STRING,
                    'social_forum_footer' => XenForo_Input::STRING
                ));

            $headerName = '_header_social_forum_node.' . $this->get('node_id');
            $footerName = '_footer_social_forum_node.' . $this->get('node_id');

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
            $templateWriter->set('template', $templates['social_forum_header']);
            $templateWriter->save();

            /* @var $templateWriter XenForo_DataWriter_Template */
            $templateWriter = XenForo_DataWriter::create('XenForo_DataWriter_Template');
            if (isset($oldTemplates[$footerName])) {
                $templateWriter->setExistingData($oldTemplates[$footerName]);
            }
            $templateWriter->set('title', $footerName);
            $templateWriter->set('style_id', 0);
            $templateWriter->set('template', $templates['social_forum_footer']);
            $templateWriter->save();

            $this->_updateCustomSocialForumFields = unserialize($this->get('custom_social_forum_fields'));
        }

        $this->updateCustomFields();
        $this->updateCustomSocialForumFields();

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

    /**
     *
     * @param array $fieldValues
     * @param array $fieldsShown
     */
    public function setCustomSocialForumFields(array $fieldValues, array $fieldsShown = null)
    {
        if ($fieldsShown === null) {
            // not passed - assume keys are all there
            $fieldsShown = array_keys($fieldValues);
        }

        $fieldModel = $this->_getSocialForumFieldModel();
        $fields = $this->_getSocialForumFieldDefinitions();
        $callbacks = array();

        if ($this->get('node_id') && !$this->_importMode) {
            $existingValues = $fieldModel->getDefaultSocialForumFieldValues($this->get('node_id'));
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
                $valid = $fieldModel->verifySocialForumFieldValue($field, $value, $error);
                if (!$valid) {
                    $this->error($error, "custom_social_forum_field_$fieldId");
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

        $this->_updateCustomSocialForumFields = $finalValues + $this->_updateCustomSocialForumFields;
        $this->set('custom_social_forum_fields', $finalValues + $existingValues);
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

    public function updateCustomSocialForumFields()
    {
        if ($this->_updateCustomSocialForumFields) {
            $nodeId = $this->get('node_id');

            foreach ($this->_updateCustomSocialForumFields as $fieldId => $value) {
                if (is_array($value)) {
                    $value = serialize($value);
                }
                $this->_db->query(
                    '
					UPDATE xf_social_category_field
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
     * Fetch (and cache) social forum field definitions
     *
     * @return array
     */
    protected function _getSocialForumFieldDefinitions()
    {
        $fields = $this->getExtraData(self::DATA_SOCIAL_FORUM_FIELD_DEFINITIONS);

        if (is_null($fields)) {
            $fields = $this->_getSocialForumFieldModel()->getSocialForumFields();

            $this->setExtraData(self::DATA_SOCIAL_FORUM_FIELD_DEFINITIONS, $fields);
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
     * @return Waindigo_CustomFields_Model_SocialForumField
     */
    protected function _getSocialForumFieldModel()
    {
        return $this->getModelFromCache('Waindigo_CustomFields_Model_SocialForumField');
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
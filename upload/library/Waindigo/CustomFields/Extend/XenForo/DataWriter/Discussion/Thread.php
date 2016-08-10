<?php

/**
 *
 * @see XenForo_DataWriter_Discussion_Thread
 */
class Waindigo_CustomFields_Extend_XenForo_DataWriter_Discussion_Thread_Base extends XFCP_Waindigo_CustomFields_Extend_XenForo_DataWriter_Discussion_Thread
{

    const DATA_THREAD_FIELD_DEFINITIONS = 'threadFields';

    /**
     * The custom fields to be updated.
     * Use setCustomFields to manage this.
     *
     * @var array
     */
    protected $_updateCustomFields = array();

    /**
     *
     * @see XenForo_DataWriter_Discussion_Thread::_getFields()
     */
    protected function _getFields()
    {
        $fields = parent::_getFields();

        $fields['xf_thread']['custom_fields'] = array(
            'type' => self::TYPE_SERIALIZED,
            'default' => ''
        );

        return $fields;
    }

    /**
     *
     * @see XenForo_DataWriter_Discussion_Thread::_discussionPreSave()
     */
    protected function _discussionPreSave()
    {
        $node = $this->_getForumData();

        if (isset($GLOBALS['XenForo_ControllerPublic_Forum'])) {
            /* @var $controller XenForo_ControllerPublic_Forum */
            $controller = $GLOBALS['XenForo_ControllerPublic_Forum'];

            $fieldValues = array();
            if (isset($node['custom_fields']) && $node['custom_fields']) {
                $fieldValues = unserialize($node['custom_fields']);
            }

            $customFields = $controller->getInput()->filterSingle('custom_fields', XenForo_Input::ARRAY_SIMPLE);
            $customFieldsShown = $controller->getInput()->filterSingle('custom_fields_shown', XenForo_Input::STRING,
                array(
                    'array' => true
                ));

            foreach ($fieldValues as $fieldName => $fieldValue) {
                if (!in_array($fieldName, $customFieldsShown)) {
                    $customFieldsShown[] = $fieldName;
                    $customFields[$fieldName] = $fieldValue;
                }
            }

            $this->setCustomFields($customFields, $customFieldsShown);
        }

        if (isset($GLOBALS['XenForo_ControllerPublic_Thread'])) {
            /* @var $controller XenForo_ControllerPublic_Thread */
            $controller = $GLOBALS['XenForo_ControllerPublic_Thread'];

            if (strtolower($controller->getRouteMatch()->getAction()) == 'save') {
                $customFields = $controller->getInput()->filterSingle('custom_fields', XenForo_Input::ARRAY_SIMPLE);
                $customFieldsShown = $controller->getInput()->filterSingle('custom_fields_shown', XenForo_Input::STRING,
                    array(
                        'array' => true
                    ));

                $this->setCustomFields($customFields, $customFieldsShown);
            }
        }

        parent::_discussionPreSave();
    }

    protected function _customFieldsPostSave(array $messages = array())
    {
        if (XenForo_Application::$versionId < 1020000) {
            parent::_discussionPostSave($messages);
        } else {
            parent::_discussionPostSave();
        }

        $this->updateCustomFields();

        $this->_associateCustomFieldsAttachments();
    }

    /**
     *
     * @param array $fieldValues
     * @param array $fieldsShown
     */
    public function setCustomFields(array $fieldValues, array $fieldsShown = null)
    {
        $node = $this->_getForumData();

        $nodeRequiredFields = array();
        if (isset($node['required_fields']) && $node['required_fields']) {
            $nodeRequiredFields = unserialize($node['required_fields']);
        }

        if ($fieldsShown === null) {
            // not passed - assume keys are all there
            $fieldsShown = array_keys($fieldValues);
        }

        $fieldModel = $this->_getFieldModel();
        $fields = $this->_getThreadFieldDefinitions();
        $callbacks = array();

        if ($this->get('thread_id') && !$this->_importMode) {
            $existingValues = $fieldModel->getThreadFieldValues($this->get('thread_id'));
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

                if (in_array($fieldId, $nodeRequiredFields) && ($value === '' || $value === array())) {
                    $this->error(new XenForo_Phrase('please_enter_value_for_all_required_fields'), "required");
                    continue;
                }
            }

            if ($value !== $existingValue) {
                $finalValues[$fieldId] = $value;
            }
        }

        foreach ($callbacks as $callbackFieldId) {
            if (isset($fieldValues[$callbackFieldId])) {
                if (is_array($fieldValues[$callbackFieldId])) {
                    $value = unserialize($value);
                }
            }
        }

        $this->_updateCustomFields = $finalValues + $this->_updateCustomFields;
        $this->set('custom_fields', $finalValues + $existingValues);
    }

    public function updateCustomFields()
    {
        if ($this->_updateCustomFields) {
            $threadId = $this->get('thread_id');

            // $pairedFields = array();
            foreach ($this->_updateCustomFields as $fieldId => $value) {
                if (is_array($value)) {
                    $value = serialize($value);
                }
                $this->_db->query(
                    '
                        INSERT INTO xf_thread_field_value
                        (thread_id, field_id, field_value)
                        VALUES
                        (?, ?, ?)
                        ON DUPLICATE KEY UPDATE
                        field_value = VALUES(field_value)
                    ',
                    array(
                        $threadId,
                        $fieldId,
                        $value
                    ));
            }
        }
    }

    protected function _associateCustomFieldsAttachments()
    {
        $fieldAttachmentModel = $this->getModelFromCache('Waindigo_CustomFields_Model_Attachment');

        $fieldAttachmentModel->associateAttachments($this->get('thread_id'), 'thread');
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
            $fields = $this->_getFieldModel()->getThreadFields();

            $this->setExtraData(self::DATA_THREAD_FIELD_DEFINITIONS, $fields);
        }

        return $fields;
    }

    protected function _customFieldsPostDelete(array $messages = array())
    {
        $db = $this->_db;
        $threadId = $this->get('thread_id');
        $threadIdQuoted = $db->quote($threadId);

        $db->delete('xf_thread_field_value', "thread_id = $threadIdQuoted");

        if (XenForo_Application::$versionId < 1020000) {
            parent::_discussionPostDelete($messages);
        } else {
            parent::_discussionPostDelete();
        }
    }

    /**
     *
     * @return XenForo_Model_Forum
     */
    protected function _getForumModel()
    {
        return $this->getModelFromCache('XenForo_Model_Forum');
    }

    /**
     *
     * @return Waindigo_CustomFields_Model_ThreadField
     */
    protected function _getFieldModel()
    {
        return $this->getModelFromCache('Waindigo_CustomFields_Model_ThreadField');
    }
}

if (XenForo_Application::$versionId < 1020000) {

    class Waindigo_CustomFields_Extend_XenForo_DataWriter_Discussion_Thread extends Waindigo_CustomFields_Extend_XenForo_DataWriter_Discussion_Thread_Base
    {

        /**
         *
         * @see XenForo_DataWriter_Discussion_Thread::_discussionPostSave()
         */
        protected function _discussionPostSave(array $messages)
        {
            $this->_customFieldsPostSave($messages);
        }

        /**
         *
         * @see XenForo_DataWriter_Discussion_Thread::_discussionPostDelete()
         */
        protected function _discussionPostDelete(array $messages)
        {
            $this->_customFieldsPostDelete($messages);
        }
    }
} else {

    class Waindigo_CustomFields_Extend_XenForo_DataWriter_Discussion_Thread extends Waindigo_CustomFields_Extend_XenForo_DataWriter_Discussion_Thread_Base
    {

        /**
         *
         * @see XenForo_DataWriter_Discussion_Thread::_discussionPostSave()
         */
        protected function _discussionPostSave()
        {
            $this->_customFieldsPostSave();
        }

        /**
         *
         * @see XenForo_DataWriter_Discussion_Thread::_discussionPostDelete()
         */
        protected function _discussionPostDelete()
        {
            $this->_customFieldsPostDelete();
        }
    }
}
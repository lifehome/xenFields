<?php

/**
 *
 * @see XenResource_DataWriter_Resource
 */
class Waindigo_CustomFields_Extend_XenResource_DataWriter_Resource_Base extends XFCP_Waindigo_CustomFields_Extend_XenResource_DataWriter_Resource
{

    /**
     *
     * @see XenForo_DataWriter_User::_postSave
     */
    protected function _postSave()
    {
        $this->_associateCustomFieldsAttachments();

        parent::_postSave();
    }

    protected function _associateCustomFieldsAttachments()
    {
        $fieldAttachmentModel = $this->getModelFromCache('Waindigo_CustomFields_Model_Attachment');

        $fieldAttachmentModel->associateAttachments($this->get('resource_id'), 'resource');
    }
}

$rmVersion = 0;
if (XenForo_Application::$versionId >= 1020000) {
    $addOns = XenForo_Application::get('addOns');
    if (isset($addOns['XenResource'])) {
        $rmVersion = $addOns['XenResource'] >= 1010000;
    }
}

if ($rmVersion < 1010000) {

    class Waindigo_CustomFields_Extend_XenResource_DataWriter_Resource extends Waindigo_CustomFields_Extend_XenResource_DataWriter_Resource_Base
    {

        const DATA_FIELD_DEFINITIONS = 'resourceFields';

        /**
         * The custom fields to be updated.
         * Use setCustomFields to manage this.
         *
         * @var array
         */
        protected $_updateCustomFields = array();

        /**
         *
         * @see XenResource_DataWriter_Resource::_getFields()
         */
        protected function _getFields()
        {
            $fields = parent::_getFields();

            $fields['xf_resource']['custom_resource_fields'] = array(
                'type' => self::TYPE_SERIALIZED,
                'default' => ''
            );

            return $fields;
        }

        /**
         *
         * @see XenResource_DataWriter_Resource::_preSave()
         */
        protected function _preSave()
        {
            /* @var $categoryModel XenResource_Model_Category */
            $categoryModel = $this->_getCategoryModel();
            $category = $categoryModel->getCategoryById($this->get('resource_category_id'));

            if (isset($GLOBALS['XenResource_ControllerPublic_Resource'])) {
                /* @var $controller XenResource_ControllerPublic_Resource */
                $controller = $GLOBALS['XenResource_ControllerPublic_Resource'];

                $fieldValues = array();
                if (isset($category['category_resource_fields']) && $category['category_resource_fields']) {
                    $fieldValues = unserialize($category['category_resource_fields']);
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

            $customFields = $this->get('custom_resource_fields');
            if ($customFields) {
                $customFields = unserialize($customFields);
            }

            $categoryRequiredFields = array();
            if (isset($category['required_fields']) && $category['required_fields']) {
                $categoryRequiredFields = unserialize($category['required_fields']);
            }

            foreach ($categoryRequiredFields as $fieldId) {
                if (!isset($customFields[$fieldId]) ||
                     ($customFields[$fieldId] === '' || $customFields[$fieldId] === array())) {
                    $this->error(new XenForo_Phrase('please_enter_value_for_all_required_fields'),
                        "custom_field_$fieldId");
                    continue;
                }
            }

            parent::_preSave();
        }

        protected function _postSave()
        {
            $this->updateCustomFields();

            parent::_postSave();
        }

        public function setCustomFields(array $fieldValues, array $fieldsShown = null)
        {
            if ($fieldsShown === null) {
                // not passed - assume keys are all there
                $fieldsShown = array_keys($fieldValues);
            }

            $fieldModel = $this->_getFieldModel();
            $fields = $this->_getResourceFieldDefinitions();
            $callbacks = array();

            if ($this->get('resource_id') && !$this->_importMode) {
                $existingValues = $fieldModel->getResourceFieldValues($this->get('resource_id'));
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
                    $valid = $fieldModel->verifyResourceFieldValue($field, $value, $error);
                    if (!$valid) {
                        $this->error($error, "custom_field_$fieldId");
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
            $this->set('custom_resource_fields', $finalValues + $existingValues);
        }

        public function updateCustomFields()
        {
            if ($this->_updateCustomFields) {
                $resourceId = $this->get('resource_id');

                // $pairedFields = array();
                foreach ($this->_updateCustomFields as $fieldId => $value) {
                    if (is_array($value)) {
                        $value = serialize($value);
                    }
                    $this->_db->query(
                        '
                            INSERT INTO xf_resource_field_value
                            (resource_id, field_id, field_value)
                            VALUES
                            (?, ?, ?)
                            ON DUPLICATE KEY UPDATE
                                field_value = VALUES(field_value)
                        ',
                        array(
                            $resourceId,
                            $fieldId,
                            $value
                        ));
                }
            }
        }

        /**
         * Fetch (and cache) resource field definitions
         *
         * @return array
         */
        protected function _getResourceFieldDefinitions()
        {
            $fields = $this->getExtraData(self::DATA_FIELD_DEFINITIONS);

            if (is_null($fields)) {
                $fields = $this->_getFieldModel()->getResourceFields();

                $this->setExtraData(self::DATA_FIELD_DEFINITIONS, $fields);
            }

            return $fields;
        }

        /**
         *
         * @see XenResource_DataWriter_Resource::_postDelete()
         */
        protected function _postDelete()
        {
            parent::_postDelete();

            $db = $this->_db;
            $resourceId = $this->get('resource_id');
            $resourceIdQuoted = $db->quote($resourceId);

            $db->delete('xf_resource_field_value', 'resource_id = ' . $resourceIdQuoted);
        }

        /**
         *
         * @return Waindigo_CustomFields_Model_ResourceField
         */
        protected function _getFieldModel()
        {
            return $this->getModelFromCache('Waindigo_CustomFields_Model_ResourceField');
        }
    }
} else {

    class Waindigo_CustomFields_Extend_XenResource_DataWriter_Resource extends Waindigo_CustomFields_Extend_XenResource_DataWriter_Resource_Base
    {

        /**
         *
         * @see XenResource_DataWriter_Resource::setCustomFields()
         */
        public function setCustomFields(array $fieldValues, array $fieldsShown = null)
        {
            if ($fieldsShown === null) {
                // not passed - assume keys are all there
                $fieldsShown = array_keys($fieldValues);
            }

            $fieldModel = $this->_getFieldModel();
            $fields = $fieldModel->getResourceFieldsForEdit($this->get('resource_category_id'));
            $callbacks = array();

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
                }
            }

            parent::setCustomFields($fieldValues, $fieldsShown);

            $customFields = $this->get('custom_fields');
            if ($customFields && !empty($callbacks)) {
                $customFields = unserialize($customFields);
                foreach ($callbacks as $fieldId) {
                    if (!isset($fields[$fieldId])) {
                        continue;
                    }

                    $field = $fields[$fieldId];

                    if (isset($fieldValues[$fieldId])) {
                        if (is_array($fieldValues[$fieldId])) {
                            $this->_updateCustomFields[$fieldId] = unserialize($this->_updateCustomFields[$fieldId]);
                            $customFields[$fieldId] = unserialize($customFields[$fieldId]);
                        }
                    }
                }
                $this->set('custom_fields', $customFields);
            }
        }

        public function rebuildCounters()
        {
            $this->rebuildCustomFields();

            parent::rebuildCounters();
        }

        public function rebuildCustomFields()
        {
            $customFields = $this->_getFieldModel()->getResourceFieldValues($this->get('resource_id'));

            $this->_updateCustomFields = $customFields;
            $this->set('custom_resource_fields', $this->_updateCustomFields);
        }
    }
}
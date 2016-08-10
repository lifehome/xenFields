<?php

/**
 *
 * @see XenResource_DataWriter_Category
 */
class Waindigo_CustomFields_Extend_XenResource_DataWriter_Category_Base extends XFCP_Waindigo_CustomFields_Extend_XenResource_DataWriter_Category
{

    const DATA_FIELD_DEFINITIONS = 'resourceFields';

    /**
     * The custom fields to be updated.
     * Use setCustomFields to manage these.
     *
     * @var array
     */
    protected $_updateCustomFields = array();

    /**
     *
     * @see XenResource_DataWriter_Category::_getFields()
     */
    protected function _getFields()
    {
        $fields = parent::_getFields();

        $fields['xf_resource_category']['required_fields'] = array(
            'type' => self::TYPE_SERIALIZED,
            'default' => ''
        );

        $fields['xf_resource_category']['category_resource_fields'] = array(
            'type' => self::TYPE_SERIALIZED,
            'default' => ''
        );

        return $fields;
    }

    /**
     *
     * @see XenResource_DataWriter_Category::_preSave()
     */
    protected function _preSave()
    {
        if (isset($GLOBALS['XenResource_ControllerAdmin_Category'])) {
            /* @var $controller XenResource_ControllerAdmin_Category */
            $controller = $GLOBALS['XenResource_ControllerAdmin_Category'];

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
     * @see XenResource_DataWriter_Category::_postSave()
     */
    protected function _postSave()
    {
        if (isset($GLOBALS['XenResource_ControllerAdmin_Category'])) {
            /* @var $controller XenResource_ControllerAdmin_Category */
            $controller = $GLOBALS['XenResource_ControllerAdmin_Category'];

            $templates = $controller->getInput()->filter(
                array(
                    'header' => XenForo_Input::STRING,
                    'footer' => XenForo_Input::STRING
                ));

            $headerName = '_header_resource_category.' . $this->get('resource_category_id');
            $footerName = '_footer_resource_category.' . $this->get('resource_category_id');

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
            $templateWriter->set('template', $templates['header']);
            $templateWriter->save();

            /* @var $templateWriter XenForo_DataWriter_Template */
            $templateWriter = XenForo_DataWriter::create('XenForo_DataWriter_Template');
            if (isset($oldTemplates[$footerName])) {
                $templateWriter->setExistingData($oldTemplates[$footerName]);
            }
            $templateWriter->set('title', $footerName);
            $templateWriter->set('style_id', 0);
            $templateWriter->set('template', $templates['footer']);
            $templateWriter->save();

            $this->_updateCustomFields = unserialize($this->get('category_resource_fields'));
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

        $fieldModel = $this->_getFieldModel();
        $fields = $this->_getResourceFieldDefinitions();
        $callbacks = array();

        if ($this->get('node_id') && !$this->_importMode) {
            $existingValues = $fieldModel->getDefaultResourceFieldValues($this->get('node_id'));
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
        $this->set('category_resource_fields', $finalValues + $existingValues);
    }

    public function updateCustomFields()
    {
        if ($this->_updateCustomFields) {
            $categoryId = $this->get('resource_category_id');

            foreach ($this->_updateCustomFields as $fieldId => $value) {
                if (is_array($value)) {
                    $value = serialize($value);
                }
                $this->_db->query(
                    '
					UPDATE xf_resource_field_category
					SET field_value = ?
					WHERE resource_category_id = ? AND field_id = ?
				',
                    array(
                        $value,
                        $categoryId,
                        $fieldId
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
     * @return XenForo_Model_Template
     */
    protected function _getTemplateModel()
    {
        return $this->getModelFromCache('XenForo_Model_Template');
    } /* END Waindigo_CustomFields_Extend_XenResource_DataWriter_Category::_getTemplateModel() */
}

$addOns = XenForo_Application::get('addOns');

if ($addOns['XenResource'] < 1010000) {

    class Waindigo_CustomFields_Extend_XenResource_DataWriter_Category extends Waindigo_CustomFields_Extend_XenResource_DataWriter_Category_Base
    {

        /**
         *
         * @see XenResource_DataWriter_Category::_postSave()
         */
        protected function _postSave()
        {
            if (isset($GLOBALS['XenResource_ControllerAdmin_Category'])) {
                /* @var $controller XenResource_ControllerAdmin_Category */
                $controller = $GLOBALS['XenResource_ControllerAdmin_Category'];

                $fieldIds = $controller->getInput()->filterSingle('available_fields', XenForo_Input::STRING,
                    array(
                        'array' => true
                    ));
                $this->_getFieldModel()->updateResourceFieldCategoryAssociationByCategory(
                    $this->get('resource_category_id'), $fieldIds);
            }

            parent::_postSave();
        }
    }
} else {

    class Waindigo_CustomFields_Extend_XenResource_DataWriter_Category extends Waindigo_CustomFields_Extend_XenResource_DataWriter_Category_Base
    {
    }
}
<?php

/**
 *
 * @see Waindigo_SocialGroups_DataWriter_SocialForum
 */
class Waindigo_CustomFields_Extend_Waindigo_SocialGroups_DataWriter_SocialForum extends XFCP_Waindigo_CustomFields_Extend_Waindigo_SocialGroups_DataWriter_SocialForum
{

    const DATA_SOCIAL_FORUM_FIELD_DEFINITIONS = 'socialForumFields';

    /**
     * The custom fields to be updated.
     * Use setCustomFields to manage this.
     *
     * @var array
     */
    protected $_updateCustomFields = array();

    /**
     *
     * @see Waindigo_SocialGroups_DataWriter_SocialForum::_getFields()
     */
    protected function _getFields()
    {
        $fields = parent::_getFields();

        $fields['xf_social_forum']['custom_social_forum_fields'] = array(
            'type' => self::TYPE_SERIALIZED,
            'default' => ''
        );

        return $fields;
    }

    /**
     *
     * @see Waindigo_SocialGroups_DataWriter_SocialForum::_preSave()
     */
    protected function _preSave()
    {
        if (isset($GLOBALS['Waindigo_SocialGroups_ControllerPublic_SocialForum']) ||
             isset($GLOBALS['Waindigo_SocialGroups_ControllerPublic_SocialCategory'])) {
            if (isset($GLOBALS['Waindigo_SocialGroups_ControllerPublic_SocialForum'])) {
                /* @var $controller Waindigo_SocialGroups_ControllerPublic_SocialForum */
                $controller = $GLOBALS['Waindigo_SocialGroups_ControllerPublic_SocialForum'];
            } elseif (isset($GLOBALS['Waindigo_SocialGroups_ControllerPublic_SocialCategory'])) {
                /* @var $controller Waindigo_SocialGroups_ControllerPublic_SocialCategory */
                $controller = $GLOBALS['Waindigo_SocialGroups_ControllerPublic_SocialCategory'];
            }

            if (($controller instanceof Waindigo_SocialGroups_ControllerPublic_SocialForum &&
                 $controller->getRouteMatch()->getAction() == 'save') || ($controller instanceof Waindigo_SocialGroups_ControllerPublic_SocialCategory &&
                 $controller->getRouteMatch()->getAction() == 'add-social-forum')) {

                $category = $this->_getSocialCategoryData();

                $fieldValues = array();
                if (isset($category['custom_social_forum_fields']) && $category['custom_social_forum_fields']) {
                    $fieldValues = unserialize($category['custom_social_forum_fields']);
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
        }

        parent::_preSave();
    }

    /**
     *
     * @see Waindigo_SocialGroups_DataWriter_SocialForum::_postSave()
     */
    protected function _postSave()
    {
        $this->updateCustomFields();

        $this->_associateCustomFieldsAttachments();

        parent::_postSave();
    }

    /**
     *
     * @param array $fieldValues
     * @param array $fieldsShown
     */
    public function setCustomFields(array $fieldValues, array $fieldsShown = null)
    {
        $category = $this->_getSocialCategoryData();

        $categoryRequiredFields = array();
        if (isset($category['required_social_forum_fields']) && $category['required_social_forum_fields']) {
            $categoryRequiredFields = unserialize($category['required_social_forum_fields']);
        }

        if ($fieldsShown === null) {
            // not passed - assume keys are all there
            $fieldsShown = array_keys($fieldValues);
        }

        $fieldModel = $this->_getFieldModel();
        $fields = $this->_getSocialForumFieldDefinitions();
        $callbacks = array();

        if ($this->get('social_forum_id') && !$this->_importMode) {
            $existingValues = $fieldModel->getSocialForumFieldValues($this->get('social_forum_id'));
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
                    $this->error($error, "custom_field_$fieldId");
                    continue;
                }

                if (in_array($fieldId, $categoryRequiredFields) && ($value === '' || $value === array())) {
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
        $this->set('custom_social_forum_fields', $finalValues + $existingValues);
    }

    public function updateCustomFields()
    {
        if ($this->_updateCustomFields) {
            $socialForumId = $this->get('social_forum_id');

            foreach ($this->_updateCustomFields as $fieldId => $value) {
                if (is_array($value)) {
                    $value = serialize($value);
                }
                $this->_db->query(
                    '
                        INSERT INTO xf_social_forum_field_value
                        (social_forum_id, field_id, field_value)
                        VALUES
                        (?, ?, ?)
                        ON DUPLICATE KEY UPDATE
                            field_value = VALUES(field_value)
                    ',
                    array(
                        $socialForumId,
                        $fieldId,
                        $value
                    ));
            }
        }
    }

    protected function _associateCustomFieldsAttachments()
    {
        $fieldAttachmentModel = $this->getModelFromCache('Waindigo_CustomFields_Model_Attachment');

        $fieldAttachmentModel->associateAttachments($this->get('social_forum_id'), 'social_forum');
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
            $fields = $this->_getFieldModel()->getSocialForumFields();

            $this->setExtraData(self::DATA_SOCIAL_FORUM_FIELD_DEFINITIONS, $fields);
        }

        return $fields;
    }

    /**
     *
     * @see Waindigo_SocialGroups_DataWriter_SocialForum::_postDelete()
     */
    protected function _postDelete()
    {
        parent::_postDelete();

        $db = $this->_db;
        $socialForumId = $this->get('social_forum_id');
        $socialForumIdQuoted = $db->quote($socialForumId);

        $db->delete('xf_social_forum_field_value', 'social_forum_id = ' . $socialForumIdQuoted);
    }

    /**
     *
     * @return Waindigo_CustomFields_Model_SocialForumField
     */
    protected function _getFieldModel()
    {
        return $this->getModelFromCache('Waindigo_CustomFields_Model_SocialForumField');
    }
}
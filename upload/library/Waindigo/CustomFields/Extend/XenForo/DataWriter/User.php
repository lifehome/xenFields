<?php

/**
 *
 * @see XenForo_DataWriter_User
 */
class Waindigo_CustomFields_Extend_XenForo_DataWriter_User extends XFCP_Waindigo_CustomFields_Extend_XenForo_DataWriter_User
{

    /**
     *
     * @see XenForo_DataWriter_User::_postSave
     */
    protected function _postSave()
    {
        $this->_associateAttachments();

        parent::_postSave();
    }

    /**
     *
     * @see XenForo_DataWriter_User::setCustomFields()
     */
    public function setCustomFields(array $fieldValues, array $fieldsShown = null)
    {
        if ($fieldsShown === null) {
            // not passed - assume keys are all there
            $fieldsShown = array_keys($fieldValues);
        }

        $fields = $this->_getUserFieldDefinitions();
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
                        $fields[$fieldId]['field_type'] = 'textbox';
                        $callbacks[] = $fieldId;
                    }
                }
            }
        }

        $this->setExtraData(self::DATA_USER_FIELD_DEFINITIONS, $fields);

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
                        $fields[$fieldId]['field_type'] = 'callback';
                    }
                }
            }
            $this->setExtraData(self::DATA_USER_FIELD_DEFINITIONS, $fields);
            $this->set('custom_fields', $customFields);
        }
    }

    protected function _associateAttachments()
    {
        $fieldAttachmentModel = $this->getModelFromCache('Waindigo_CustomFields_Model_Attachment');

        $fieldAttachmentModel->associateAttachments($this->get('user_id'), 'user');
    }
}
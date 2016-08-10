<?php

/**
 *
 * @see XenResource_Model_ResourceField
 */
class Waindigo_CustomFields_Extend_XenResource_Model_ResourceField extends XFCP_Waindigo_CustomFields_Extend_XenResource_Model_ResourceField
{

    /**
     * Fetches all resource fields usable by the visiting user in the specified
     * category(ies)
     *
     * @param integer|array $categoryIds
     * @param array|null $viewingUser
     *
     * @return array
     */
    public function getUsableResourceFieldsInForums($categoryIds, array $viewingUser = null, $verifyUsability = true)
    {
        $this->standardizeViewingUserReference($viewingUser);

        $fields = $this->getResourceFieldsInCategories($categoryIds);

        $fieldGroups = array();
        foreach ($fields as $field) {
            if (!$verifyUsability || $this->_verifyResourceFieldIsUsableInternal($field, $viewingUser)) {
                $fieldId = $field['field_id'];
                $fieldGroupId = $field['field_group_id'];

                if (!isset($fieldGroups[$fieldGroupId])) {
                    $fieldGroups[$fieldGroupId] = array();

                    if ($fieldGroupId) {
                        $fieldGroups[$fieldGroupId]['title'] = new XenForo_Phrase(
                            $this->getResourceFieldGroupTitlePhraseName($fieldGroupId));
                    }
                }

                $fieldGroups[$fieldGroupId]['fields'][$fieldId] = $field;
            }
        }

        return $fieldGroups;
    }

    /**
     * Fetches all resource fields usable by the visiting user
     *
     * @param array|null $viewingUser
     *
     * @return array
     */
    public function getUsableResourceFields(array $viewingUser = null, $verifyUsability = true)
    {
        $this->standardizeViewingUserReference($viewingUser);

        $fields = $this->getResourceFields();

        $fieldGroups = array();
        foreach ($fields as $field) {
            if (!$verifyUsability || $this->_verifyResourceFieldIsUsableInternal($field, $viewingUser)) {
                $fieldId = $field['field_id'];
                $fieldGroupId = $field['field_group_id'];

                if (!isset($fieldGroups[$fieldGroupId])) {
                    $fieldGroups[$fieldGroupId] = array();

                    if ($fieldGroupId) {
                        $resourceFieldModel = $this->getModelFromCache('Waindigo_CustomFields_Model_ResourceField');

                        $fieldGroups[$fieldGroupId]['title'] = new XenForo_Phrase(
                            $resourceFieldModel->getResourceFieldGroupTitlePhraseName($fieldGroupId));
                    }
                }

                $fieldGroups[$fieldGroupId]['fields'][$fieldId] = $field;
            }
        }

        return $fieldGroups;
    }

    public function getResourceFieldIfInCategory($fieldId, $categoryId)
    {
        return $this->_getDb()->fetchRow(
            '
                SELECT field.*
                FROM xf_resource_field AS field
                INNER JOIN xf_resource_field_category AS rfc ON (rfc.field_id = field.field_id AND rfc.resource_category_id = ?)
                WHERE field.field_id = ?
            ', array(
                    $categoryId,
                    $fieldId
                ));
    }

    /**
     *
     * @see XenResource_Model_ResourceField::prepareResourceField()
     */
    public function prepareResourceField(array $field, $getFieldChoices = false, $fieldValue = null, $valueSaved = true)
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

        return parent::prepareResourceField($field, $getFieldChoices, $fieldValue, $valueSaved);
    }

    /**
     *
     * @see XenResource_Model_ResourceField::getResourceFieldChoices()
     */
    public function getResourceFieldChoices($fieldId, $choices, $master = false)
    {
        $choices = parent::getResourceFieldChoices($fieldId, $choices, $master);

        $xenOptions = XenForo_Application::get('options');

        if ($xenOptions->waindigo_customFields_sortChoicesAlphabetically) {
            asort($choices);
        }

        return $choices;
    }

    /**
     *
     * @see XenResource_Model_ResourceField::prepareResourceFieldConditions()
     */
    public function prepareResourceFieldConditions(array $conditions, array &$fetchOptions)
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

        $resourceFieldConditions = parent::prepareResourceFieldConditions($conditions, $fetchOptions);

        if (empty($sqlConditions)) {
            return $resourceFieldConditions;
        }

        $sqlConditions[] = $resourceFieldConditions;
        return $this->getConditionsForClause($sqlConditions);
    }

    /**
     * Prepares a list of grouped resource fields for display.
     *
     * @param array $fieldGroups
     * @param boolean $getFieldChoices If true, gets the choice options for
     * these fields (as phrases)
     * @param array $fieldValues List of values for the specified fields; if
     * skipped, pulled from field_value in array
     * @param boolean $valueSaved If true, considers the value passed to be
     * saved; should be false on registration
     *
     * @return array
     */
    public function prepareGroupedResourceFields(array $fieldGroups, $getFieldChoices = false, array $fieldValues = array(),
        $valueSaved = true, array $nodeRequiredFields = array(), array $extraData = array())
    {
        foreach ($fieldGroups as &$fieldGroup) {
            $fieldGroup['fields'] = $this->prepareResourceFields($fieldGroup['fields'], $getFieldChoices, $fieldValues,
                $valueSaved, $nodeRequiredFields, $extraData);
        }

        return $fieldGroups;
    }

    /**
     *
     * @see XenResource_Model_ResourceField::verifyResourceFieldValue()
     */
    public function verifyResourceFieldValue(array $field, &$value, &$error = '')
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

        $field['custom_field_type'] = 'resource';

        if ($field['field_type'] == 'callback') {
            $field['field_type'] = 'textbox';
        }

        return parent::verifyResourceFieldValue($field, $value, $error);
    }

    /**
     *
     * @see XenResource_Model_ResourceField::getResourceFieldGroups()
     */
    public function getResourceFieldGroups()
    {
        $resourceFieldGroups = parent::getResourceFieldGroups();

        $resourceFieldGroups['none'] = array(
            'value' => 'none',
            'label' => new XenForo_Phrase('none')
        );

        return $resourceFieldGroups;
    }

    /**
     *
     * @see XenResource_Model_ResourceField::getResourceFieldTypes()
     */
    public function getResourceFieldTypes()
    {
        $resourceFieldTypes = parent::getResourceFieldTypes();

        $resourceFieldTypes['callback'] = array(
            'value' => 'callback',
            'label' => new XenForo_Phrase('php_callback')
        );

        return $resourceFieldTypes;
    }

    /**
     *
     * @return array [field type] => type group
     */
    public function getResourceFieldTypeMap()
    {
        $resourceFieldTypeMap = parent::getResourceFieldTypeMap();

        $resourceFieldTypeMap['callback'] = 'text';

        return $resourceFieldTypeMap;
    }

    public function verifyResourceFieldIsUsable($fieldId, $categoryId, array $viewingUser = null)
    {
        $this->standardizeViewingUserReference($viewingUser);

        if (!$fieldId) {
            return true; // not picking one, always ok
        }

        $field = $this->getResourceFieldIfInCategory($fieldId, $categoryId);
        if (!$field) {
            return false; // bad field or bad category
        }

        return $this->_verifyResourceFieldIsUsableInternal($field, $viewingUser);
    }


    protected function _verifyResourceFieldIsUsableInternal(array $field, array $viewingUser)
    {
        $userGroups = explode(',', $field['allowed_user_group_ids']);
        if (in_array(-1, $userGroups) || in_array($viewingUser['user_group_id'], $userGroups)) {
            return true; // available to all groups or the primary group
        }

        if ($viewingUser['secondary_group_ids']) {
            foreach (explode(',', $viewingUser['secondary_group_ids']) as $userGroupId) {
                if (in_array($userGroupId, $userGroups)) {
                    return true; // available to one secondary group
                }
            }
        }

        return false; // not available to any groups
    }
}
<?php

/**
 * Model for custom resource fields.
 */
class Waindigo_CustomFields_Model_ResourceField extends XenForo_Model
{

    const FETCH_CATEGORY_FIELD = 0x01;

    const FETCH_FIELD_GROUP = 0x02;

    const FETCH_ADDON = 0x04;

    const FETCH_RESOURCE_FIELD_VALUE = 0x08;

    /**
     * Gets a custom resource field by ID.
     *
     * @param string $fieldId
     *
     * @return array false
     */
    public function getResourceFieldById($fieldId)
    {
        if (!$fieldId) {
            return array();
        }

        return $this->_getDb()->fetchRow(
            '
            SELECT *
            FROM xf_resource_field
            WHERE field_id = ?
        ', $fieldId);
    }

    /**
     * Gets custom resource fields that match the specified criteria.
     *
     * @param array $conditions
     * @param array $fetchOptions
     *
     * @return array [field id] => info
     */
    public function getResourceFields(array $conditions = array(), array $fetchOptions = array())
    {
        $whereConditions = $this->prepareResourceFieldConditions($conditions, $fetchOptions);

        $orderClause = $this->prepareResourceFieldOrderOptions($fetchOptions, 'field.materialized_order');
        $joinOptions = $this->prepareResourceFieldFetchOptions($fetchOptions);
        $limitOptions = $this->prepareLimitFetchOptions($fetchOptions);

        $fetchAll = (!empty($fetchOptions['join']) && ($fetchOptions['join'] & self::FETCH_CATEGORY_FIELD));

        $query = $this->limitQueryResults(
            '
            SELECT field.*
            ' . $joinOptions['selectFields'] . '
            FROM xf_resource_field AS field
            ' . $joinOptions['joinTables'] . '
            WHERE ' . $whereConditions . '
            ' . $orderClause . '
            ', $limitOptions['limit'], $limitOptions['offset']);

        return ($fetchAll ? $this->_getDb()->fetchAll($query) : $this->fetchAllKeyed($query, 'field_id'));
    }

    /**
     * Prepares a set of conditions to select fields against.
     *
     * @param array $conditions List of conditions.
     * @param array $fetchOptions The fetch options that have been provided. May
     * be edited if criteria requires.
     *
     * @return string Criteria as SQL for where clause
     */
    public function prepareResourceFieldConditions(array $conditions, array &$fetchOptions)
    {
        $db = $this->_getDb();
        $sqlConditions = array();

        if (isset($conditions['field_ids'])) {
            $sqlConditions[] = 'field.field_id IN(' . $db->quote($conditions['field_ids']) . ')';
        }

        if (!empty($conditions['field_group_id'])) {
            $sqlConditions[] = 'field.field_group_id = ' . $db->quote($conditions['field_group_id']);
        }

        if (!empty($conditions['field_choices_class_id'])) {
            $sqlConditions[] = 'field.field_choices_class_id = ' . $db->quote($conditions['field_choices_class_id']);
        }

        if (!empty($conditions['addon_id'])) {
            $sqlConditions[] = 'field.addon_id = ' . $db->quote($conditions['addon_id']);
        }

        if (!empty($conditions['active'])) {
            $sqlConditions[] = 'addon.active = 1 OR field.addon_id = \'\'';
            $this->addFetchOptionJoin($fetchOptions, self::FETCH_ADDON);
        }

        if (!empty($conditions['adminQuickSearch'])) {
            $searchStringSql = 'field.field_id LIKE ' .
                 XenForo_Db::quoteLike($conditions['adminQuickSearch']['searchText'], 'lr');

            if (!empty($conditions['adminQuickSearch']['phraseMatches'])) {
                $sqlConditions[] = '(' . $searchStringSql . ' OR field.field_id IN (' .
                     $db->quote($conditions['adminQuickSearch']['phraseMatches']) . '))';
            } else {
                $sqlConditions[] = $searchStringSql;
            }
        }

        if (isset($conditions['resource_category_id'])) {
            if (is_array($conditions['resource_category_id'])) {
                $sqlConditions[] = 'rcf.resource_category_id IN (' . $db->quote($conditions['resource_category_id']) .
                     ')';
            } else {
                $sqlConditions[] = 'rcf.resource_category_id = ' . $db->quote($conditions['resource_category_id']);
            }
            $this->addFetchOptionJoin($fetchOptions, self::FETCH_CATEGORY_FIELD);
        }

        if (isset($conditions['resource_category_ids'])) {
            $sqlConditions[] = 'rcf.resource_category_id IN(' . $db->quote($conditions['resource_category_ids']) . ')';
            $this->addFetchOptionJoin($fetchOptions, self::FETCH_CATEGORY_FIELD);
        }

        if (!empty($conditions['informationView'])) {
            $sqlConditions[] = 'field.viewable_information = 1';
        }

        return $this->getConditionsForClause($sqlConditions);
    }

    /**
     * Prepares join-related fetch options.
     *
     * @param array $fetchOptions
     *
     * @return array Containing 'selectFields' and 'joinTables' keys.
     */
    public function prepareResourceFieldFetchOptions(array $fetchOptions)
    {
        $selectFields = '';
        $joinTables = '';

        $db = $this->_getDb();

        if (!empty($fetchOptions['valueResourceId'])) {
            $selectFields .= ',
                field_value.field_value';
            $joinTables .= '
                LEFT JOIN xf_resource_field_value AS field_value ON
                (field_value.field_id = field.field_id AND field_value.resource_id = ' .
                 $db->quote($fetchOptions['valueResourceId']) . ')';
        }

        if (!empty($fetchOptions['join'])) {
            if ($fetchOptions['join'] & self::FETCH_CATEGORY_FIELD) {
                $selectFields .= ',
                    rcf.field_id, rcf.resource_category_id';
                $joinTables .= '
                    INNER JOIN xf_resource_field_category AS rcf ON
                    (rcf.field_id = field.field_id)';
            }

            if ($fetchOptions['join'] & self::FETCH_FIELD_GROUP) {
                $selectFields .= ',
                    field_group.display_order AS group_display_order';
                $joinTables .= '
                    LEFT JOIN xf_resource_field_group AS field_group ON
                    (field_group.field_group_id = field.field_group_id)';
            }

            if ($fetchOptions['join'] & self::FETCH_ADDON) {
                $selectFields .= ',
                    addon.title AS addon_title, addon.active';
                $joinTables .= '
                    LEFT JOIN xf_addon AS addon ON
                    (field.addon_id = addon.addon_id)';
            }
        }

        return array(
            'selectFields' => $selectFields,
            'joinTables' => $joinTables
        );
    }

    /**
     * Construct 'ORDER BY' clause
     *
     * @param array $fetchOptions (uses 'order' key)
     * @param string $defaultOrderSql Default order SQL
     *
     * @return string
     */
    public function prepareResourceFieldOrderOptions(array &$fetchOptions, $defaultOrderSql = '')
    {
        $choices = array(
            'materialized_order' => 'field.materialized_order',
            'canonical_order' => 'field_group.display_order, field.display_order'
        );

        if (!empty($fetchOptions['order']) && $fetchOptions['order'] == 'canonical_order') {
            $this->addFetchOptionJoin($fetchOptions, self::FETCH_FIELD_GROUP);
        }

        return $this->getOrderByClause($choices, $fetchOptions, $defaultOrderSql);
    }

    /**
     * Fetches custom resource fields in display groups
     *
     * @param array $conditions
     * @param array $fetchOptions
     * @param integer $fieldCount Reference: counts the total number of fields
     *
     * @return [group ID => [title, fields => field]]
     */
    public function getResourceFieldsByGroups(array $conditions = array(), array $fetchOptions = array(), &$fieldCount = 0)
    {
        $fields = $this->getResourceFields($conditions, $fetchOptions);

        $fieldGroups = array();
        foreach ($fields as $field) {
            $fieldGroups[$field['field_group_id']][$field['field_id']] = $this->prepareResourceField($field);
        }

        $fieldCount = count($fields);

        return $fieldGroups;
    }

    /**
     * Fetches all custom resource fields available in the specified categories
     *
     * @param integer|array $categoryIds
     *
     * @return array
     */
    public function getResourceFieldsInCategories($categoryId)
    {
        return $this->getResourceFields(
            is_array($categoryId) ? array(
                'resource_category_ids' => $categoryId
            ) : array(
                'resource_category_id' => $categoryId
            ));
    }

    /**
     * Fetches all custom resource fields available in the specified categories
     *
     * @param integer $categoryId
     *
     * @return array
     */
    public function getResourceFieldsInCategory($categoryId)
    {
        $output = array();
        foreach ($this->getResourceFields(array(
            'resource_category_id' => $categoryId
        )) as $field) {
            $output[$field['field_id']] = $field;
        }

        return $output;
    }

    /**
     * Fetches all resource fields usable by the visiting user in the specified
     * category(s)
     *
     * @param integer|array $categoryIds
     * @param array|null $viewingUser
     *
     * @return array
     */
    public function getUsableResourceFieldsInCategories($categoryIds, array $viewingUser = null, $verifyUsability = true)
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

    public function getResourceFieldIfInCategory($fieldId, $categoryId)
    {
        return $this->_getDb()->fetchRow(
            '
                SELECT field.*
                FROM xf_resource_field AS field
                INNER JOIN xf_resource_field_category AS rcf ON (rcf.field_id = field.field_id AND rcf.resource_category_id = ?)
                WHERE field.field_id = ?
            ', array(
                $categoryId,
                $fieldId
            ));
    }

    public function getCategoryAssociationsByResourceField($fieldId, $fetchAll = false)
    {
        $query = '
            SELECT rcf.resource_category_id
            ' . ($fetchAll ? ', category.*' : '') . '
            FROM xf_resource_field_category AS rcf
            ' .
             ($fetchAll ? 'LEFT JOIN xf_category AS category ON (rcf.resource_category_id = category.resource_category_id)' : '') . '
            WHERE rcf.field_id = ' . $this->_getDb()->quote($fieldId) . '
        ';

        return ($fetchAll ? $this->fetchAllKeyed($query, 'resource_category_id') : $this->_getDb()->fetchCol($query));
    }

    /**
     * Groups resource fields by their field group.
     *
     * @param array $fields
     *
     * @return array [field group id][key] => info
     */
    public function groupResourceFields(array $fields)
    {
        $return = array();

        foreach ($fields as $fieldId => $field) {
            $return[$field['field_group_id']][$fieldId] = $field;
        }

        return $return;
    }

    /**
     * Prepares a resource field for display.
     *
     * @param array $field
     * @param boolean $getFieldChoices If true, gets the choice options for this
     * field (as phrases)
     * @param mixed $fieldValue If not null, the value for the field; if null,
     * pulled from field_value
     * @param boolean $valueSaved If true, considers the value passed to be
     * saved; should be false on registration
     *
     * @return array Prepared field
     */
    public function prepareResourceField(array $field, $getFieldChoices = false, $fieldValue = null, $valueSaved = true,
        $required = false, array $extraData = array())
    {
        $field['isMultiChoice'] = ($field['field_type'] == 'checkbox' || $field['field_type'] == 'multiselect');

        if ($fieldValue === null && isset($field['field_value'])) {
            $fieldValue = $field['field_value'];
        }
        if ($field['isMultiChoice']) {
            if (is_string($fieldValue)) {
                $fieldValue = @unserialize($fieldValue);
            } else
                if (!is_array($fieldValue)) {
                    $fieldValue = array();
                }
        }
        $field['field_value'] = $fieldValue;

        $field['title'] = new XenForo_Phrase($this->getResourceFieldTitlePhraseName($field['field_id']));
        $field['description'] = new XenForo_Phrase($this->getResourceFieldDescriptionPhraseName($field['field_id']));

        $field['hasValue'] = $valueSaved &&
             ((is_string($fieldValue) && $fieldValue !== '') || (!is_string($fieldValue) && $fieldValue));

        if ($getFieldChoices) {
            if ((isset($field['field_choices_callback_class']) && $field['field_choices_callback_class']) &&
                 (isset($field['field_choices_callback_method']) && $field['field_choices_callback_method'])) {
                try {
                    $field['fieldChoices'] = call_user_func(
                        array(
                            $field['field_choices_callback_class'],
                            $field['field_choices_callback_method']
                        ), $field, $extraData);
                } catch (Exception $e) {
                    // do nothing
                }
            } else {
                $field['fieldChoices'] = $this->getResourceFieldChoices($field['field_id'], $field['field_choices']);
            }
        }

        $field['isEditable'] = true;

        $field['required'] = $required;

        return $field;
    }

    /**
     * Prepares a list of resource fields for display.
     *
     * @param array $fields
     * @param boolean $getFieldChoices If true, gets the choice options for
     * these fields (as phrases)
     * @param array $fieldValues List of values for the specified fields; if
     * skipped, pulled from field_value in array
     * @param boolean $valueSaved If true, considers the value passed to be
     * saved; should be false on registration
     *
     * @return array
     */
    public function prepareResourceFields(array $fields, $getFieldChoices = false, array $fieldValues = array(), $valueSaved = true,
        array $categoryRequiredFields = array(), array $extraData = array())
    {
        foreach ($fields as &$field) {
            $value = isset($fieldValues[$field['field_id']]) ? $fieldValues[$field['field_id']] : null;
            $required = in_array($field['field_id'], $categoryRequiredFields);
            $field = $this->prepareResourceField($field, $getFieldChoices, $value, $valueSaved, $required, $extraData);
        }

        return $fields;
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
        $valueSaved = true, array $categoryRequiredFields = array(), array $extraData = array())
    {
        foreach ($fieldGroups as &$fieldGroup) {
            $fieldGroup['fields'] = $this->prepareResourceFields($fieldGroup['fields'], $getFieldChoices, $fieldValues,
                $valueSaved, $categoryRequiredFields, $extraData);
        }

        return $fieldGroups;
    }

    public function getResourceFieldTitlePhraseName($fieldId)
    {
        return 'resource_field_' . $fieldId;
    }

    /**
     * Gets the field choices for the given field.
     *
     * @param string $fieldId
     * @param string|array $choices Serialized string or array of choices; key
     * is choide ID
     * @param boolean $master If true, gets the master phrase values; otherwise,
     * phrases
     *
     * @return array Choices
     */
    public function getResourceFieldChoices($fieldId, $choices, $master = false)
    {
        if (!is_array($choices)) {
            $choices = ($choices ? @unserialize($choices) : array());
        }

        if (!$master) {
            foreach ($choices as $value => &$text) {
                $text = new XenForo_Phrase($this->getResourceFieldChoicePhraseName($fieldId, $value));
            }
        }

        $xenOptions = XenForo_Application::get('options');

        if ($xenOptions->waindigo_customFields_sortChoicesAlphabetically) {
            asort($choices);
        }

        return $choices;
    }

    /**
     * Verifies that the value for the specified field is valid.
     *
     * @param array $field
     * @param mixed $value
     * @param mixed $error Returned error message
     *
     * @return boolean
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
        $error = false;

        switch ($field['field_type']) {
            case 'textbox':
                $value = preg_replace('/\r?\n/', ' ', strval($value));
            // break missing intentionally


            case 'textarea':
                $value = trim(strval($value));

                if ($field['max_length'] && utf8_strlen($value) > $field['max_length']) {
                    $error = new XenForo_Phrase('please_enter_value_using_x_characters_or_fewer',
                        array(
                            'count' => $field['max_length']
                        ));
                    return false;
                }

                $matched = true;

                if ($value !== '') {
                    switch ($field['match_type']) {
                        case 'number':
                            $matched = preg_match('/^[0-9]+(\.[0-9]+)?$/', $value);
                            break;

                        case 'alphanumeric':
                            $matched = preg_match('/^[a-z0-9_]+$/i', $value);
                            break;

                        case 'email':
                            $matched = Zend_Validate::is($value, 'EmailAddress');
                            break;

                        case 'url':
                            if ($value === 'http://') {
                                $value = '';
                                break;
                            }
                            if (substr(strtolower($value), 0, 4) == 'www.') {
                                $value = 'http://' . $value;
                            }
                            $matched = Zend_Uri::check($value);
                            break;

                        case 'regex':
                            $matched = preg_match('#' . str_replace('#', '\#', $field['match_regex']) . '#sU', $value);
                            break;

                        case 'callback':
                            $field['custom_field_type'] = 'resource';
                            $matched = call_user_func_array(
                                array(
                                    $field['match_callback_class'],
                                    $field['match_callback_method']
                                ),
                                array(
                                    $field,
                                    &$value,
                                    &$error
                                ));

                        default:
                        // no matching
                    }
                }

                if (!$matched) {
                    if (!$error) {
                        $error = new XenForo_Phrase('please_enter_value_that_matches_required_format');
                    }
                    return false;
                }
                break;

            case 'radio':
            case 'select':
                $choices = unserialize($field['field_choices']);
                $value = strval($value);

                if (!isset($choices[$value])) {
                    $value = '';
                }
                break;

            case 'checkbox':
            case 'multiselect':
                $choices = unserialize($field['field_choices']);
                if (!is_array($value)) {
                    $value = array();
                }

                $newValue = array();

                foreach ($value as $key => $choice) {
                    $choice = strval($choice);
                    if (isset($choices[$choice])) {
                        $newValue[$choice] = $choice;
                    }
                }

                $value = $newValue;
                break;
        }

        return true;
    }

    public function updateResourceFieldCategoryAssociationByResourceField($fieldId, array $categoryIds)
    {
        $emptyCategoryKey = array_search(0, $categoryIds);
        if ($emptyCategoryKey !== false) {
            unset($categoryIds[$emptyCategoryKey]);
        }

        $categoryIds = array_unique($categoryIds);

        $existingCategoryIds = $this->getCategoryAssociationsByResourceField($fieldId);
        if (!$categoryIds && !$existingCategoryIds) {
            return; // nothing to do
        }

        $db = $this->_getDb();
        XenForo_Db::beginTransaction($db);

        $db->delete('xf_resource_field_category', 'field_id = ' . $db->quote($fieldId));

        foreach ($categoryIds as $categoryId) {
            $db->insert('xf_resource_field_category',
                array(
                    'resource_category_id' => $categoryId,
                    'field_id' => $fieldId,
                    'field_value' => ''
                ));
        }

        $rebuildCategoryIds = array_unique(array_merge($categoryIds, $existingCategoryIds));
        $this->rebuildResourceFieldCategoryAssociationCache($rebuildCategoryIds);

        XenForo_Db::commit($db);
    }

    public function updateResourceFieldCategoryAssociationByCategory($categoryId, array $fieldIds)
    {
        $emptyFieldKey = array_search(0, $fieldIds, true);
        if ($emptyFieldKey !== false) {
            unset($fieldIds[$emptyFieldKey]);
        }

        $fieldIds = array_unique($fieldIds);

        $db = $this->_getDb();

        XenForo_Db::beginTransaction($db);

        $db->delete('xf_resource_field_category', 'resource_category_id = ' . $db->quote($categoryId));

        foreach ($fieldIds as $fieldId) {
            $db->insert('xf_resource_field_category',
                array(
                    'resource_category_id' => $categoryId,
                    'field_id' => $fieldId,
                    'field_value' => ''
                ));
        }

        $this->rebuildResourceFieldCategoryAssociationCache($categoryId);

        XenForo_Db::commit($db);
    }

    public function rebuildResourceFieldCategoryAssociationCache($categoryIds)
    {
        if (!is_array($categoryIds)) {
            $categoryIds = array(
                $categoryIds
            );
        }
        if (!$categoryIds) {
            return;
        }

        $categories = $this->_getCategoryModel()->getAllCategories();

        $db = $this->_getDb();

        $newCache = array();

        foreach ($this->getResourceFieldsInCategories($categoryIds) as $field) {
            $fieldGroupId = $field['field_group_id'];
            $newCache[$field['resource_category_id']][$fieldGroupId][$field['field_id']] = $field['field_id'];
        }

        XenForo_Db::beginTransaction($db);

        foreach ($categoryIds as $categoryId) {
            $update = (isset($newCache[$categoryId]) ? serialize($newCache[$categoryId]) : '');
            if (isset($categories[$categoryId])) {
                $db->update('xf_resource_category',
                    array(
                        'field_cache' => $update
                    ), 'resource_category_id = ' . $db->quote($categoryId));
            }
        }

        XenForo_Db::commit($db);
    }

    /**
     * Fetches an array of custom resource fields including display group info,
     * for use in <xen:options source />
     *
     * @param array $conditions
     * @param array $fetchOptions
     *
     * @return array
     */
    public function getResourceFieldOptions(array $conditions = array(), array $fetchOptions = array())
    {
        $fieldGroups = $this->getResourceFieldsByGroups($conditions, $fetchOptions);

        $options = array();

        foreach ($fieldGroups as $fieldGroupId => $fields) {
            if ($fields) {
                if ($fieldGroupId) {
                    $groupTitle = new XenForo_Phrase($this->getResourceFieldGroupTitlePhraseName($fieldGroupId));
                    $groupTitle = (string) $groupTitle;
                } else {
                    $groupTitle = new XenForo_Phrase('ungrouped');
                    $groupTitle = '(' . $groupTitle . ')';
                }

                foreach ($fields as $fieldId => $field) {
                    $options[$groupTitle][$fieldId] = array(
                        'value' => $fieldId,
                        'label' => (string) $field['title'],
                        '_data' => array()
                    );
                }
            }
        }

        return $options;
    }

    /**
     * Gets the possible resource field types.
     *
     * @return array [type] => keys: value, label, hint (optional)
     */
    public function getResourceFieldTypes()
    {
        return array(
            'textbox' => array(
                'value' => 'textbox',
                'label' => new XenForo_Phrase('single_line_text_box')
            ),
            'textarea' => array(
                'value' => 'textarea',
                'label' => new XenForo_Phrase('multi_line_text_box')
            ),
            'select' => array(
                'value' => 'select',
                'label' => new XenForo_Phrase('drop_down_selection')
            ),
            'radio' => array(
                'value' => 'radio',
                'label' => new XenForo_Phrase('radio_buttons')
            ),
            'checkbox' => array(
                'value' => 'checkbox',
                'label' => new XenForo_Phrase('check_boxes')
            ),
            'multiselect' => array(
                'value' => 'multiselect',
                'label' => new XenForo_Phrase('multiple_choice_drop_down_selection')
            ),
            'callback' => array(
                'value' => 'callback',
                'label' => new XenForo_Phrase('php_callback')
            )
        );
    }

    /**
     * Maps resource fields to their high level type "group".
     * Field types can be changed only
     * within the group.
     *
     * @return array [field type] => type group
     */
    public function getResourceFieldTypeMap()
    {
        return array(
            'textbox' => 'text',
            'textarea' => 'text',
            'radio' => 'single',
            'select' => 'single',
            'checkbox' => 'multiple',
            'multiselect' => 'multiple',
            'callback' => 'text'
        );
    }

    /**
     * Gets the field's description phrase name.
     *
     * @param string $fieldId
     *
     * @return string
     */
    public function getResourceFieldDescriptionPhraseName($fieldId)
    {
        return 'resource_field_' . $fieldId . '_desc';
    }

    /**
     * Gets a field choices's phrase name.
     *
     * @param string $fieldId
     * @param string $choice
     *
     * @return string
     */
    public function getResourceFieldChoicePhraseName($fieldId, $choice)
    {
        return 'resource_field_' . $fieldId . '_choice_' . $choice;
    }

    /**
     * Gets a field's master title phrase text.
     *
     * @param string $id
     *
     * @return string
     */
    public function getResourceFieldMasterTitlePhraseValue($id)
    {
        $phraseName = $this->getResourceFieldTitlePhraseName($id);
        return $this->_getPhraseModel()->getMasterPhraseValue($phraseName);
    }

    /**
     * Gets a field's master description phrase text.
     *
     * @param string $id
     *
     * @return string
     */
    public function getResourceFieldMasterDescriptionPhraseValue($id)
    {
        $phraseName = $this->getResourceFieldDescriptionPhraseName($id);
        return $this->_getPhraseModel()->getMasterPhraseValue($phraseName);
    }

    protected function _prepareFieldValues(array $fields = array())
    {
        $values = array();
        foreach ($fields as $field) {
            if ($field['field_type'] == 'checkbox' || $field['field_type'] == 'multiselect') {
                $values[$field['field_id']] = @unserialize($field['field_value']);
            } else {
                $values[$field['field_id']] = $field['field_value'];
            }
        }

        return $values;
    }

    /**
     * Gets the resource field values for the given resource.
     *
     * @param integer $resourceId
     *
     * @return array [field id] => value (may be string or array)
     */
    public function getResourceFieldValues($resourceId)
    {
        $fields = $this->_getDb()->fetchAll(
            '
            SELECT value.*, field.field_type
            FROM xf_resource_field_value AS value
            INNER JOIN xf_resource_field AS field ON (field.field_id = value.field_id)
            WHERE value.resource_id = ?
        ', $resourceId);

        return $this->_prepareFieldValues($fields);
    }

    /**
     * Gets the resource field values for the given resource.
     *
     * @param integer $articleId
     *
     * @return array [field id] => value (may be string or array)
     */
    public function getArticleFieldValues($articleId)
    {
        $fields = $this->_getDb()->fetchAll(
            '
                SELECT value.*, field.field_type
                FROM xf_article_field_value AS value
                INNER JOIN xf_resource_field AS field ON (field.field_id = value.field_id)
                WHERE value.article_id = ?
            ', $articleId);

        return $this->_prepareFieldValues($fields);
    }

    /**
     * Gets the default resource field values for the given category.
     *
     * @param integer $categoryId
     *
     * @return array [field id] => value (may be string or array)
     */
    public function getDefaultResourceFieldValues($categoryId = null)
    {
        if ($categoryId) {
            $fields = $this->_getDb()->fetchAll(
                '
                SELECT rcf.*, field.field_type
                FROM xf_resource_field_category AS rcf
                INNER JOIN xf_resource_field AS field ON (field.field_id = rcf.field_id)
                WHERE rcf.resource_category_id = ?
                ', $categoryId);

            return $this->_prepareFieldValues($fields);
        } else {
            return array(
                'field_id' => null,
                'field_group_id' => '0',
                'display_order' => 1,
                'field_type' => 'textbox',
                'match_type' => 'none',
                'max_length' => 0,
                'field_choices' => ''
            );
        }
    }

    /**
     * Rebuilds the cache of resource field info for front-end display
     *
     * @return array
     */
    public function rebuildResourceFieldCache()
    {
        $cache = array();
        foreach ($this->getResourceFields() as $fieldId => $field) {
            $cache[$fieldId] = XenForo_Application::arrayFilterKeys($field,
                array(
                    'field_id',
                    'field_type',
                    'field_group_id'
                ));
        }

        $this->_getDataRegistryModel()->set('resourceFieldsInfo', $cache);
        return $cache;
    }

    /**
     * Rebuilds the 'materialized_order' field in the field table,
     * based on the canonical display_order data in the field and field_group
     * tables.
     */
    public function rebuildResourceFieldMaterializedOrder()
    {
        $fields = $this->getResourceFields(array(), array(
            'order' => 'canonical_order'
        ));

        $db = $this->_getDb();
        $ungroupedFields = array();
        $updates = array();
        $i = 0;

        foreach ($fields as $fieldId => $field) {
            if ($field['field_group_id']) {
                if (++ $i != $field['materialized_order']) {
                    $updates[$fieldId] = 'WHEN ' . $db->quote($fieldId) . ' THEN ' . $db->quote($i);
                }
            } else {
                $ungroupedFields[$fieldId] = $field;
            }
        }

        foreach ($ungroupedFields as $fieldId => $field) {
            if (++ $i != $field['materialized_order']) {
                $updates[$fieldId] = 'WHEN ' . $db->quote($fieldId) . ' THEN ' . $db->quote($i);
            }
        }

        if (!empty($updates)) {
            $db->query(
                '
                UPDATE xf_resource_field SET materialized_order = CASE field_id
                ' . implode(' ', $updates) . '
                END
                WHERE field_id IN(' . $db->quote(array_keys($updates)) . ')
            ');
        }
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

    // field groups ---------------------------------------------------------


    /**
     * Fetches a single field group, as defined by its unique field group ID
     *
     * @param integer $fieldGroupId
     *
     * @return array
     */
    public function getResourceFieldGroupById($fieldGroupId)
    {
        if (!$fieldGroupId) {
            return array();
        }

        return $this->_getDb()->fetchRow(
            '
                SELECT *
                FROM xf_resource_field_group
                WHERE field_group_id = ?
            ', $fieldGroupId);
    }

    public function getAllResourceFieldGroups()
    {
        return $this->fetchAllKeyed(
            '
                SELECT *
                FROM xf_resource_field_group
                ORDER BY display_order
            ', 'field_group_id');
    }

    public function getResourceFieldGroupOptions($selectedGroupId = '')
    {
        $fieldGroups = $this->getAllResourceFieldGroups();
        $fieldGroups = $this->prepareResourceFieldGroups($fieldGroups);

        $options = array();

        foreach ($fieldGroups as $fieldGroupId => $fieldGroup) {
            $options[$fieldGroupId] = $fieldGroup['title'];
        }

        return $options;
    }

    public function mergeResourceFieldsIntoGroups(array $fields, array $fieldGroups)
    {
        $merge = array();

        foreach ($fieldGroups as $fieldGroupId => $fieldGroup) {
            if (isset($fields[$fieldGroupId])) {
                $merge[$fieldGroupId] = $fields[$fieldGroupId];
                unset($fields[$fieldGroupId]);
            } else {
                $merge[$fieldGroupId] = array();
            }
        }

        if (!empty($fields)) {
            foreach ($fields as $fieldGroupId => $_fields) {
                $merge[$fieldGroupId] = $_fields;
            }
        }

        return $merge;
    }

    public function getResourceFieldGroupTitlePhraseName($fieldGroupId)
    {
        return 'resource_field_group_' . $fieldGroupId;
    }

    public function prepareResourceFieldGroups(array $fieldGroups)
    {
        return array_map(array(
            $this,
            'prepareResourceFieldGroup'
        ), $fieldGroups);
    }

    public function prepareResourceFieldGroup(array $fieldGroup)
    {
        $fieldGroup['title'] = new XenForo_Phrase(
            $this->getResourceFieldGroupTitlePhraseName($fieldGroup['field_group_id']));

        return $fieldGroup;
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

        $rootCategory = $document->createElement('field');
        $this->_appendFieldXml($rootCategory, $field);
        $document->appendChild($rootCategory);

        $templatesCategory = $document->createElement('templates');
        $rootCategory->appendChild($templatesCategory);
        $this->getModelFromCache('Waindigo_CustomFields_Model_Template')->appendTemplatesFieldXml($templatesCategory,
            $field);

        $adminTemplatesCategory = $document->createElement('admin_templates');
        $rootCategory->appendChild($adminTemplatesCategory);
        $this->getModelFromCache('Waindigo_CustomFields_Model_AdminTemplate')->appendAdminTemplatesFieldXml(
            $adminTemplatesCategory, $field);

        $phrasesCategory = $document->createElement('phrases');
        $rootCategory->appendChild($phrasesCategory);
        $this->getModelFromCache('XenForo_Model_Phrase')->appendPhrasesFieldXml($phrasesCategory, $field);

        return $document;
    }

    /**
     * Appends the add-on field XML to a given DOM element.
     *
     * @param DOMElement $rootCategory Category to append all elements to
     * @param string $addOnId Add-on ID to be exported
     */
    public function appendFieldsAddOnXml(DOMElement $rootCategory, $addOnId)
    {
        $document = $rootCategory->ownerDocument;

        $fields = $this->getResourceFields(array(
            'addon_id' => $addOnId
        ));
        foreach ($fields as $field) {
            $fieldCategory = $document->createElement('field');
            $this->_appendFieldXml($fieldCategory, $field);
            $rootCategory->appendChild($fieldCategory);
        }
    }

    /**
     *
     * @param DOMElement $rootCategory
     * @param array $field
     */
    protected function _appendFieldXml(DOMElement $rootCategory, $field)
    {
        $document = $rootCategory->ownerDocument;

        $rootCategory->setAttribute('export_callback_method', $field['export_callback_method']);
        $rootCategory->setAttribute('export_callback_class', $field['export_callback_class']);
        $rootCategory->setAttribute('field_callback_method', $field['field_callback_method']);
        $rootCategory->setAttribute('field_callback_class', $field['field_callback_class']);
        $rootCategory->setAttribute('field_choices_callback_class', $field['field_choices_callback_class']);
        $rootCategory->setAttribute('field_choices_callback_method', $field['field_choices_callback_method']);
        $rootCategory->setAttribute('display_callback_method', $field['display_callback_method']);
        $rootCategory->setAttribute('display_callback_class', $field['display_callback_class']);
        $rootCategory->setAttribute('max_length', $field['max_length']);
        $rootCategory->setAttribute('match_callback_method', $field['match_callback_method']);
        $rootCategory->setAttribute('match_callback_class', $field['match_callback_class']);
        $rootCategory->setAttribute('match_regex', $field['match_regex']);
        $rootCategory->setAttribute('match_type', $field['match_type']);
        $rootCategory->setAttribute('field_type', $field['field_type']);
        $rootCategory->setAttribute('display_order', $field['display_order']);
        $rootCategory->setAttribute('field_id', $field['field_id']);
        $rootCategory->setAttribute('addon_id', $field['addon_id']);

        $titleCategory = $document->createElement('title');
        $rootCategory->appendChild($titleCategory);
        $titleCategory->appendChild(
            XenForo_Helper_DevelopmentXml::createDomCdataSection($document,
                new XenForo_Phrase('resource_field_' . $field['field_id'])));

        $descriptionCategory = $document->createElement('description');
        $rootCategory->appendChild($descriptionCategory);
        $descriptionCategory->appendChild(
            XenForo_Helper_DevelopmentXml::createDomCdataSection($document,
                new XenForo_Phrase('resource_field_' . $field['field_id'] . '_desc')));

        $displayTemplateCategory = $document->createElement('display_template');
        $rootCategory->appendChild($displayTemplateCategory);
        $displayTemplateCategory->appendChild(
            XenForo_Helper_DevelopmentXml::createDomCdataSection($document, $field['display_template']));

        $fieldChoicesCategory = $document->createElement('field_choices');
        $rootCategory->appendChild($fieldChoicesCategory);
        if ($field['field_choices']) {
            $fieldChoices = unserialize($field['field_choices']);
            foreach ($fieldChoices as $fieldChoiceValue => $fieldChoiceText) {
                $fieldChoiceCategory = $document->createElement('field_choice');
                $fieldChoiceCategory->setAttribute('value', $fieldChoiceValue);
                $fieldChoiceCategory->appendChild(
                    XenForo_Helper_DevelopmentXml::createDomCdataSection($document, $fieldChoiceText));
                $fieldChoicesCategory->appendChild($fieldChoiceCategory);
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
    public function importFieldXml(SimpleXMLElement $document, $fieldGroupId = 0, $overwriteFieldId = 0)
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
            $overwriteField = $this->getResourceFieldById($overwriteFieldId);
        }

        $db = $this->_getDb();
        XenForo_Db::beginTransaction($db);

        $dw = XenForo_DataWriter::create('Waindigo_CustomFields_DataWriter_ResourceField');
        if (isset($overwriteField['field_id'])) {
            $dw->setExistingData($overwriteFieldId);
        } else {
            if ($overwriteFieldId) {
                $dw->set('field_id', $overwriteFieldId);
            } else {
                $dw->set('field_id', $fieldId);
            }
            if ($fieldGroupId) {
                $dw->set('field_group_id', $fieldGroupId);
            }
            $dw->set('allowed_user_group_ids', -1);
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

        $dw->setExtraData(Waindigo_CustomFields_DataWriter_ResourceField::DATA_TITLE,
            XenForo_Helper_DevelopmentXml::processSimpleXmlCdata($document->title));
        $dw->setExtraData(Waindigo_CustomFields_DataWriter_ResourceField::DATA_DESCRIPTION,
            XenForo_Helper_DevelopmentXml::processSimpleXmlCdata($document->description));

        $fieldChoices = XenForo_Helper_DevelopmentXml::fixPhpBug50670($document->field_choices->field_choice);

        foreach ($fieldChoices as $fieldChoice) {
            if ($fieldChoice && $fieldChoice['value']) {
                $fieldChoicesCombined[(string) $fieldChoice['value']] = XenForo_Helper_DevelopmentXml::processSimpleXmlCdata(
                    $fieldChoice);
            }
        }

        if (isset($fieldChoicesCombined)) {
            $dw->setFieldChoices($fieldChoicesCombined);
        }

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

    /**
     *
     * @return XenResource_Model_Category
     */
    protected function _getCategoryModel()
    {
        return $this->getModelFromCache('XenResource_Model_Category');
    }

    /**
     *
     * @return XenForo_Model_Phrase
     */
    protected function _getPhraseModel()
    {
        return $this->getModelFromCache('XenForo_Model_Phrase');
    }
}
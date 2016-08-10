<?php

/**
 * Model for custom thread fields.
 */
class Waindigo_CustomFields_Model_ThreadField extends XenForo_Model
{

    const FETCH_FORUM_FIELD = 0x01;

    const FETCH_FIELD_GROUP = 0x02;

    const FETCH_ADDON = 0x04;

    /**
     * Gets a custom thread field by ID.
     *
     * @param string $fieldId
     *
     * @return array false
     */
    public function getThreadFieldById($fieldId)
    {
        if (!$fieldId) {
            return array();
        }

        return $this->_getDb()->fetchRow(
            '
            SELECT *
            FROM xf_thread_field
            WHERE field_id = ?
        ', $fieldId);
    }

    /**
     * Gets custom thread fields that match the specified criteria.
     *
     * @param array $conditions
     * @param array $fetchOptions
     *
     * @return array [field id] => info
     */
    public function getThreadFields(array $conditions = array(), array $fetchOptions = array())
    {
        $whereConditions = $this->prepareThreadFieldConditions($conditions, $fetchOptions);

        $orderClause = $this->prepareThreadFieldOrderOptions($fetchOptions, 'field.materialized_order');
        $joinOptions = $this->prepareThreadFieldFetchOptions($fetchOptions);
        $limitOptions = $this->prepareLimitFetchOptions($fetchOptions);

        $fetchAll = (!empty($fetchOptions['join']) && ($fetchOptions['join'] & self::FETCH_FORUM_FIELD));

        $query = $this->limitQueryResults(
            '
            SELECT field.*
            ' . $joinOptions['selectFields'] . '
            FROM xf_thread_field AS field
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
    public function prepareThreadFieldConditions(array $conditions, array &$fetchOptions)
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

        if (isset($conditions['node_id'])) {
            if (is_array($conditions['node_id'])) {
                $sqlConditions[] = 'ff.node_id IN(' . $db->quote($conditions['node_id']) . ')';
            } else {
                $sqlConditions[] = 'ff.node_id = ' . $db->quote($conditions['node_id']);
            }
            $this->addFetchOptionJoin($fetchOptions, self::FETCH_FORUM_FIELD);
        }

        if (isset($conditions['node_ids'])) {
            $sqlConditions[] = 'ff.node_id IN(' . $db->quote($conditions['node_ids']) . ')';
            $this->addFetchOptionJoin($fetchOptions, self::FETCH_FORUM_FIELD);
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
    public function prepareThreadFieldFetchOptions(array $fetchOptions)
    {
        $selectFields = '';
        $joinTables = '';

        $db = $this->_getDb();

        if (!empty($fetchOptions['valueThreadId'])) {
            $selectFields .= ',
                field_value.field_value';
            $joinTables .= '
                LEFT JOIN xf_thread_field_value AS field_value ON
                (field_value.field_id = field.field_id AND field_value.thread_id = ' .
                 $db->quote($fetchOptions['valueThreadId']) . ')';
        }

        if (!empty($fetchOptions['join'])) {
            if ($fetchOptions['join'] & self::FETCH_FORUM_FIELD) {
                $selectFields .= ',
                    ff.field_id, ff.node_id';
                $joinTables .= '
                    INNER JOIN xf_forum_field AS ff ON
                    (ff.field_id = field.field_id)';
            }

            if ($fetchOptions['join'] & self::FETCH_FIELD_GROUP) {
                $selectFields .= ',
                    field_group.display_order AS group_display_order';
                $joinTables .= '
                    LEFT JOIN xf_thread_field_group AS field_group ON
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
    public function prepareThreadFieldOrderOptions(array &$fetchOptions, $defaultOrderSql = '')
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
     * Fetches custom thread fields in display groups
     *
     * @param array $conditions
     * @param array $fetchOptions
     * @param integer $fieldCount Reference: counts the total number of fields
     *
     * @return [group ID => [title, fields => field]]
     */
    public function getThreadFieldsByGroups(array $conditions = array(), array $fetchOptions = array(), &$fieldCount = 0)
    {
        $fields = $this->getThreadFields($conditions, $fetchOptions);

        $fieldGroups = array();
        foreach ($fields as $field) {
            $fieldGroups[$field['field_group_id']][$field['field_id']] = $this->prepareThreadField($field);
        }

        $fieldCount = count($fields);

        return $fieldGroups;
    }

    /**
     * Fetches all custom thread fields available in the specified forums
     *
     * @param integer|array $nodeIds
     *
     * @return array
     */
    public function getThreadFieldsInForums($nodeId)
    {
        return $this->getThreadFields(
            is_array($nodeId) ? array(
                'node_ids' => $nodeId
            ) : array(
                'node_id' => $nodeId
            ));
    }

    /**
     * Fetches all custom thread fields available in the specified forums
     *
     * @param integer $nodeId
     *
     * @return array
     */
    public function getThreadFieldsInForum($nodeId)
    {
        $output = array();
        foreach ($this->getThreadFields(array(
            'node_id' => $nodeId
        )) as $field) {
            $output[$field['field_id']] = $field;
        }

        return $output;
    }

    /**
     * Fetches all thread fields usable by the visiting user in the specified
     * forum(s)
     *
     * @param integer|array $nodeIds
     * @param array|null $viewingUser
     *
     * @return array
     */
    public function getUsableThreadFieldsInForums($nodeIds, array $viewingUser = null, $verifyUsability = true)
    {
        $this->standardizeViewingUserReference($viewingUser);

        $fields = $this->getThreadFieldsInForums($nodeIds);

        $fieldGroups = array();
        foreach ($fields as $field) {
            if (!$verifyUsability || $this->_verifyThreadFieldIsUsableInternal($field, $viewingUser)) {
                $fieldId = $field['field_id'];
                $fieldGroupId = $field['field_group_id'];

                if (!isset($fieldGroups[$fieldGroupId])) {
                    $fieldGroups[$fieldGroupId] = array();

                    if ($fieldGroupId) {
                        $fieldGroups[$fieldGroupId]['title'] = new XenForo_Phrase(
                            $this->getThreadFieldGroupTitlePhraseName($fieldGroupId));
                    }
                }

                $fieldGroups[$fieldGroupId]['fields'][$fieldId] = $field;
            }
        }

        return $fieldGroups;
    }

    /**
     * Fetches all thread fields usable by the visiting user
     *
     * @param array|null $viewingUser
     *
     * @return array
     */
    public function getUsableThreadFields(array $viewingUser = null, $verifyUsability = true)
    {
        $this->standardizeViewingUserReference($viewingUser);

        $fields = $this->getThreadFields();

        $fieldGroups = array();
        foreach ($fields as $field) {
            if (!$verifyUsability || $this->_verifyThreadFieldIsUsableInternal($field, $viewingUser)) {
                $fieldId = $field['field_id'];
                $fieldGroupId = $field['field_group_id'];

                if (!isset($fieldGroups[$fieldGroupId])) {
                    $fieldGroups[$fieldGroupId] = array();

                    if ($fieldGroupId) {
                        $fieldGroups[$fieldGroupId]['title'] = new XenForo_Phrase(
                            $this->getThreadFieldGroupTitlePhraseName($fieldGroupId));
                    }
                }

                $fieldGroups[$fieldGroupId]['fields'][$fieldId] = $field;
            }
        }

        return $fieldGroups;
    }

    public function getThreadFieldIfInForum($fieldId, $nodeId)
    {
        return $this->_getDb()->fetchRow(
            '
                SELECT field.*
                FROM xf_thread_field AS field
                INNER JOIN xf_forum_field AS ff ON (ff.field_id = field.field_id AND ff.node_id = ?)
                WHERE field.field_id = ?
            ', array(
                $nodeId,
                $fieldId
            ));
    }

    public function getForumAssociationsByThreadField($fieldId, $fetchAll = false)
    {
        $query = '
            SELECT ff.node_id
            ' . ($fetchAll ? ', node.*' : '') . '
            FROM xf_forum_field AS ff
            ' . ($fetchAll ? 'LEFT JOIN xf_node AS node ON (ff.node_id = node.node_id)' : '') . '
            WHERE ff.field_id = ' . $this->_getDb()->quote($fieldId) . '
        ';

        return ($fetchAll ? $this->fetchAllKeyed($query, 'node_id') : $this->_getDb()->fetchCol($query));
    }

    /**
     * Groups thread fields by their field group.
     *
     * @param array $fields
     *
     * @return array [field group id][key] => info
     */
    public function groupThreadFields(array $fields)
    {
        $return = array();

        foreach ($fields as $fieldId => $field) {
            $return[$field['field_group_id']][$fieldId] = $field;
        }

        return $return;
    }

    /**
     * Prepares a thread field for display.
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
    public function prepareThreadField(array $field, $getFieldChoices = false, $fieldValue = null, $valueSaved = true,
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

        $field['title'] = new XenForo_Phrase($this->getThreadFieldTitlePhraseName($field['field_id']));
        $field['description'] = new XenForo_Phrase($this->getThreadFieldDescriptionPhraseName($field['field_id']));

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
                $field['fieldChoices'] = $this->getThreadFieldChoices($field['field_id'], $field['field_choices']);
            }
        }

        $field['isEditable'] = true;

        $field['required'] = $required;

        return $field;
    }

    /**
     * Prepares a list of thread fields for display.
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
    public function prepareThreadFields(array $fields, $getFieldChoices = false, array $fieldValues = array(), $valueSaved = true,
        array $nodeRequiredFields = array(), array $extraData = array())
    {
        foreach ($fields as &$field) {
            $value = isset($fieldValues[$field['field_id']]) ? $fieldValues[$field['field_id']] : null;
            $required = in_array($field['field_id'], $nodeRequiredFields);
            $field = $this->prepareThreadField($field, $getFieldChoices, $value, $valueSaved, $required, $extraData);
        }

        return $fields;
    }

    /**
     * Prepares a list of grouped thread fields for display.
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
    public function prepareGroupedThreadFields(array $fieldGroups, $getFieldChoices = false, array $fieldValues = array(),
        $valueSaved = true, array $nodeRequiredFields = array(), array $extraData = array())
    {
        foreach ($fieldGroups as &$fieldGroup) {
            $fieldGroup['fields'] = $this->prepareThreadFields($fieldGroup['fields'], $getFieldChoices, $fieldValues,
                $valueSaved, $nodeRequiredFields, $extraData);
        }

        return $fieldGroups;
    }

    public function getThreadFieldTitlePhraseName($fieldId)
    {
        return 'thread_field_' . $fieldId;
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
    public function getThreadFieldChoices($fieldId, $choices, $master = false)
    {
        if (!is_array($choices)) {
            $choices = ($choices ? @unserialize($choices) : array());
        }

        if (!$master) {
            foreach ($choices as $value => &$text) {
                $text = new XenForo_Phrase($this->getThreadFieldChoicePhraseName($fieldId, $value));
            }
        }

        $xenOptions = XenForo_Application::get('options');

        if ($xenOptions->waindigo_customFields_sortChoicesAlphabetically) {
            if (!$master && version_compare(phpversion(), '5.3.0', '>') && extension_loaded('intl')) {
                $visitor = XenForo_Visitor::getInstance();
                $language = $visitor->getLanguage();
                $col = new \Collator($language['language_code']);
                $col->asort($choices);
            } else {
                asort($choices, SORT_LOCALE_STRING);
            }
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
    public function verifyThreadFieldValue(array $field, &$value, &$error = '')
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
                            $field['custom_field_type'] = 'thread';
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

    public function updateThreadFieldForumAssociationByThreadField($fieldId, array $nodeIds)
    {
        $emptyNodeKey = array_search(0, $nodeIds);
        if ($emptyNodeKey !== false) {
            unset($nodeIds[$emptyNodeKey]);
        }

        $nodeIds = array_unique($nodeIds);

        $existingNodeIds = $this->getForumAssociationsByThreadField($fieldId);
        if (!$nodeIds && !$existingNodeIds) {
            return; // nothing to do
        }

        $db = $this->_getDb();
        XenForo_Db::beginTransaction($db);

        $db->delete('xf_forum_field', 'field_id = ' . $db->quote($fieldId));

        foreach ($nodeIds as $nodeId) {
            $db->insert('xf_forum_field',
                array(
                    'node_id' => $nodeId,
                    'field_id' => $fieldId,
                    'field_value' => ''
                ));
        }

        $rebuildNodeIds = array_unique(array_merge($nodeIds, $existingNodeIds));
        $this->rebuildThreadFieldForumAssociationCache($rebuildNodeIds);

        XenForo_Db::commit($db);
    }

    public function updateThreadFieldForumAssociationByForum($nodeId, array $fieldIds)
    {
        $emptyFieldKey = array_search(0, $fieldIds, true);
        if ($emptyFieldKey !== false) {
            unset($fieldIds[$emptyFieldKey]);
        }

        $fieldIds = array_unique($fieldIds);

        $db = $this->_getDb();

        XenForo_Db::beginTransaction($db);

        $db->delete('xf_forum_field', 'node_id = ' . $db->quote($nodeId));

        foreach ($fieldIds as $fieldId) {
            $db->insert('xf_forum_field',
                array(
                    'node_id' => $nodeId,
                    'field_id' => $fieldId,
                    'field_value' => ''
                ));
        }

        $this->rebuildThreadFieldForumAssociationCache($nodeId);

        XenForo_Db::commit($db);
    }

    public function rebuildThreadFieldForumAssociationCache($nodeIds)
    {
        if (!is_array($nodeIds)) {
            $nodeIds = array(
                $nodeIds
            );
        }
        if (!$nodeIds) {
            return;
        }

        $nodes = $this->_getNodeModel()->getAllNodes();

        $db = $this->_getDb();

        $newCache = array();

        foreach ($this->getThreadFieldsInForums($nodeIds) as $field) {
            $fieldGroupId = $field['field_group_id'];
            $newCache[$field['node_id']][$fieldGroupId][$field['field_id']] = $field['field_id'];
        }

        XenForo_Db::beginTransaction($db);

        foreach ($nodeIds as $nodeId) {
            $update = (isset($newCache[$nodeId]) ? serialize($newCache[$nodeId]) : '');
            if (isset($nodes[$nodeId])) {
                if ($nodes[$nodeId]['node_type_id'] == 'Library') {
                    $db->update('xf_library',
                        array(
                            'field_cache' => $update
                        ), 'node_id = ' . $db->quote($nodeId));
                } else {
                    $db->update('xf_forum',
                        array(
                            'field_cache' => $update
                        ), 'node_id = ' . $db->quote($nodeId));
                }
            }
        }

        XenForo_Db::commit($db);
    }

    /**
     * Fetches an array of custom thread fields including display group info,
     * for use in <xen:options source />
     *
     * @param array $conditions
     * @param array $fetchOptions
     *
     * @return array
     */
    public function getThreadFieldOptions(array $conditions = array(), array $fetchOptions = array())
    {
        $fieldGroups = $this->getThreadFieldsByGroups($conditions, $fetchOptions);

        $options = array();

        foreach ($fieldGroups as $fieldGroupId => $fields) {
            if ($fields) {
                if ($fieldGroupId) {
                    $groupTitle = new XenForo_Phrase($this->getThreadFieldGroupTitlePhraseName($fieldGroupId));
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
     * Gets the possible thread field types.
     *
     * @return array [type] => keys: value, label, hint (optional)
     */
    public function getThreadFieldTypes()
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
     * Maps thread fields to their high level type "group".
     * Field types can be changed only
     * within the group.
     *
     * @return array [field type] => type group
     */
    public function getThreadFieldTypeMap()
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
    public function getThreadFieldDescriptionPhraseName($fieldId)
    {
        return 'thread_field_' . $fieldId . '_desc';
    }

    /**
     * Gets a field choices's phrase name.
     *
     * @param string $fieldId
     * @param string $choice
     *
     * @return string
     */
    public function getThreadFieldChoicePhraseName($fieldId, $choice)
    {
        return 'thread_field_' . $fieldId . '_choice_' . $choice;
    }

    /**
     * Gets a field's master title phrase text.
     *
     * @param string $id
     *
     * @return string
     */
    public function getThreadFieldMasterTitlePhraseValue($id)
    {
        $phraseName = $this->getThreadFieldTitlePhraseName($id);
        return $this->_getPhraseModel()->getMasterPhraseValue($phraseName);
    }

    /**
     * Gets a field's master description phrase text.
     *
     * @param string $id
     *
     * @return string
     */
    public function getThreadFieldMasterDescriptionPhraseValue($id)
    {
        $phraseName = $this->getThreadFieldDescriptionPhraseName($id);
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
     * Gets the thread field values for the given thread.
     *
     * @param integer $threadId
     *
     * @return array [field id] => value (may be string or array)
     */
    public function getThreadFieldValues($threadId)
    {
        $fields = $this->_getDb()->fetchAll(
            '
            SELECT value.*, field.field_type
            FROM xf_thread_field_value AS value
            INNER JOIN xf_thread_field AS field ON (field.field_id = value.field_id)
            WHERE value.thread_id = ?
        ', $threadId);

        return $this->_prepareFieldValues($fields);
    }

    /**
     * Gets the thread field values for the given thread.
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
                INNER JOIN xf_thread_field AS field ON (field.field_id = value.field_id)
                WHERE value.article_id = ?
            ', $articleId);

        return $this->_prepareFieldValues($fields);
    }

    /**
     * Gets the default thread field values for the given forum.
     *
     * @param integer $nodeId
     *
     * @return array [field id] => value (may be string or array)
     */
    public function getDefaultThreadFieldValues($nodeId = null)
    {
        if ($nodeId) {
            $fields = $this->_getDb()->fetchAll(
                '
                SELECT ff.*, field.field_type
                FROM xf_forum_field AS ff
                INNER JOIN xf_thread_field AS field ON (field.field_id = ff.field_id)
                WHERE ff.node_id = ?
                ', $nodeId);

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
     * Rebuilds the cache of thread field info for front-end display
     *
     * @return array
     */
    public function rebuildThreadFieldCache()
    {
        $cache = array();
        foreach ($this->getThreadFields() as $fieldId => $field) {
            $cache[$fieldId] = XenForo_Application::arrayFilterKeys($field,
                array(
                    'field_id',
                    'field_type',
                    'field_group_id'
                ));
        }

        $this->_getDataRegistryModel()->set('threadFieldsInfo', $cache);
        return $cache;
    }

    /**
     * Rebuilds the 'materialized_order' field in the field table,
     * based on the canonical display_order data in the field and field_group
     * tables.
     */
    public function rebuildThreadFieldMaterializedOrder()
    {
        $fields = $this->getThreadFields(array(), array(
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
                    UPDATE xf_thread_field SET materialized_order = CASE field_id
                    ' . implode(' ', $updates) . '
                    END
                    WHERE field_id IN(' . $db->quote(array_keys($updates)) . ')
                ');
        }
    }

    public function verifyThreadFieldIsUsable($fieldId, $nodeId, array $viewingUser = null)
    {
        $this->standardizeViewingUserReference($viewingUser);

        if (!$fieldId) {
            return true; // not picking one, always ok
        }

        $field = $this->getThreadFieldIfInForum($fieldId, $nodeId);
        if (!$field) {
            return false; // bad field or bad node
        }

        return $this->_verifyThreadFieldIsUsableInternal($field, $viewingUser);
    }

    protected function _verifyThreadFieldIsUsableInternal(array $field, array $viewingUser)
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
    public function getThreadFieldGroupById($fieldGroupId)
    {
        if (!$fieldGroupId) {
            return array();
        }

        return $this->_getDb()->fetchRow(
            '
                SELECT *
                FROM xf_thread_field_group
                WHERE field_group_id = ?
            ', $fieldGroupId);
    }

    public function getAllThreadFieldGroups()
    {
        return $this->fetchAllKeyed(
            '
                SELECT *
                FROM xf_thread_field_group
                ORDER BY display_order
            ', 'field_group_id');
    }

    public function getThreadFieldGroupOptions($selectedGroupId = '')
    {
        $fieldGroups = $this->getAllThreadFieldGroups();
        $fieldGroups = $this->prepareThreadFieldGroups($fieldGroups);

        $options = array();

        foreach ($fieldGroups as $fieldGroupId => $fieldGroup) {
            $options[$fieldGroupId] = $fieldGroup['title'];
        }

        return $options;
    }

    public function mergeThreadFieldsIntoGroups(array $fields, array $fieldGroups)
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

    public function getThreadFieldGroupTitlePhraseName($fieldGroupId)
    {
        return 'thread_field_group_' . $fieldGroupId;
    }

    public function prepareThreadFieldGroups(array $fieldGroups)
    {
        return array_map(array(
            $this,
            'prepareThreadFieldGroup'
        ), $fieldGroups);
    }

    public function prepareThreadFieldGroup(array $fieldGroup)
    {
        $fieldGroup['title'] = new XenForo_Phrase(
            $this->getThreadFieldGroupTitlePhraseName($fieldGroup['field_group_id']));

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

        $fields = $this->getThreadFields(array(
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
                new XenForo_Phrase('thread_field_' . $field['field_id'])));

        $descriptionNode = $document->createElement('description');
        $rootNode->appendChild($descriptionNode);
        $descriptionNode->appendChild(
            XenForo_Helper_DevelopmentXml::createDomCdataSection($document,
                new XenForo_Phrase('thread_field_' . $field['field_id'] . '_desc')));

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
            $overwriteField = $this->getThreadFieldById($overwriteFieldId);
        }

        $db = $this->_getDb();
        XenForo_Db::beginTransaction($db);

        $dw = XenForo_DataWriter::create('Waindigo_CustomFields_DataWriter_ThreadField');
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
     * @return XenForo_Model_Node
     */
    protected function _getNodeModel()
    {
        return $this->getModelFromCache('XenForo_Model_Node');
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
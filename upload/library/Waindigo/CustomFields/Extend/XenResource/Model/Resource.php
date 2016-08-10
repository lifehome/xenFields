<?php

/**
 *
 * @see XenResource_Model_Resource
 */
class Waindigo_CustomFields_Extend_XenResource_Model_Resource extends XFCP_Waindigo_CustomFields_Extend_XenResource_Model_Resource
{

    /**
     *
     * @see XenResource_Model_Resource::prepareResourceFetchOptions()
     */
    public function prepareResourceFetchOptions(array $fetchOptions)
    {
        $resourceFetchOptions = parent::prepareResourceFetchOptions($fetchOptions);

        $db = $this->_getDb();

        if (!empty($fetchOptions['order'])) {
            if (strlen($fetchOptions['order']) > strlen('custom_field_') &&
                 substr($fetchOptions['order'], 0, strlen('custom_field_')) == 'custom_field_') {
                $customFieldId = substr($fetchOptions['order'], strlen('custom_field_'));
                $fetchOptions['customFields'][$customFieldId] = true;
            }
        }

        if (!empty($fetchOptions['customFields']) && is_array($fetchOptions['customFields'])) {
            foreach ($fetchOptions['customFields'] as $customFieldId => $value) {
                if ($value === '' || (is_array($value) && !$value)) {
                    continue;
                }

                $isExact = !empty($fetchOptions['customFieldsExact'][$customFieldId]);
                $customFieldId = preg_replace('/[^a-z0-9_]/i', '', $customFieldId);
                $resourceFetchOptions['selectFields'] .= ", resource_field_value_$customFieldId.field_value AS custom_field_$customFieldId";

                if ($value === true) {
                    $resourceFetchOptions['joinTables'] .= "
                    LEFT JOIN xf_resource_field_value AS resource_field_value_$customFieldId ON
                    (resource_field_value_$customFieldId.resource_id = resource.resource_id
                    AND resource_field_value_$customFieldId.field_id = " . $this->_getDb()->quote($customFieldId) .
                         ")";
                } else {
                    $possibleValues = array();
                    foreach ((array) $value as $possible) {
                        if ($isExact) {
                            $possibleValues[] = "resource_field_value_$customFieldId.field_value = " .
                                 $this->_getDb()->quote($possible);
                        } else {
                            $possibleValues[] = "resource_field_value_$customFieldId.field_value LIKE " .
                                 XenForo_Db::quoteLike($possible, 'lr');
                        }
                    }

                    $resourceFetchOptions['joinTables'] .= "
                    INNER JOIN xf_resource_field_value AS resource_field_value_$customFieldId ON
                    (resource_field_value_$customFieldId.resource_id = resource.resource_id
                    AND resource_field_value_$customFieldId.field_id = " . $this->_getDb()->quote($customFieldId) . "
						AND (" . implode(' OR ', $possibleValues) . "))";
                }
            }
        }

        return $resourceFetchOptions;
    }

    /**
     *
     * @see XenResource_Model_Resource::prepareResourceOrderOptions()
     */
    public function getOrderByClause(array $choices, array $fetchOptions, $defaultOrderSql = '')
    {
        $fieldModel = $this->_getFieldModel();

        if (!empty($fetchOptions['order'])) {
            if (strlen($fetchOptions['order']) > strlen('custom_field_') &&
                 substr($fetchOptions['order'], 0, strlen('custom_field_')) == 'custom_field_') {
                $customFields = $fieldModel->getResourceFields();

                foreach ($customFields as $customFieldId => $field) {
                    if (in_array($field['allow_sort'], array(
                        'asc',
                        'desc'
                    ))) {
                        $choices['custom_field_' . $customFieldId] = 'resource_field_value_' . $customFieldId . '.field_value ' .
                             strtoupper($field['allow_sort']) . ', resource.last_update %s';
                    }
                }
            }
        }

        return parent::getOrderByClause($choices, $fetchOptions, $defaultOrderSql);
    }

    /**
     *
     * @see XenResource_Model_Resource::getTabOrderChoices()
     */
    public function getTabOrderChoices()
    {
        $choices = parent::getTabOrderChoices();

        $fieldModel = $this->_getFieldModel();

        $customFields = $fieldModel->getResourceFields();

        foreach ($customFields as $customFieldId => $field) {
            if (in_array($field['allow_sort'], array(
                'asc',
                'desc'
            ))) {
                $choices['custom_field_' . $customFieldId] = new XenForo_Phrase(
                    $fieldModel->getResourceFieldTitlePhraseName($field['field_id']));
            }
        }

        return $choices;
    }
}
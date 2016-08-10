<?php

class Waindigo_CustomFields_Search_Helper_CustomField
{

    public static function getTypeConstraintsFromInput(XenForo_Input $input, array $fields, $fieldType)
    {
        $xenOptions = XenForo_Application::get('options');

        $constraints = array();

        $fieldValues = $input->filterSingle('custom_fields', XenForo_Input::ARRAY_SIMPLE);
        if ($fieldValues && $fields) {
            foreach ($fields as $fieldId => $field) {
                if (empty($fieldValues[$fieldId])) {
                    continue;
                }
                $fieldValue = $fieldValues[$fieldId];
                if (in_array($field['field_type'],
                    array(
                        'multiselect',
                        'checkbox'
                    ))) {
                    if (is_array($fieldValue)) {
                        $newFieldValue = array();
                        foreach ($fieldValue as $_fieldValue) {
                            $newFieldValue[$_fieldValue] = $_fieldValue;
                        }
                        $fieldValue = array(
                            '=',
                            serialize($newFieldValue)
                        );
                    } else {
                        $fieldValue = array(
                            'LIKE',
                            '%' . serialize($fieldValue) . '%'
                        );
                    }

                } elseif ($xenOptions->waindigo_customFields_partialSearch) {
                    $fieldValue = array(
                        'LIKE',
                        '%' . $fieldValue . '%'
                    );
                } else {
                    $fieldValue = array(
                        '=',
                        $fieldValue
                    );
                }
                $constraints[$fieldType . '_field_id_' . $fieldId] = $fieldId;
                $constraints[$fieldType . '_field_value_' . $fieldId] = $fieldValue;
            }
        }

        return $constraints;
    }

    public static function processConstraint(XenForo_Search_SourceHandler_Abstract $sourceHandler, $constraint,
        $constraintInfo, array $constraints, $fieldType)
    {
        if (strlen($constraint) > strlen($fieldType . '_field_id_') &&
             substr($constraint, 0, strlen($fieldType . '_field_id_')) == $fieldType . '_field_id_') {
            if ($constraintInfo) {
                $constraintInfo = strval($constraintInfo);
                return array(
                    'query' => array(
                        $fieldType . '_field_value_' . $constraintInfo,
                        'field_id',
                        '=',
                        $constraintInfo
                    )
                );
            }
        }
        if (!is_array($constraintInfo)) {
            $constraintInfo = array(
                '=',
                $constraintInfo
            );
        }
        if (!in_array($constraintInfo[0], array(
            '=',
            'LIKE'
        ))) {
            $constraintInfo[0] = '=';
        }
        if (strlen($constraint) > strlen($fieldType . '_field_value_') &&
             substr($constraint, 0, strlen($fieldType . '_field_value_')) == $fieldType . '_field_value_') {
            if ($constraintInfo) {
                return array(
                    'query' => array(
                        strval($constraint),
                        'field_value',
                        $constraintInfo[0],
                        $constraintInfo[1]
                    )
                );
            }
        }

        return false;
    }
}
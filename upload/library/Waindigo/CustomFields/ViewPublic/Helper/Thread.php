<?php

class Waindigo_CustomFields_ViewPublic_Helper_Thread
{

    /**
     * Gets the HTML value of the thread field.
     *
     * @param array $field
     * @param mixed $value Value of the field; if null, pulls from field_value
     *            in field
     */
    public static function getThreadFieldValueHtml(XenForo_View $view, array $field, $value = null)
    {
        if ($value === null && isset($field['field_value'])) {
            $value = $field['field_value'];
        }

        if ($value === '' || $value === null) {
            return '';
        }

        $multiChoice = false;
        $choice = '';

        switch ($field['field_type']) {
            case 'radio':
            case 'select':
                $choice = $value;
                $value = new XenForo_Phrase("thread_field_$field[field_id]_choice_$value");
                $value->setPhraseNameOnInvalid(false);
                break;

            case 'checkbox':
            case 'multiselect':
                $multiChoice = true;
                if (!is_array($value) || count($value) == 0) {
                    return '';
                }

                $newValues = array();
                foreach ($value as $id => $choice) {
                    $phrase = new XenForo_Phrase("thread_field_$field[field_id]_choice_$choice");
                    $phrase->setPhraseNameOnInvalid(false);

                    $newValues[$choice] = $phrase;
                }
                $value = $newValues;
                break;

            case 'textbox':
            case 'textarea':
            default:
                $value = nl2br(htmlspecialchars(XenForo_Helper_String::censorString($value)));
        }

        if (!empty($field['display_callback_class']) && !empty($field['display_callback_method'])) {
            $value = call_user_func_array(array(
                $field['display_callback_class'],
                $field['display_callback_method']
            ), array(
                $view,
                $field,
                $value
            ));
        } elseif (!empty($field['display_template'])) {
            if ($multiChoice && is_array($value)) {
                foreach ($value as $choice => &$thisValue) {
                    $thisValue = strtr($field['display_template'],
                        array(
                            '{$fieldId}' => $field['field_id'],
                            '{$value}' => $thisValue,
                            '{$valueUrl}' => urlencode($thisValue),
                            '{$choice}' => $choice
                        ));
                }
            } else {
                $value = strtr($field['display_template'],
                    array(
                        '{$fieldId}' => $field['field_id'],
                        '{$value}' => $value,
                        '{$valueUrl}' => urlencode($value),
                        '{$choice}' => $choice
                    ));
            }
        }

        return $value;
    }

    /**
     * Add thread field HTML keys to the given list of fields.
     *
     * @param XenForo_View $view
     * @param array $fields
     * @param array $values Field values; pulls from field_value in fields if
     *            not specified here
     */
    public static function addThreadFieldsValueHtml(XenForo_View $view, array $fields, array $values = array())
    {
        foreach ($fields as &$field) {
            $field['fieldValueHtml'] = self::getThreadFieldValueHtml($view, $field,
                isset($values[$field['field_id']]) ? $values[$field['field_id']] : null);
        }

        return $fields;
    }
}
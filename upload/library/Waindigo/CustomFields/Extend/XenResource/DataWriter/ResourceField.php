<?php

/**
 *
 * @see XenResource_DataWriter_ResourceField
 */
class Waindigo_CustomFields_Extend_XenResource_DataWriter_ResourceField extends XFCP_Waindigo_CustomFields_Extend_XenResource_DataWriter_ResourceField
{

    const OPTION_MASS_UPDATE = 'massUpdate';

    /**
     *
     * @see XenResource_DataWriter_ResourceField::_getFields()
     */
    protected function _getFields()
    {
        $fields = parent::_getFields();

        $fields['xf_resource_field']['display_group']['allowedValues'][] = 'none';
        $fields['xf_resource_field']['display_callback_class'] = array(
            'type' => self::TYPE_STRING,
            'default' => '',
            'maxLength' => 75
        );
        $fields['xf_resource_field']['display_callback_method'] = array(
            'type' => self::TYPE_STRING,
            'default' => '',
            'maxLength' => 75
        );
        $fields['xf_resource_field']['allowed_user_group_ids'] = array(
            'type' => self::TYPE_UNKNOWN,
            'default' => '',
            'verification' => array(
                '$this',
                '_verifyAllowedUserGroupIds'
            )
        );
        $fields['xf_resource_field']['addon_id'] = array(
            'type' => self::TYPE_STRING,
            'default' => '',
            'maxLength' => 25
        );
        $fields['xf_resource_field']['field_choices_callback_class'] = array(
            'type' => self::TYPE_STRING,
            'default' => '',
            'maxLength' => 75
        );
        $fields['xf_resource_field']['field_choices_callback_method'] = array(
            'type' => self::TYPE_STRING,
            'default' => '',
            'maxLength' => 75
        );
        $fields['xf_resource_field']['field_type']['allowedValues'][] = 'callback';
        $fields['xf_resource_field']['field_callback_class'] = array(
            'type' => self::TYPE_STRING,
            'default' => '',
            'maxLength' => 75
        );
        $fields['xf_resource_field']['field_callback_method'] = array(
            'type' => self::TYPE_STRING,
            'default' => '',
            'maxLength' => 75
        );
        $fields['xf_resource_field']['export_callback_class'] = array(
            'type' => self::TYPE_STRING,
            'default' => '',
            'maxLength' => 75
        );
        $fields['xf_resource_field']['export_callback_method'] = array(
            'type' => self::TYPE_STRING,
            'default' => '',
            'maxLength' => 75
        );
        $fields['xf_resource_field']['viewable_information'] = array(
            'type' => self::TYPE_UINT,
            'default' => 0
        );
        $fields['xf_resource_field']['allow_sort'] = array(
            'type' => self::TYPE_STRING,
            'allowedValues' => array(
                'none',
                'asc',
                'desc'
            ),
            'default' => 'none'
        );

        return $fields;
    }

    /**
     *
     * @see XenResource_DataWriter_ResourceField::_getDefaultOptions()
     */
    protected function _getDefaultOptions()
    {
        $defaultOptions = parent::_getDefaultOptions();

        $defaultOptions[self::OPTION_MASS_UPDATE] = false;

        return $defaultOptions;
    }

    /**
     *
     * @see XenResource_DataWriter_ResourceField::_preSave()
     */
    protected function _preSave()
    {
        if (isset($GLOBALS['XenResource_ControllerAdmin_Field'])) {
            /* @var $controller XenResource_ControllerAdmin_Field */
            $controller = $GLOBALS['XenResource_ControllerAdmin_Field'];

            $dwInput = $controller->getInput()->filter(
                array(
                    'field_choices_callback_class' => XenForo_Input::STRING,
                    'field_choices_callback_method' => XenForo_Input::STRING,
                    'display_callback_class' => XenForo_Input::STRING,
                    'display_callback_method' => XenForo_Input::STRING,
                    'addon_id' => XenForo_Input::STRING,
                    'field_callback_class' => XenForo_Input::STRING,
                    'field_callback_method' => XenForo_Input::STRING,
                    'export_callback_class' => XenForo_Input::STRING,
                    'export_callback_method' => XenForo_Input::STRING,
                    'viewable_information' => XenForo_Input::UINT,
                    'allow_sort' => XenForo_Input::STRING
                ));
            if (!in_array($dwInput['allow_sort'], array('asc', 'desc'))) {
                $dwInput['allow_sort'] = 'none';
            }
            $this->bulkSet($dwInput);

            $input = $controller->getInput()->filter(
                array(
                    'usable_user_group_type' => XenForo_Input::STRING,
                    'user_group_ids' => array(
                        XenForo_Input::UINT,
                        'array' => true
                    )
                ));

            if ($input['usable_user_group_type'] == 'all') {
                $allowedGroupIds = array(
                    -1
                ); // -1 is a sentinel for all groups
            } else {
                $allowedGroupIds = $input['user_group_ids'];
            }
            $this->set('allowed_user_group_ids', $allowedGroupIds);
        }

        if ($this->_errorHandler == self::ERROR_EXCEPTION) {
            try {
                parent::_preSave();
            } catch (Exception $e) {
                $errorPhrase = new XenForo_Phrase('please_enter_at_least_one_choice');
                if ($e->getMessage() == $errorPhrase && $this->get('field_choices_callback_class') ||
                     $this->get('field_choices_callback_method')) {
                    $this->setFieldChoices(array());
                }
            }
        } else {
            parent::_preSave();
            if ($this->hasErrors()) {
                $errorPhrase = new XenForo_Phrase('please_enter_at_least_one_choice');
                foreach ($this->getErrors() as $errorKey => $e) {
                    if ((string) $e == (string) $errorPhrase && $this->get('field_choices_callback_class') ||
                         $this->get('field_choices_callback_method')) {
                        $this->setFieldChoices(array());
                        unset($this->_errors[$errorKey]);
                    }
                }
            }
        }

        if ($this->isChanged('field_choices_callback_class') || $this->isChanged('field_choices_callback_method')) {
            $class = $this->get('field_choices_callback_class');
            $method = $this->get('field_choices_callback_method');

            if (!$class || !$method) {
                $this->set('field_choices_callback_class', '');
                $this->set('field_choices_callback_method', '');
            } elseif (!XenForo_Application::autoload($class) || !method_exists($class, $method)) {
                $this->error(new XenForo_Phrase('please_enter_valid_callback_method'), 'callback_method');
            }
        }

        if ($this->isChanged('display_callback_class') || $this->isChanged('display_callback_method')) {
            $class = $this->get('display_callback_class');
            $method = $this->get('display_callback_method');

            if (!$class || !$method) {
                $this->set('display_callback_class', '');
                $this->set('display_callback_method', '');
            } elseif (!XenForo_Application::autoload($class) || !method_exists($class, $method)) {
                $this->error(new XenForo_Phrase('please_enter_valid_callback_method'), 'callback_method');
            }
        }

        if ($this->isChanged('field_callback_class') || $this->isChanged('field_callback_method')) {
            $class = $this->get('field_callback_class');
            $method = $this->get('field_callback_method');

            if (!$class || !$method) {
                $this->set('field_callback_class', '');
                $this->set('field_callback_method', '');
            } elseif (!XenForo_Application::autoload($class) || !method_exists($class, $method)) {
                $this->error(new XenForo_Phrase('please_enter_valid_callback_method'), 'callback_method');
            }
        }

        if ($this->isChanged('export_callback_class') || $this->isChanged('export_callback_method')) {
            $class = $this->get('export_callback_class');
            $method = $this->get('export_callback_method');

            if (!$class || !$method) {
                $this->set('export_callback_class', '');
                $this->set('export_callback_method', '');
            } elseif (!XenForo_Application::autoload($class) || !method_exists($class, $method)) {
                $this->error(new XenForo_Phrase('please_enter_valid_callback_method'), 'callback_method');
            }
        }

        if ($this->get('field_choices_callback_class') || $this->get('field_choices_callback_method')) {
            $this->setFieldChoices(array());
        }
    }

    /**
     * Verifies the allowed user group IDs.
     *
     * @param array|string $userGroupIds Array or comma-delimited list
     *
     * @return boolean
     */
    protected function _verifyAllowedUserGroupIds(&$userGroupIds)
    {
        if (!is_array($userGroupIds)) {
            $userGroupIds = preg_split('#,\s*#', $userGroupIds);
        }

        $userGroupIds = array_map('intval', $userGroupIds);
        $userGroupIds = array_unique($userGroupIds);
        sort($userGroupIds, SORT_NUMERIC);
        $userGroupIds = implode(',', $userGroupIds);

        return true;
    }
}
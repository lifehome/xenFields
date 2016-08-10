<?php

/**
 *
 * @see XenForo_DataWriter_UserField
 */
class Waindigo_CustomFields_Extend_XenForo_DataWriter_UserField extends XFCP_Waindigo_CustomFields_Extend_XenForo_DataWriter_UserField
{

    const OPTION_MASS_UPDATE = 'massUpdate';

    /**
     *
     * @see XenForo_DataWriter_UserField::_getFields()
     */
    protected function _getFields()
    {
        $fields = parent::_getFields();

        $fields['xf_user_field']['display_callback_class'] = array(
            'type' => self::TYPE_STRING,
            'default' => '',
            'maxLength' => 75
        );
        $fields['xf_user_field']['display_callback_method'] = array(
            'type' => self::TYPE_STRING,
            'default' => '',
            'maxLength' => 75
        );
        $fields['xf_user_field']['addon_id'] = array(
            'type' => self::TYPE_STRING,
            'default' => '',
            'maxLength' => 25
        );
        $fields['xf_user_field']['field_choices_callback_class'] = array(
            'type' => self::TYPE_STRING,
            'default' => '',
            'maxLength' => 75
        );
        $fields['xf_user_field']['field_choices_callback_method'] = array(
            'type' => self::TYPE_STRING,
            'default' => '',
            'maxLength' => 75
        );
        $fields['xf_user_field']['field_type']['allowedValues'][] = 'callback';
        $fields['xf_user_field']['field_callback_class'] = array(
            'type' => self::TYPE_STRING,
            'default' => '',
            'maxLength' => 75
        );
        $fields['xf_user_field']['field_callback_method'] = array(
            'type' => self::TYPE_STRING,
            'default' => '',
            'maxLength' => 75
        );
        $fields['xf_user_field']['export_callback_class'] = array(
            'type' => self::TYPE_STRING,
            'default' => '',
            'maxLength' => 75
        );
        $fields['xf_user_field']['export_callback_method'] = array(
            'type' => self::TYPE_STRING,
            'default' => '',
            'maxLength' => 75
        );
        $fields['xf_user_field']['export_callback_method'] = array(
            'type' => self::TYPE_STRING,
            'default' => '',
            'maxLength' => 75
        );
        $fields['xf_user_field']['search_advanced_user_waindigo'] = array(
            'type' => self::TYPE_UINT,
            'default' => 1
        );

        return $fields;
    }

    /**
     *
     * @see XenForo_DataWriter_UserField::_getDefaultOptions()
     */
    protected function _getDefaultOptions()
    {
        $defaultOptions = parent::_getDefaultOptions();

        $defaultOptions[self::OPTION_MASS_UPDATE] = false;

        return $defaultOptions;
    }

    /**
     *
     * @see XenForo_DataWriter_UserField::_preSave()
     */
    protected function _preSave()
    {
        if (isset($GLOBALS['XenForo_ControllerAdmin_UserField'])) {
            /* @var $controller XenForo_ControllerAdmin_UserField */
            $controller = $GLOBALS['XenForo_ControllerAdmin_UserField'];

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
                    'export_callback_method' => XenForo_Input::STRING
                ));
            $this->bulkSet($dwInput);

            if (XenForo_Application::$versionId > 1020000) {
                $addOns = XenForo_Application::get('addOns');
                $isUsInstalled = !empty($addOns['Waindigo_UserSearch']);
            } else {
                $isUsInstalled = $this->getAddOnById('Waindigo_UserSearch') ? true : false;
            }

            if ($isUsInstalled) {
                $searchAdvancedUser = $controller->getInput()->filterSingle('search_advanced_user_waindigo', XenForo_Input::UINT);
                $this->set('search_advanced_user_waindigo', $searchAdvancedUser ? 1 : 0);
            }
        }

        if ($this->isChanged('match_callback_class') || $this->isChanged('match_callback_method')) {
            $class = $this->get('match_callback_class');
            $method = $this->get('match_callback_method');

            if (!$class || !$method) {
                $this->set('match_callback_class', '');
                $this->set('match_callback_method', '');
            } elseif (!XenForo_Application::autoload($class) || !method_exists($class, $method)) {
                $this->error(new XenForo_Phrase('please_enter_valid_callback_method'), 'callback_method');
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

        if ($this->isUpdate() && $this->isChanged('field_type')) {
            $typeMap = $this->_getFieldModel()->getObjectFieldTypeMap();
            if ($typeMap[$this->get('field_type')] != $typeMap[$this->getExisting('field_type')]) {
                $this->error(new XenForo_Phrase('you_may_not_change_field_to_different_type_after_it_has_been_created'),
                    'field_type');
            }
        }

        if (!$this->get('field_choices_callback_class') && !$this->get('field_choices_callback_method') && in_array(
            $this->get('field_type'),
            array(
                'select',
                'radio',
                'checkbox',
                'multiselect'
            ))) {
            if (($this->isInsert() && !$this->_fieldChoices) || (is_array($this->_fieldChoices) && !$this->_fieldChoices)) {
                $this->error(new XenForo_Phrase('please_enter_at_least_one_choice'), 'field_choices', false);
            }
        } else {
            $this->setFieldChoices(array());
        }

        if (!$this->getOption(self::OPTION_MASS_UPDATE)) {
            $titlePhrase = $this->getExtraData(self::DATA_TITLE);
            if ($titlePhrase !== null && strlen($titlePhrase) == 0) {
                $this->error(new XenForo_Phrase('please_enter_valid_title'), 'title');
            }
        }
    }
}
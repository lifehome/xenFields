<?php

/**
 * Data writer for custom thread fields.
 */
class Waindigo_CustomFields_DataWriter_ThreadField extends Waindigo_CustomFields_DataWriter_AbstractField
{

    /**
     * Gets the object that represents the definition of this type of custom
     * field.
     *
     * @return Waindigo_CustomFields_Definition_ThreadField
     */
    public function getFieldDefinition()
    {
        return new Waindigo_CustomFields_Definition_ThreadField();
    }

    /**
     * Gets the fields that are defined for the table.
     * See parent for explanation.
     *
     * @return array
     */
    protected function _getFields()
    {
        $fields = $this->_getCommonFields();
        $fields['xf_thread_field']['viewable_forum_view'] = array(
            'type' => self::TYPE_UINT,
            'default' => 0
        );
        $fields['xf_thread_field']['viewable_thread_view'] = array(
            'type' => self::TYPE_UINT,
            'default' => 0
        );
        $fields['xf_thread_field']['below_title_on_create'] = array(
            'type' => self::TYPE_UINT,
            'default' => 0
        );
        $fields['xf_thread_field']['search_advanced_thread_waindigo'] = array(
            'type' => self::TYPE_UINT,
            'default' => 1
        );
        $fields['xf_thread_field']['search_quick_forum_waindigo'] = array(
            'type' => self::TYPE_UINT,
            'default' => 0
        );
        return $fields;
    }

    /**
     * Gets the actual existing data out of data that was passed in.
     * See parent for explanation.
     *
     * @param mixed
     *
     * @return array false
     */
    protected function _getExistingData($data)
    {
        if (!$id = $this->_getExistingPrimaryKey($data, 'field_id')) {
            return false;
        }

        return array(
            'xf_thread_field' => $this->_getFieldModel()->getThreadFieldById($id)
        );
    }

    /**
     * Verifies that the ID contains valid characters and does not already
     * exist.
     *
     * @param $id
     *
     * @return boolean
     */
    protected function _verifyFieldId(&$id)
    {
        if (preg_match('/[^a-zA-Z0-9_]/', $id)) {
            $this->error(new XenForo_Phrase('please_enter_an_id_using_only_alphanumeric'), 'field_id');
            return false;
        }

        if ($id !== $this->getExisting('field_id') && $this->_getFieldModel()->getThreadFieldById($id)) {
            $this->error(new XenForo_Phrase('field_ids_must_be_unique'), 'field_id');
            return false;
        }

        return true;
    }

    /**
     * Pre-save behaviors.
     */
    protected function _preSave()
    {
        if ($this->isChanged('match_callback_class') || $this->isChanged('match_callback_method')) {
            $class = $this->get('match_callback_class');
            $method = $this->get('match_callback_method');

            if (!$class || !$method) {
                $this->set('match_callback_class', '');
                $this->set('match_callback_method', '');
            } else
                if (!XenForo_Application::autoload($class) || !method_exists($class, $method)) {
                    $this->error(new XenForo_Phrase('please_enter_valid_callback_method'), 'callback_method');
                }
        }

        if ($this->isChanged('field_choices_callback_class') || $this->isChanged('field_choices_callback_method')) {
            $class = $this->get('field_choices_callback_class');
            $method = $this->get('field_choices_callback_method');

            if (!$class || !$method) {
                $this->set('field_choices_callback_class', '');
                $this->set('field_choices_callback_method', '');
            } else
                if (!XenForo_Application::autoload($class) || !method_exists($class, $method)) {
                    $this->error(new XenForo_Phrase('please_enter_valid_callback_method'), 'callback_method');
                }
        }

        if ($this->isChanged('display_callback_class') || $this->isChanged('display_callback_method')) {
            $class = $this->get('display_callback_class');
            $method = $this->get('display_callback_method');

            if (!$class || !$method) {
                $this->set('display_callback_class', '');
                $this->set('display_callback_method', '');
            } else
                if (!XenForo_Application::autoload($class) || !method_exists($class, $method)) {
                    $this->error(new XenForo_Phrase('please_enter_valid_callback_method'), 'callback_method');
                }
        }

        if ($this->isChanged('field_callback_class') || $this->isChanged('field_callback_method')) {
            $class = $this->get('field_callback_class');
            $method = $this->get('field_callback_method');

            if (!$class || !$method) {
                $this->set('field_callback_class', '');
                $this->set('field_callback_method', '');
            } else
                if (!XenForo_Application::autoload($class) || !method_exists($class, $method)) {
                    $this->error(new XenForo_Phrase('please_enter_valid_callback_method'), 'callback_method');
                }
        }

        if ($this->isChanged('export_callback_class') || $this->isChanged('export_callback_method')) {
            $class = $this->get('export_callback_class');
            $method = $this->get('export_callback_method');

            if (!$class || !$method) {
                $this->set('export_callback_class', '');
                $this->set('export_callback_method', '');
            } else
                if (!XenForo_Application::autoload($class) || !method_exists($class, $method)) {
                    $this->error(new XenForo_Phrase('please_enter_valid_callback_method'), 'callback_method');
                }
        }

        if ($this->isUpdate() && $this->isChanged('field_type')) {
            $typeMap = $this->_getFieldModel()->getThreadFieldTypeMap();
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

    /**
     * Post-save handling.
     */
    protected function _postSave()
    {
        if (!$this->getOption(self::OPTION_MASS_UPDATE)) {
            $fieldId = $this->get('field_id');

            if ($this->isUpdate() && $this->isChanged('field_id')) {
                $this->_renameMasterPhrase($this->_getTitlePhraseName($this->getExisting('field_id')),
                    $this->_getTitlePhraseName($fieldId));

                $this->_renameMasterPhrase($this->_getDescriptionPhraseName($this->getExisting('field_id')),
                    $this->_getDescriptionPhraseName($fieldId));
            }

            $titlePhrase = $this->getExtraData(self::DATA_TITLE);
            if ($titlePhrase !== null) {
                $this->_insertOrUpdateMasterPhrase($this->_getTitlePhraseName($fieldId), $titlePhrase, '',
                    array(
                        'global_cache' => 1
                    ));
            }

            $descriptionPhrase = $this->getExtraData(self::DATA_DESCRIPTION);
            if ($descriptionPhrase !== null) {
                $this->_insertOrUpdateMasterPhrase($this->_getDescriptionPhraseName($fieldId), $descriptionPhrase);
            }

            if (is_array($this->_fieldChoices)) {
                $this->_deleteExistingChoicePhrases();

                foreach ($this->_fieldChoices as $choice => $text) {
                    $this->_insertOrUpdateMasterPhrase($this->_getChoicePhraseName($fieldId, $choice), $text, '',
                        array(
                            'global_cache' => 1
                        ));
                }
            }

            if ($this->isChanged('display_order') || $this->isChanged('field_group_id')) {
                $this->_getFieldModel()->rebuildThreadFieldMaterializedOrder();
            }

            $this->_rebuildThreadFieldCache();
        }
    }

    /**
     * Post-delete behaviors.
     */
    protected function _postDelete()
    {
        $fieldId = $this->get('field_id');

        $this->_deleteMasterPhrase($this->_getTitlePhraseName($fieldId));
        $this->_deleteMasterPhrase($this->_getDescriptionPhraseName($fieldId));
        $this->_deleteExistingChoicePhrases();

        $this->_db->delete('xf_thread_field_value', 'field_id = ' . $this->_db->quote($fieldId));
        // note the thread caches aren't rebuilt here; this shouldn't be an
    // issue as we don't enumerate them
    }

    /**
     * Gets the name of the title phrase for this field.
     *
     * @param string $id
     *
     * @return string
     */
    protected function _getTitlePhraseName($id)
    {
        return $this->_getFieldModel()->getThreadFieldTitlePhraseName($id);
    }

    /**
     * Gets the name of the description phrase for this field.
     *
     * @param string $id
     *
     * @return string
     */
    protected function _getDescriptionPhraseName($id)
    {
        return $this->_getFieldModel()->getThreadFieldDescriptionPhraseName($id);
    }

    /**
     * Gets the name of the choice phrase for a value in this field.
     *
     * @param string $fieldId
     * @param string $choice
     *
     * @return string
     */
    protected function _getChoicePhraseName($fieldId, $choice)
    {
        return $this->_getFieldModel()->getThreadFieldChoicePhraseName($fieldId, $choice);
    }

    protected function _rebuildThreadFieldCache()
    {
        return $this->_getFieldModel()->rebuildThreadFieldCache();
    } /* END Waindigo_CustomFields_DataWriter_ThreadField::_rebuildThreadFieldCache() */

    /**
     *
     * @return Waindigo_CustomFields_Model_ThreadField
     */
    protected function _getFieldModel()
    {
        return $this->getModelFromCache('Waindigo_CustomFields_Model_ThreadField');
    } /* END Waindigo_CustomFields_DataWriter_ThreadField::_getFieldModel() */
}
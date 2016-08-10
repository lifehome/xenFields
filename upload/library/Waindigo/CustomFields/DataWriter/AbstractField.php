<?php

/**
 * Data writer for custom fields.
 */
abstract class Waindigo_CustomFields_DataWriter_AbstractField extends XenForo_DataWriter
{

    /**
     * Gets the object that represents the definition of this type of field.
     *
     * @return Waindigo_CustomFields_Definition_Abstract
     */
    abstract public function getFieldDefinition();

    /**
     * Constant for extra data that holds the value for the phrase
     * that is the title of this field.
     *
     * This value is required on inserts.
     *
     * @var string
     */
    const DATA_TITLE = 'phraseTitle';

    const OPTION_MASS_UPDATE = 'massUpdate';

    /**
     * Constant for extra data that holds the value for the phrase
     * that is the description of this field.
     *
     * @var string
     */
    const DATA_DESCRIPTION = 'phraseDescription';

    /**
     * Title of the phrase that will be created when a call to set the
     * existing data fails (when the data doesn't exist).
     *
     * @var string
     */
    protected $_existingDataErrorPhrase = 'requested_field_not_found';

    /**
     * List of choices, if this is a choice field.
     * Interface to set field_choices properly.
     *
     * @var null array
     */
    protected $_fieldChoices = null;

    /**
     * Data about the field's definition.
     *
     * @var Waindigo_CustomFields_Definition_Abstract
     */
    protected $_fieldDefinition = null;

    /**
     * Constructor.
     *
     * @param constant Error handler. See {@link ERROR_EXCEPTION} and related.
     * @param array|null Dependency injector. Array keys available: db, cache.
     */
    public function __construct($errorHandler = self::ERROR_EXCEPTION, array $inject = null)
    {
        $this->_fieldDefinition = $this->getFieldDefinition();

        parent::__construct($errorHandler, $inject);
    }

    /**
     * Gets the fields that are defined for the table.
     * See parent for explanation.
     *
     * @return array
     */
    protected function _getCommonFields()
    {
        $structure = $this->_fieldDefinition->getFieldStructure();

        return array(
            $structure['table'] => array(
                'field_id' => array(
                    'type' => self::TYPE_STRING,
                    'required' => true,
                    'maxLength' => 25,
                    'verification' => array(
                        '$this',
                        '_verifyFieldId'
                    ),
                    'requiredError' => 'please_enter_valid_field_id'
                ), 
                'field_group_id' => array(
                    'type' => self::TYPE_UINT,
                    'default' => 0
                ), 
                'display_order' => array(
                    'type' => self::TYPE_UINT_FORCED,
                    'default' => 0
                ), 
                'materialized_order' => array(
                    'typpe' => self::TYPE_UINT_FORCED,
                    'default' => 0
                ), 
                'field_type' => array(
                    'type' => self::TYPE_STRING,
                    'default' => 'textbox',
                    'allowedValues' => array(
                        'textbox',
                        'textarea',
                        'select',
                        'radio',
                        'checkbox',
                        'multiselect',
                        'callback'
                    )
                ), 
                'field_choices' => array(
                    'type' => self::TYPE_SERIALIZED,
                    'default' => ''
                ), 
                'match_type' => array(
                    'type' => self::TYPE_STRING,
                    'default' => 'none',
                    'allowedValues' => array(
                        'none',
                        'number',
                        'alphanumeric',
                        'email',
                        'url',
                        'regex',
                        'callback'
                    )
                ), 
                'match_regex' => array(
                    'type' => self::TYPE_STRING,
                    'default' => '',
                    'maxLength' => 250
                ), 
                'match_callback_class' => array(
                    'type' => self::TYPE_STRING,
                    'default' => '',
                    'maxLength' => 75
                ), 
                'match_callback_method' => array(
                    'type' => self::TYPE_STRING,
                    'default' => '',
                    'maxLength' => 75
                ), 
                'max_length' => array(
                    'type' => self::TYPE_UINT,
                    'default' => 0
                ), 
                'display_template' => array(
                    'type' => self::TYPE_STRING,
                    'default' => ''
                ), 
                'display_callback_class' => array(
                    'type' => self::TYPE_STRING,
                    'default' => '',
                    'maxLength' => 75
                ), 
                'display_callback_method' => array(
                    'type' => self::TYPE_STRING,
                    'default' => '',
                    'maxLength' => 75
                ), 
                'allowed_user_group_ids' => array(
                    'type' => self::TYPE_UNKNOWN,
                    'default' => '',
                    'verification' => array(
                        '$this',
                        '_verifyAllowedUserGroupIds'
                    )
                ), 
                'addon_id' => array(
                    'type' => self::TYPE_STRING,
                    'default' => '',
                    'maxLength' => 25
                ), 
                'field_choices_callback_class' => array(
                    'type' => self::TYPE_STRING,
                    'default' => '',
                    'maxLength' => 75
                ), 
                'field_choices_callback_method' => array(
                    'type' => self::TYPE_STRING,
                    'default' => '',
                    'maxLength' => 75
                ), 
                'field_callback_class' => array(
                    'type' => self::TYPE_STRING,
                    'default' => '',
                    'maxLength' => 75
                ), 
                'field_callback_method' => array(
                    'type' => self::TYPE_STRING,
                    'default' => '',
                    'maxLength' => 75
                ), 
                'export_callback_class' => array(
                    'type' => self::TYPE_STRING,
                    'default' => '',
                    'maxLength' => 75
                ), 
                'export_callback_method' => array(
                    'type' => self::TYPE_STRING,
                    'default' => '',
                    'maxLength' => 75
                ), 
           )
        );
    }

    /**
     * Gets SQL condition to update the existing record.
     *
     * @return string
     */
    protected function _getUpdateCondition($tableName)
    {
        return 'field_id = ' . $this->_db->quote($this->getExisting('field_id'));
    }

    /**
     * Gets the default options for this data writer.
     */
    protected function _getDefaultOptions()
    {
        return array(
            self::OPTION_MASS_UPDATE => false
        );
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

    /**
     * Sets the choices for this field.
     *
     * @param array $choices [choice key] => text
     */
    public function setFieldChoices(array $choices)
    {
        foreach ($choices as $value => &$text) {
            if ($value === '') {
                unset($choices[$value]);
                continue;
            }

            $text = strval($text);

            if ($text === '') {
                $this->error(new XenForo_Phrase('please_enter_text_for_each_choice'), 'field_choices');
                return false;
            }

            if (preg_match('#[^a-z0-9_]#i', $value)) {
                $this->error(new XenForo_Phrase('please_enter_an_id_using_only_alphanumeric'), 'field_choices');
                return false;
            }

            if (strlen($value) > 25) {
                $this->error(
                    new XenForo_Phrase('please_enter_value_using_x_characters_or_fewer',
                        array(
                            'count' => 25
                        )));
                return false;
            }
        }

        $this->_fieldChoices = $choices;
        $this->set('field_choices', $choices);

        return true;
    }

    /**
     * Deletes all phrases for existing choices.
     */
    protected function _deleteExistingChoicePhrases()
    {
        $fieldId = $this->get('field_id');

        $existingChoices = $this->getExisting('field_choices');
        if ($existingChoices && $existingChoices = @unserialize($existingChoices)) {
            foreach ($existingChoices as $choice => $text) {
                $this->_deleteMasterPhrase($this->_getChoicePhraseName($fieldId, $choice));
            }
        }
    }
}
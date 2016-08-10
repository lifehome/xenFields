<?php

/**
* Data writer for resource field groups.
*/
class Waindigo_CustomFields_DataWriter_ResourceFieldGroup extends XenForo_DataWriter
{

    /**
     * Constant for extra data that holds the value for the phrase
     * that is the title of this field.
     *
     * This value is required on inserts.
     *
     * @var string
     */
    const DATA_TITLE = 'phraseTitle';

    /**
     * Title of the phrase that will be created when a call to set the
     * existing data fails (when the data doesn't exist).
     *
     * @var string
     */
    protected $_existingDataErrorPhrase = 'requested_field_group_not_found';

    /**
     * Gets the fields that are defined for the table.
     * See parent for explanation.
     *
     * @return array
     */
    protected function _getFields()
    {
        return array(
            'xf_resource_field_group' => array(
                'field_group_id' => array(
                    'type' => self::TYPE_UINT,
                    'autoIncrement' => true
                ), 
                'display_order' => array(
                    'type' => self::TYPE_UINT_FORCED,
                    'default' => 0
                ), 
            ), 
        );
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
        if (!$id = $this->_getExistingPrimaryKey($data, 'field_group_id')) {
            return false;
        }

        return array(
            'xf_resource_field_group' => $this->_getFieldModel()->getResourceFieldGroupById($id)
        );
    }

    /**
     * Gets SQL condition to update the existing record.
     *
     * @return string
     */
    protected function _getUpdateCondition($tableName)
    {
        return 'field_group_id = ' . $this->_db->quote($this->getExisting('field_group_id'));
    }

    protected function _preSave()
    {
        $titlePhrase = $this->getExtraData(self::DATA_TITLE);
        if ($titlePhrase !== null && strlen($titlePhrase) == 0) {
            $this->error(new XenForo_Phrase('please_enter_valid_title'), 'title');
        }
    }

    protected function _postSave()
    {
        $titlePhrase = $this->getExtraData(self::DATA_TITLE);
        if ($titlePhrase !== null) {
            $this->_insertOrUpdateMasterPhrase($this->_getTitlePhraseName($this->get('field_group_id')), $titlePhrase,
                '', array(
                    'global_cache' => 1
                ));
        }

        if ($this->isChanged('display_order')) {
            $this->_getFieldModel()->rebuildResourceFieldMaterializedOrder();
        }

        $this->_getFieldModel()->rebuildResourceFieldCache();
    }

    protected function _postDelete()
    {
        $fieldGroupId = $this->get('field_group_id');

        $this->_deleteMasterPhrase($this->_getTitlePhraseName($fieldGroupId));

        $this->_db->update('xf_resource_field', array(
            'field_group_id' => 0
        ), 'field_group_id = ' . $this->_db->quote($fieldGroupId));

        $this->_getFieldModel()->rebuildResourceFieldMaterializedOrder();
        $this->_getFieldModel()->rebuildResourceFieldCache();
    }

    /**
     * Gets the name of the title phrase for this field.
     *
     * @param integer $fieldId
     *
     * @return string
     */
    protected function _getTitlePhraseName($fieldGroupId)
    {
        return $this->_getFieldModel()->getResourceFieldGroupTitlePhraseName($fieldGroupId);
    }

    /**
     *
     * @return XenForo_Model_ResourceField
     */
    protected function _getFieldModel()
    {
        return $this->getModelFromCache('Waindigo_CustomFields_Model_ResourceField');
    }
}
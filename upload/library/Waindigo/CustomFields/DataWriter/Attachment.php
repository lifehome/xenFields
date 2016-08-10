<?php

/**
 * Data writer for custom field attachments
 */
class Waindigo_CustomFields_DataWriter_Attachment extends XenForo_DataWriter
{

    /**
     * Title of the phrase that will be created when a call to set the
     * existing data fails (when the data doesn't exist).
     *
     * @var string
     */
    protected $_existingDataErrorPhrase = 'requested_custom_field_attachment_not_found';

    /**
     * Gets the fields that are defined for the table.
     * See parent for explanation.
     *
     * @return array
     */
    protected function _getFields()
    {
        return array(
            'xf_custom_field_attachment' => array(
                'field_attachment_id' => array(
                    'type' => self::TYPE_UINT,
                    'autoIncrement' => true
                ),
                'field_id' => array(
                    'type' => self::TYPE_STRING,
                    'required' => true,
                    'maxLength' => 64
                ),
                'custom_field_type' => array(
                    'type' => self::TYPE_STRING,
                    'allowedValues' => array(
                        'user',
                        'thread',
                        'post',
                        'resource',
                        'social_forum'
                    ),
                    'default' => 'user'
                ),
                'content_id' => array(
                    'type' => self::TYPE_UINT,
                    'default' => 0
                ),
                'temp_hash' => array(
                    'type' => self::TYPE_STRING,
                    'default' => ''
                ),
                'unassociated' => array(
                    'type' => self::TYPE_BOOLEAN,
                    'default' => true
                ),
                'attach_count' => array(
                    'type' => self::TYPE_UINT,
                    'default' => 0
                )
            )
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
        if (!$id = $this->_getExistingPrimaryKey($data)) {
            return false;
        }

        return array(
            'xf_custom_field_attachment' => $this->_getFieldAttachmentModel()->getFieldAttachmentById($id)
        );
    }

    /**
     * Gets SQL condition to update the existing record.
     *
     * @return string
     */
    protected function _getUpdateCondition($tableName)
    {
        return 'field_attachment_id = ' . $this->_db->quote($this->getExisting('field_attachment_id'));
    }

    /**
     *
     * @return Waindigo_CustomFields_Model_Attachment
     */
    protected function _getFieldAttachmentModel()
    {
        return $this->getModelFromCache('Waindigo_CustomFields_Model_Attachment');
    }
}
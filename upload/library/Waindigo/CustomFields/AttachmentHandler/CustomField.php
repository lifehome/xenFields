<?php

/**
 * Attachment handler for custom fields.
 */
class Waindigo_CustomFields_AttachmentHandler_CustomField extends XenForo_AttachmentHandler_Abstract
{

    /**
     * Custom field attachment model object.
     *
     * @var Waindigo_CustomFields_Model_Attachment
     */
    protected $_fieldAttachmentModel = null;

    /**
     * Key of primary content in content data array.
     *
     * @var string
     */
    protected $_contentIdKey = 'field_attachment_id';

    /**
     * Route to get to custom field content.
     *
     * @var string
     */
    protected $_contentRoute = 'custom-field-content';

    /**
     * Name of the phrase that describes the custom field content type.
     *
     * @var string
     */
    protected $_contentTypePhraseKey = 'custom_field';

    /**
     * Determines if attachments and be uploaded and managed in this context.
     *
     * @see XenForo_AttachmentHandler_Abstract::_canUploadAndManageAttachments()
     */
    protected function _canUploadAndManageAttachments(array $contentData, array $viewingUser)
    {
        // TODO
        return true;
    }

    /**
     * Determines if the specified attachment can be viewed.
     */
    protected function _canViewAttachment(array $attachment, array $viewingUser)
    {
        // TODO
        return true;
    }

    public function getAttachmentConstraints()
    {
        $attachmentConstraints = parent::getAttachmentConstraints();

        return array_merge($attachmentConstraints, $this->_getFieldAttachmentModel()->getFileConstraints());
    }

    /**
     * Code to run after deleting an associated attachment.
     */
    public function attachmentPostDelete(array $attachment, Zend_Db_Adapter_Abstract $db)
    {
        $db->query(
            '
			UPDATE xf_custom_field_attachment
			SET attach_count = IF(attach_count > 0, attach_count - 1, 0)
			WHERE field_attachment_id = ?
		', $attachment['content_id']);

        $db->query(
            '
            DELETE FROM xf_custom_field_attachment
            WHERE field_attachment_id = ? AND attach_count = 0
        ', $attachment['content_id']);
    }

    /**
     *
     * @return Waindigo_CustomFields_Model_Attachment
     */
    protected function _getFieldAttachmentModel()
    {
        if ($this->_fieldAttachmentModel === null) {
            $this->_fieldAttachmentModel = XenForo_Model::create('Waindigo_CustomFields_Model_Attachment');
        }

        return $this->_fieldAttachmentModel;
    }
}
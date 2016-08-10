<?php

class Waindigo_CustomFields_Model_Attachment extends XenForo_Model
{

    protected static $_fieldAttachmentIdCache = array();

    /**
     * Gets a custom field attachment by ID.
     *
     * @param string $fieldAttachmentId
     *
     * @return array false
     */
    public function getFieldAttachmentById($fieldAttachmentId)
    {
        if (!$fieldAttachmentId) {
            return array();
        }

        return $this->_getDb()->fetchRow(
            '
            SELECT *
            FROM xf_custom_field_attachment
            WHERE field_attachment_id = ?
        ', $fieldAttachmentId);
    }

    public function cacheFieldAttachmentId($contentType, $fieldAttachmentId)
    {
        self::$_fieldAttachmentIdCache[$contentType][] = $fieldAttachmentId;
    }

    public function getCachedFieldAttachmentIdsForContentType($contentType)
    {
        if (isset(self::$_fieldAttachmentIdCache[$contentType])) {
            return self::$_fieldAttachmentIdCache[$contentType];
        }
        return array();
    }

    public function getFileParams(array $version, array $contentData, array $viewingUser = null)
    {
        return array(
            'hash' => md5(uniqid('', true)), 
            'content_type' => 'custom_field', 
            'content_data' => $contentData, 
        );
    }

    public function getFileConstraints()
    {
        return array(
            'count' => 1, 
        );
    }

    public function associateAttachments($contentId, $customFieldType)
    {
        $fieldAttachmentIds = $this->getCachedFieldAttachmentIdsForContentType(
            $customFieldType);
        
        foreach ($fieldAttachmentIds as $fieldAttachmentId) {
            $dw = XenForo_DataWriter::create('Waindigo_CustomFields_DataWriter_Attachment',
                XenForo_DataWriter::ERROR_SILENT);
            $dw->setExistingData($fieldAttachmentId);
            if ($dw->get('content_id') && $dw->get('content_id') != $contentId) {
                continue;
            }
            if ($dw->get('custom_field_type') != $customFieldType) {
                continue;
            }
            $rows = 0;
            if ($dw->get('temp_hash')) {
                $rows = XenForo_Application::get('db')->update('xf_attachment',
                    array(
                        'content_type' => 'custom_field',
                        'content_id' => $dw->get('field_attachment_id'),
                        'temp_hash' => '',
                        'unassociated' => 0
                    ), 'temp_hash = ' . XenForo_Application::get('db')->quote($dw->get('temp_hash')));
            } elseif (!$dw->get('attach_count')) {
                $dw->delete();
                continue;
            }
            $dw->bulkSet(
                array(
                    'custom_field_type' => $customFieldType,
                    'content_id' => $contentId,
                    'temp_hash' => '',
                    'unassociated' => 0,
                    'attach_count' => $dw->get('attach_count') + $rows
                ));
            $dw->save();
        }
    }
}
<?php

/**
 * Exports a user field as XML.
 */
class Waindigo_CustomFields_ViewAdmin_UserField_Export extends XenForo_ViewAdmin_Base
{

    public function renderXml()
    {
        $this->setDownloadFileName('field-' . $this->_params['field']['field_id'] . '.xml');
        return $this->_params['xml']->saveXml();
    }
}
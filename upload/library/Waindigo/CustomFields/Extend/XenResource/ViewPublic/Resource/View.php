<?php

/**
 *
 * @see XenResource_ViewPublic_Resource_View
 */
class Waindigo_CustomFields_Extend_XenResource_ViewPublic_Resource_View extends XFCP_Waindigo_CustomFields_Extend_XenResource_ViewPublic_Resource_View
{

    /**
     *
     * @see XenResource_ViewPublic_Resource_View::renderHtml()
     */
    public function renderHtml()
    {
        if (method_exists(get_parent_class(), 'renderHtml')) {
            parent::renderHtml();
        }

        if (isset($this->_params['customFieldsGrouped'])) {
            foreach ($this->_params['customFieldsGrouped'] as &$fields) {
                $fields = Waindigo_CustomFields_ViewPublic_Helper_Resource::addResourceFieldsValueHtml($this, $fields);
            }
        }
    }
}
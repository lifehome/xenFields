<?php

/**
 *
 * @see XenForo_ControllerPublic_Search
 */
class Waindigo_CustomFields_Extend_XenForo_ControllerPublic_Search extends XFCP_Waindigo_CustomFields_Extend_XenForo_ControllerPublic_Search
{

    protected function _handleInputType(array &$input = array())
    {
        $type = parent::_handleInputType($input);

        if (!$type) {
            $customFields = $this->_input->filterSingle('custom_fields', XenForo_Input::ARRAY_SIMPLE);

            foreach ($customFields as $customField) {
                if ($customField) {
                    $type = 'post';
                    break;
                }
            }
        }

        return $type;
    }
}
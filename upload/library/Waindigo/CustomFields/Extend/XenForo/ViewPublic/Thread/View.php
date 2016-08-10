<?php

/**
 *
 * @see XenForo_ViewPublic_Forum_View
 */
class Waindigo_CustomFields_Extend_XenForo_ViewPublic_Thread_View extends XFCP_Waindigo_CustomFields_Extend_XenForo_ViewPublic_Thread_View
{

    /**
     *
     * @see XenForo_ViewPublic_Forum_View::renderHtml()
     */
    public function renderHtml()
    {
        parent::renderHtml();

        if (isset($this->_params['thread']['customFields'])) {
            foreach ($this->_params['thread']['customFields'] as $groupId => $threadFields) {
                $this->_params['thread']['customFields'][$groupId]['fields'] = Waindigo_CustomFields_ViewPublic_Helper_Thread::addThreadFieldsValueHtml(
                    $this, $this->_params['thread']['customFields'][$groupId]['fields']);
            }
        }
    }
}
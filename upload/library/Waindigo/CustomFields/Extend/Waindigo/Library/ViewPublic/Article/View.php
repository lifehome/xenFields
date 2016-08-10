<?php

/**
 *
 * @see XenForo_ViewPublic_Forum_View
 */
class Waindigo_CustomFields_Extend_Waindigo_Library_ViewPublic_Article_View extends XFCP_Waindigo_CustomFields_Extend_Waindigo_Library_ViewPublic_Article_View
{

    /**
     *
     * @see XenForo_ViewPublic_Forum_View::renderHtml()
     */
    public function renderHtml()
    {
        parent::renderHtml();

        if (isset($this->_params['article']['customFields'])) {
            foreach ($this->_params['article']['customFields'] as $groupId => $threadFields) {
                $this->_params['article']['customFields'][$groupId]['fields'] = Waindigo_CustomFields_ViewPublic_Helper_Thread::addThreadFieldsValueHtml(
                    $this, $this->_params['article']['customFields'][$groupId]['fields']);
            }
        }
    }
}
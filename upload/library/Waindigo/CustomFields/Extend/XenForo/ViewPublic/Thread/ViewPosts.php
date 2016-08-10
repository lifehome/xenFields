<?php

/**
 *
 * @see XenForo_ViewPublic_Forum_ViewPosts
 */
class Waindigo_CustomFields_Extend_XenForo_ViewPublic_Thread_ViewPosts extends XFCP_Waindigo_CustomFields_Extend_XenForo_ViewPublic_Thread_ViewPosts
{

    /**
     *
     * @see XenForo_ViewPublic_Forum_ViewPosts::renderJson()
     */
    public function renderJson()
    {
        if (isset($this->_params['thread']['customFields'])) {
            foreach ($this->_params['thread']['customFields'] as $groupId => $threadFields) {
                $this->_params['thread']['customFields'][$groupId]['fields'] = Waindigo_CustomFields_ViewPublic_Helper_Thread::addThreadFieldsValueHtml(
                    $this, $this->_params['thread']['customFields'][$groupId]['fields']);
            }
        }

        return parent::renderJson();
    }
}
<?php

/**
 *
 * @see XenForo_ViewPublic_Forum_View
 */
class Waindigo_CustomFields_Extend_XenForo_ViewPublic_Forum_View extends XFCP_Waindigo_CustomFields_Extend_XenForo_ViewPublic_Forum_View
{

    /**
     *
     * @see XenForo_ViewPublic_Forum_View::renderHtml()
     */
    public function renderHtml()
    {
        if (method_exists(get_parent_class(), 'renderHtml')) {
            parent::renderHtml();
        }

        if (isset($this->_params['threads'])) {
            foreach ($this->_params['threads'] as $threadId => &$thread) {
                if (isset($thread['customThreadFields'])) {
                    foreach ($thread['customThreadFields'] as $groupId => $threadFields) {
                        if (isset($thread['customThreadFields'][$groupId]['fields'])) {
                            $thread['customThreadFields'][$groupId]['fields'] = Waindigo_CustomFields_ViewPublic_Helper_Thread::addThreadFieldsValueHtml(
                                $this, $thread['customThreadFields'][$groupId]['fields']);
                        }
                    }
                }
            }
        }

        if (isset($this->_params['stickyThreads'])) {
            foreach ($this->_params['stickyThreads'] as $threadId => &$thread) {
                if (isset($thread['customThreadFields'])) {
                    foreach ($thread['customThreadFields'] as $groupId => $threadFields) {
                        if (isset($thread['customThreadFields'][$groupId]['fields'])) {
                            $thread['customThreadFields'][$groupId]['fields'] = Waindigo_CustomFields_ViewPublic_Helper_Thread::addThreadFieldsValueHtml(
                                $this, $thread['customThreadFields'][$groupId]['fields']);
                        }
                    }
                }
            }
        }
    }
}
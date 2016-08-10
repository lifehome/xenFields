<?php

class Waindigo_CustomFields_Listener_TemplateCreate extends Waindigo_Listener_TemplateCreate
{

    protected function _getTemplates()
    {
        return array(
            'forum_view',
            'thread_view',
            'thread_edit',
            'waindigo_social_forum_container_socialgroups'
        );
    }

    public static function templateCreate(&$templateName, array &$params, XenForo_Template_Abstract $template)
    {
        $templateCreate = new Waindigo_CustomFields_Listener_TemplateCreate($templateName, $params, $template);
        list($templateName, $params) = $templateCreate->run();
    }

    protected function _forumView()
    {
        $this->_preloadTemplate('waindigo_thread_list_item_socialgroups');
    }

    protected function _threadView()
    {
        if (isset($this->_params['forum']['node_id'])) {
            $this->_preloadTemplates(
                array(
                    '_header_node.' . $this->_params['forum']['node_id'],
                    '_footer_node.' . $this->_params['forum']['node_id'],
                    '_header_post_node.' . $this->_params['forum']['node_id'],
                    '_footer_post_node.' . $this->_params['forum']['node_id']
                ));
        }
        $this->_preloadTemplates(
            array(
                'waindigo_quick_reply_prepend_customfields',
                'waindigo_header_node_customfields'
            ));
    }

    protected function _waindigoSocialForumContainerSocialgroups()
    {
        if (isset($this->_params['socialForum']['node_id'])) {
            $this->_preloadTemplates(
                array(
                    '_header_social_forum_node.' . $this->_params['socialForum']['node_id'],
                    '_footer_social_forum_node.' . $this->_params['socialForum']['node_id']
                ));
        }
        $this->_preloadTemplates(
            array(
                'waindigo_sidebar_customfields',
                'custom_field_view'
            ));
    }

    protected function _threadEdit()
    {
        $this->_template->addRequiredExternal('css', 'waindigo_thread_edit_scrollbar_customfields');
    }
}
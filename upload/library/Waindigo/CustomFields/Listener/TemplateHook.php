<?php

class Waindigo_CustomFields_Listener_TemplateHook extends Waindigo_Listener_TemplateHook
{

    protected function _getHooks()
    {
        return array(
            'admin_forum_edit_tabs',
            'waindigo_admin_library_edit_tabs_library',
            'admin_forum_edit_panes',
            'waindigo_admin_library_edit_panes_library',
            'admin_resource_category_edit_tabs',
            'admin_resource_category_edit_panes',
            'thread_create_fields_extra',
            'waindigo_article_create_fields_extra_library',
            'message_content',
            'waindigo_article_message_content_library',
            'resource_view_sidebar_resource_info',
            'resource_view_sidebar_below_info',
            'waindigo_social_forum_fields_extra_socialgroups',
            'waindigo_social_forum_description_socialgroups',
            'waindigo_social_forum_sidebar_info_socialgroups',
            'waindigo_social_forum_sidebar_below_info_socialgroups',
            'thread_list_threads',
            'thread_list_stickies',
            'thread_view_tools_links'
        );
    }

    public static function templateHook($hookName, &$contents, array $hookParams, XenForo_Template_Abstract $template)
    {
        $templateHook = new Waindigo_CustomFields_Listener_TemplateHook($hookName, $contents, $hookParams, $template);
        $contents = $templateHook->run();
    }

    protected function _adminForumEditTabs()
    {
        $this->_appendTemplate('waindigo_forum_edit_tabs_customfields');

        if ($this->_template->getTemplateName() == 'waindigo_social_category_edit_socialgroups') {
            $this->_appendTemplate('waindigo_social_category_edit_tabs_customfields');
        }
    }

    protected function _waindigoAdminLibraryEditTabsLibrary()
    {
        $this->_adminForumEditTabs();
    }

    protected function _adminForumEditPanes()
    {
        $this->_appendTemplate('waindigo_forum_edit_panes_customfields');

        $viewParams = $this->_fetchViewParams();
        $customThreadFields = $viewParams['customThreadFields'];
        foreach ($customThreadFields as $field) {
            if ($field['field_type'] == 'callback') {
                $field['node_id'] = $viewParams['forum']['node_id'];
                $field['validator_name'] = 'custom_field_' . $field['field_id'];
                $field['name'] = 'custom_fields[' . $field['field_id'] . ']';
                $this->_appendAtCodeSnippet(
                    '<input type="hidden" name="custom_fields_shown[]" value="' . $field['field_id'] . '" />',
                    call_user_func_array(
                        array(
                            $field['field_callback_class'],
                            $field['field_callback_method']
                        ),
                        array(
                            $this->_template,
                            $field
                        ))->render());
            }
        }

        if ($this->_template->getTemplateName() == 'waindigo_social_category_edit_socialgroups') {
            $this->_appendTemplate('waindigo_social_category_edit_panes_customfields');

            $customSocialForumFields = $viewParams['customSocialForumFields'];
            foreach ($customSocialForumFields as $field) {
                if ($field['field_type'] == 'callback') {
                    $field['node_id'] = $viewParams['forum']['node_id'];
                    $field['validator_name'] = 'custom_social_forum_field_' . $field['field_id'];
                    $field['name'] = 'custom_social_forum_fields[' . $field['field_id'] . ']';
                    $this->_appendAtCodeSnippet(
                        '<input type="hidden" name="custom_social_forum_fields_shown[]" value="' . $field['field_id'] .
                             '" />',
                            call_user_func_array(
                                array(
                                    $field['field_callback_class'],
                                    $field['field_callback_method']
                                ),
                                array(
                                    $this->_template,
                                    $field
                                ))->render());
                }
            }
        }
    }

    protected function _waindigoAdminLibraryEditPanesLibrary()
    {
        $this->_adminForumEditPanes();
    }

    protected function _adminResourceCategoryEditTabs()
    {
        $this->_appendTemplate('waindigo_resource_category_edit_tabs_customfields');
    }

    protected function _adminResourceCategoryEditPanes()
    {
        $this->_appendTemplate('waindigo_resource_category_edit_panes_customfields');

        $viewParams = $this->_fetchViewParams();
        $customFields = $viewParams['customFields'];
        foreach ($customFields as $field) {
            if ($field['field_type'] == 'callback') {
                $field['node_id'] = $viewParams['forum']['node_id'];
                $field['validator_name'] = 'custom_field_' . $field['field_id'];
                $field['name'] = 'custom_fields[' . $field['field_id'] . ']';
                $this->_appendAtCodeSnippet(
                    '<input type="hidden" name="custom_fields_shown[]" value="' . $field['field_id'] . '" />',
                    call_user_func_array(
                        array(
                            $field['field_callback_class'],
                            $field['field_callback_method']
                        ),
                        array(
                            $this->_template,
                            $field
                        ))->render());
            }
        }
    }

    protected function _threadCreateFieldsExtra()
    {
        $viewParams = $this->_fetchViewParams();
        $customThreadFields = $viewParams['customThreadFields'];
        if ($customThreadFields) {
            foreach ($customThreadFields as $threadFieldGroup) {
                $append = '';
                foreach ($threadFieldGroup['fields'] as $field) {
                    if ($field['below_title_on_create']) {
                        continue;
                    }
                    if ($field['field_type'] == 'callback') {
                        $field['validator_name'] = 'custom_field_' . $field['field_id'];
                        $field['name'] = 'custom_fields[' . $field['field_id'] . ']';
                        $field['custom_field_type'] = 'thread';
                        $append .= call_user_func_array(
                            array(
                                $field['field_callback_class'],
                                $field['field_callback_method']
                            ),
                            array(
                                $this->_template,
                                $field
                            ))->render() . '<input type="hidden" name="custom_fields_shown[]" value="' .
                             $field['field_id'] . '" />';
                    } else {
                        $viewParams['field'] = $field;
                        $append .= $this->_render('custom_field_edit', $viewParams);
                    }
                }
                if ($append) {
                    $append = '<h3 class="textHeading">' .
                         (isset($threadFieldGroup['title']) ? $threadFieldGroup['title'] : '') . '</h3>' . $append;
                    $this->_append($append);
                }
            }
        }
    }

    protected function _waindigoArticleCreateFieldsExtraLibrary()
    {
        $this->_threadCreateFieldsExtra();
    }

    protected function _anyMessageContent($viewParams = null)
    {
        if (is_null($viewParams)) {
            $viewParams = $this->_fetchViewParams();
        }
        if (!empty($viewParams['thread']['custom_fields'])) {
            $viewParams['customFields'] = unserialize($viewParams['thread']['custom_fields']);
        }
        if (isset($viewParams['forum'])) {
            $forum = $viewParams['forum'];
            if ($viewParams['thread']['first_post_id'] == $viewParams['message']['post_id']) {
                $pattern = '#<article>#';
                $replacement = $this->_escapeDollars(
                    (!empty($viewParams['customFields']) ? $this->_render('waindigo_header_node_customfields',
                        $viewParams) : '') . $this->_render('_header_node.' . $forum['node_id'], $viewParams)) . '${0}';
                $this->_patternReplace($pattern, $replacement);
                $pattern = '#</article>#';
                $replacement = '${0}' .
                     $this->_escapeDollars(
                         (!empty($viewParams['customFields']) ? $this->_render('waindigo_footer_node_customfields',
                        $viewParams) : '') . $this->_render('_footer_node.' . $forum['node_id'], $viewParams));
                $this->_patternReplace($pattern, $replacement);
            }
        }
    }

    protected function _messageContent()
    {
        $this->_anyMessageContent();
    }

    protected function _waindigoArticleMessageContentLibrary()
    {
        $viewParams = $this->_fetchViewParams();
        $viewParams['forum'] = $viewParams['library'];
        $viewParams['thread'] = $viewParams['article'];
        $viewParams['thread']['first_post_id'] = $viewParams['thread']['first_article_page_id'];
        $viewParams['message']['post_id'] = $viewParams['message']['article_page_id'];
        $this->_anyMessageContent($viewParams);
    }

    protected function _resourceViewSidebarResourceInfo()
    {
        $viewParams = $this->_fetchViewParams();
        if (isset($viewParams['customFieldsGrouped'][0]) && !empty($viewParams['customFieldsGrouped'][0])) {
            $pattern = '#(?:<dl[^>]*>.*?</dl>\s*)+#s';
            $replacement = '${0}';
            foreach ($viewParams['customFieldsGrouped'][0] as $fieldId => $field) {
                $viewParams['field'] = $field;
                $replacement .= $this->_escapeDollars($this->_render('custom_field_view', $viewParams));
            }
            $this->_contents = preg_replace($pattern, $replacement, $this->_contents, 1);
        }
    }

    protected function _resourceViewSidebarBelowInfo()
    {
        $viewParams = $this->_fetchViewParams();
        if (isset($viewParams['customFieldsGrouped'])) {
            unset($viewParams['customFieldsGrouped'][0]);
            if (!empty($viewParams['customFieldsGrouped'])) {
                foreach ($viewParams['customFieldsGrouped'] as $fieldGroupId => $fields) {
                    /* @var $resourceFieldModel Waindigo_CustomFields_Model_ResourceField */
                    $resourceFieldModel = $this->getModelFromCache('Waindigo_CustomFields_Model_ResourceField');
                    $viewParams['title'] = new XenForo_Phrase(
                        $resourceFieldModel->getResourceFieldGroupTitlePhraseName($fieldGroupId));
                    $viewParams['fields'] = $fields;
                    $this->_appendTemplate('waindigo_sidebar_customfields', $viewParams);
                }
            }
        }
    }

    protected function _waindigoSocialForumFieldsExtraSocialgroups()
    {
        $viewParams = $this->_fetchViewParams();
        $customFields = $viewParams['customFields'];
        if ($customFields) {
            foreach ($customFields as $fieldGroup) {
                $this->_append('<h3 class="textHeading">' . $fieldGroup['title'] . '</h3>');
                foreach ($fieldGroup['fields'] as $field) {
                    if ($field['field_type'] == 'callback') {
                        $field['validator_name'] = 'custom_field_' . $field['field_id'];
                        $field['name'] = 'custom_fields[' . $field['field_id'] . ']';
                        $field['custom_field_type'] = 'social_forum';
                        $this->_append(
                            call_user_func_array(
                                array(
                                    $field['field_callback_class'],
                                    $field['field_callback_method']
                                ),
                                array(
                                    $this->_template,
                                    $field
                                ))->render() . '<input type="hidden" name="custom_fields_shown[]" value="' .
                                 $field['field_id'] . '" />');
                    } else {
                        $viewParams['field'] = $field;
                        $this->_appendTemplate('custom_field_edit', $viewParams);
                    }
                }
            }
        }
    }

    protected function _waindigoSocialForumDescriptionSocialgroups()
    {
        $viewParams = $this->_fetchViewParams();
        if (isset($viewParams['socialForum']['custom_social_forum_fields']) &&
             $viewParams['socialForum']['custom_social_forum_fields']) {
            $viewParams['customFields'] = unserialize($viewParams['socialForum']['custom_social_forum_fields']);
        }
        $socialForum = $viewParams['socialForum'];
        $this->_prependTemplate('_header_social_forum_node.' . $socialForum['node_id'], $viewParams);
        $this->_appendTemplate('_footer_social_forum_node.' . $socialForum['node_id'], $viewParams);
    }

    protected function _waindigoSocialForumSidebarInfoSocialgroups()
    {
        $viewParams = $this->_fetchViewParams();
        if (isset($viewParams['customFieldsGrouped'][0]) && !empty($viewParams['customFieldsGrouped'][0])) {
            $pattern = '#(?:<dl[^>]*>.*?</dl>\s*)+#s';
            $replacement = '${0}';
            foreach ($viewParams['customFieldsGrouped'][0] as $fieldId => $field) {
                $viewParams['field'] = $field;
                $replacement .= $this->_escapeDollars($this->_render('custom_field_view', $viewParams));
            }
            $this->_contents = preg_replace($pattern, $replacement, $this->_contents, 1);
        }
    }

    protected function _waindigoSocialForumSidebarBelowInfoSocialgroups()
    {
        $viewParams = $this->_fetchViewParams();
        if (isset($viewParams['customFieldsGrouped'])) {
            unset($viewParams['customFieldsGrouped'][0]);
            if (!empty($viewParams['customFieldsGrouped'])) {
                foreach ($viewParams['customFieldsGrouped'] as $fieldGroupId => $fields) {
                    /* @var $socialForumFieldModel Waindigo_CustomFields_Model_SocialForumField */
                    $socialForumFieldModel = $this->getModelFromCache('Waindigo_CustomFields_Model_SocialForumField');
                    $viewParams['title'] = new XenForo_Phrase(
                        $socialForumFieldModel->getSocialForumFieldGroupTitlePhraseName($fieldGroupId));
                    $viewParams['fields'] = $fields;
                    $this->_appendTemplate('waindigo_sidebar_customfields', $viewParams);
                }
            }
        }
    }

    protected function _threadListThreads()
    {
        $this->_threadListAnyThreads('threads');
    }

    protected function _threadListStickies()
    {
        $this->_threadListAnyThreads('stickyThreads');
    }

    protected function _threadListItem($viewParams = null)
    {
        if (!$viewParams) {
            $viewParams = $this->_fetchViewParams();
        }
        if ($viewParams['thread']['discussion_state'] != 'deleted' &&
             $viewParams['thread']['discussion_type'] != 'redirect') {
            $pattern = '#<li id="thread-' . $viewParams['thread']['thread_id'] . '".*<div class="secondRow">#Us';
            $this->_appendTemplateAtPattern($pattern, 'waindigo_thread_list_item_customfields', $viewParams);
        }
    }

    protected function _threadListAnyThreads($threadType)
    {
        $viewParams = $this->_fetchViewParams();
        if (!isset($viewParams[$threadType])) {
            return;
        }
        foreach ($viewParams[$threadType] as $viewParams['thread']) {
            $this->_threadListItem($viewParams);
        }
    }

    protected function _threadViewToolsLinks()
    {
        $this->_appendTemplate('waindigo_thread_view_tools_links_customfields');
    }
}
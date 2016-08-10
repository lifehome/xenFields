<?php

class Waindigo_CustomFields_Listener_TemplatePostRender extends Waindigo_Listener_TemplatePostRender
{

    protected function _getTemplates()
    {
        return array(
            'resource_add',
            'thread_create',
            'waindigo_article_create_library',
            'thread_edit',
            'waindigo_article_edit_inline_library',
            'user_field_edit',
            'thread_field_edit',
            'resource_field_edit',
            'waindigo_resource_field_edit_customfields',
            'social_forum_field_edit',
            'account_preferences',
            'account_contact_details',
            'account_personal_details',
            'waindigo_account_user_field_category_userfieldcats',
            'register_form',
            'resource_category_edit',
            'resource_description',
            'waindigo_thread_edit_custom_fields_customfields'
        );
    }

    public static function templatePostRender($templateName, &$content, array &$containerData,
        XenForo_Template_Abstract $template)
    {
        $templatePostRender = new Waindigo_CustomFields_Listener_TemplatePostRender($templateName, $content,
            $containerData, $template);
        list($content, $containerData) = $templatePostRender->run();
    }

    protected function _resourceAdd()
    {
        $addOns = XenForo_Application::get('addOns');

        if ($addOns['XenResource'] < 1010000) {
            $codeSnippet = '<dl class="ctrlUnit submitUnit">';
            $viewParams = $this->_fetchViewParams();
            $customFields = $viewParams['customFields'];
            if ($customFields) {
                foreach ($customFields as $fieldGroup) {
                    $this->_appendAtCodeSnippet($codeSnippet,
                        '<h3 class="textHeading">' . (isset($fieldGroup['title']) ? $fieldGroup['title'] : '') . '</h3>',
                        $null, false);
                    foreach ($fieldGroup['fields'] as $field) {
                        if ($field['field_type'] == 'callback') {
                            $field['validator_name'] = 'custom_field_' . $field['field_id'];
                            $field['name'] = 'custom_fields[' . $field['field_id'] . ']';
                            $field['custom_field_type'] = 'resource';
                            $this->_appendAtCodeSnippet($codeSnippet,
                                call_user_func_array(
                                    array(
                                        $field['field_callback_class'],
                                        $field['field_callback_method']
                                    ),
                                    array(
                                        $this->_template,
                                        $field
                                    ))->render() . '<input type="hidden" name="custom_fields_shown[]" value="' .
                                     $field['field_id'] . '" />', $null, false);
                        } else {
                            $viewParams['field'] = $field;
                            $this->_appendTemplateAtCodeSnippet($codeSnippet, 'custom_field_edit', $viewParams, $null,
                                false);
                        }
                    }
                }
            }
        } else {
            $viewParams = $this->_fetchViewParams();
            $customFields = $viewParams['customFields'];
            foreach ($customFields as $displayGroup => $fields) {
                foreach ($fields as $field) {
                    $field['custom_field_type'] = 'resource';
                    $this->_replaceCallbackField($field);
                }
            }
        }
    }

    protected function _threadCreate()
    {
        $viewParams = $this->_fetchViewParams();
        $customThreadFields = $viewParams['customThreadFields'];
        if ($customThreadFields) {
            $customThreadFields = array_reverse($customThreadFields);
            foreach ($customThreadFields as $threadFieldGroup) {
                $pattern = '#<dl class="ctrlUnit fullWidth surplusLabel">\s*<dt><label for="ctrl_title_thread_create">.*</dl>#Us';
                $replace = '';
                foreach ($threadFieldGroup['fields'] as $field) {
                    if (!$field['below_title_on_create']) {
                        continue;
                    }
                    if ($field['field_type'] == 'callback') {
                        $field['validator_name'] = 'custom_field_' . $field['field_id'];
                        $field['name'] = 'custom_fields[' . $field['field_id'] . ']';
                        $field['custom_field_type'] = 'thread';
                        $replace .= call_user_func_array(
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
                        $replace .= $this->_render('custom_field_edit', $viewParams);
                    }
                }
                if ($replace) {
                    $replace = '${0}' .
                         $this->_escapeDollars(
                            '<h3 class="textHeading">' .
                             (isset($threadFieldGroup['title']) ? $threadFieldGroup['title'] : '') . '</h3>' . $replace);
                    $this->_patternReplace($pattern, $replace);
                }
            }
        }
    }

    protected function _waindigoArticleCreateLibrary()
    {
        $this->_threadCreate();
    }

    protected function _threadEdit()
    {
        $codeSnippet = '<dl class="ctrlUnit submitUnit">';
        $viewParams = $this->_fetchViewParams();
        $customThreadFields = $viewParams['customThreadFields'];
        if ($customThreadFields) {
            foreach ($customThreadFields as $fieldGroup) {
                $this->_appendAtCodeSnippet($codeSnippet,
                    '<h3 class="textHeading">' . (isset($fieldGroup['title']) ? $fieldGroup['title'] : '') . '</h3>',
                    $null, false);
                foreach ($fieldGroup['fields'] as $field) {
                    if ($field['field_type'] == 'callback') {
                        $field['validator_name'] = 'custom_field_' . $field['field_id'];
                        $field['name'] = 'custom_fields[' . $field['field_id'] . ']';
                        $field['custom_field_type'] = 'thread';
                        $this->_appendAtCodeSnippet($codeSnippet,
                            call_user_func_array(
                                array(
                                    $field['field_callback_class'],
                                    $field['field_callback_method']
                                ),
                                array(
                                    $this->_template,
                                    $field
                                ))->render() . '<input type="hidden" name="custom_fields_shown[]" value="' .
                                 $field['field_id'] . '" />', $null, false);
                    } else {
                        $viewParams['field'] = $field;
                        $this->_appendTemplateAtCodeSnippet($codeSnippet, 'custom_field_edit', $viewParams, $null,
                            false);
                    }
                }
            }
        }
    }

    protected function _waindigoArticleEditInlineLibrary()
    {
        $this->_threadEdit();
    }

    protected function _fieldEdit()
    {
        $pattern = '#<li>\s*<label for="ctrl_field_type_callback">\s*<input type="radio" name="field_type" value="callback" id="ctrl_field_type_callback"[^>]*>[^<]*</label>\s*</li>#Us';
        $replacement = $this->_escapeDollars($this->_render('waindigo_field_edit_php_callback_customfields'));
        $this->_patternReplace($pattern, $replacement);

        $viewParams = $this->_fetchViewParams();
        $pattern = '#<ul class="FieldChoices">.*</ul>\s*<input[^>]*>\s*<p class="explain">[^<]*</p>#Us';
        preg_match($pattern, $this->_contents, $matches);
        if (isset($matches[0])) {
            $viewParams['contents'] = $matches[0];
            $replacement = $this->_escapeDollars($this->_render('waindigo_field_edit_choice_customfields', $viewParams));
            $this->_patternReplace($pattern, $replacement);
        }

        $pattern = '#</li>\s*</ul>\s*<dl class="ctrlUnit submitUnit">#';
        $replacement = $this->_escapeDollars($this->_render('waindigo_field_edit_panes_customfields')) . '${0}';
        $this->_patternReplace($pattern, $replacement);

        $pattern = '#<dl class="ctrlUnit">\s*<dt>\s*<label for="ctrl_display_template">.*</dl>#Us';
        $replacement = $this->_escapeDollars($this->_render('waindigo_field_edit_value_display_customfields'));
        $this->_patternReplace($pattern, $replacement);
    }

    protected function _userFieldEdit()
    {
        $this->_fieldEdit();

        if (XenForo_Application::$versionId > 1020000) {
            $addOns = XenForo_Application::get('addOns');
            $isUsInstalled = !empty($addOns['Waindigo_UserSearch']);
        } else {
            $isUsInstalled = $this->getAddOnById('Waindigo_UserSearch') ? true : false;
        }

        if ($isUsInstalled) {
            $pattern = '#(<!--<h3 class="textHeading">' . new XenForo_Phrase('general_options') . '</h3>-->.*)(</ul>\s*</dd>)#Us';
            $replacement = '${1}' . $this->_escapeDollars($this->_render('waindigo_user_field_edit_general_customfields')) . '${2}';
            $this->_patternReplace($pattern, $replacement);
        }
    }

    protected function _threadFieldEdit()
    {
        $this->_fieldEdit();
    }

    protected function _resourceFieldEdit()
    {
        $this->_fieldEdit();
    }

    protected function _waindigoResourceFieldEditCustomFields()
    {
        $this->_fieldEdit();
    }

    protected function _socialForumFieldEdit()
    {
        $this->_fieldEdit();
    }

    protected function _replaceCallbackField(array $field)
    {
        if ($field['field_type'] == 'callback') {
            $field['validator_name'] = 'custom_field_' . $field['field_id'];
            $field['name'] = 'custom_fields[' . $field['field_id'] . ']';
            $pattern = '#<dl class="ctrlUnit[^"]*">\s*<dt>\s*<label for="ctrl_' . $field['validator_name'] .
            '">.*</dl>#Us';
            $replacement = $this->_escapeDollars(
                call_user_func_array(
                    array(
                        $field['field_callback_class'],
                        $field['field_callback_method']
                    ),
                    array(
                        $this->_template,
                        $field
                    ))->render() . '<input type="hidden" name="custom_fields_shown[]" value="' . $field['field_id'] .
                '" />');
            $this->_patternReplace($pattern, $replacement);
        }
    }

    protected function _accountPreferences()
    {
        $viewParams = $this->_fetchViewParams();
        $customFields = $viewParams['customFields'];
        foreach ($customFields as $field) {
            $field['custom_field_type'] = 'user';
            $this->_replaceCallbackField($field);
        }
    }

    protected function _accountContactDetails()
    {
        $this->_accountPreferences();
    }

    protected function _accountPersonalDetails()
    {
        $this->_accountPreferences();
    }
    
    protected function _waindigoAccountUserFieldCategoryUserfieldcats()
    {
        $this->_accountPreferences();
    }

    protected function _registerForm()
    {
        $this->_accountPreferences();
    }

    protected function _resourceCategoryEdit()
    {
        $addOns = XenForo_Application::get('addOns');

        $viewParams = $this->_fetchViewParams();
        $pattern = '#(.*<form[^>]*>)(.*)(<dl class="ctrlUnit submitUnit">.*)#s';
        preg_match($pattern, $this->_contents, $matches);
        if (isset($matches[1])) {
            $viewParams['contents'] = $matches[2];
            $this->_contents = $matches[1] . $this->_render('waindigo_resource_edit_container_customfields',
                $viewParams) . $matches[3];
        }
    }

    protected function _resourceDescription()
    {
        $viewParams = $this->_fetchViewParams();
        if (isset($viewParams['resource']['custom_resource_fields']) && $viewParams['resource']['custom_resource_fields']) {
            $viewParams['customFields'] = unserialize($viewParams['resource']['custom_resource_fields']);
        }
        $resource = $viewParams['resource'];
        $header = $this->_render('_header_resource_category.' . $resource['resource_category_id'], $viewParams);
        $footer = $this->_render('_footer_resource_category.' . $resource['resource_category_id'], $viewParams);
        $pattern = '#<article>.*</article>#s';
        $replacement = $this->_escapeDollars($header) . '${0}' . $this->_escapeDollars($footer);
        $this->_patternReplace($pattern, $replacement);
    }

    protected function _waindigoThreadEditCustomFieldsCustomfields()
    {
        $viewParams = $this->_fetchViewParams();
        $customThreadFields = $viewParams['customThreadFields'];
        foreach ($customThreadFields as $threadFieldGroup) {
            foreach ($threadFieldGroup['fields'] as $field) {
                $field['custom_field_type'] = 'thread';
                $this->_replaceCallbackField($field);
            }
        }
    }
}
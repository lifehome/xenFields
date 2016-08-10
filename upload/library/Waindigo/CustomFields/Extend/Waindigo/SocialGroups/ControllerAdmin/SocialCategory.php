<?php

/**
 *
 * @see Waindigo_SocialGroups_ControllerAdmin_SocialCategory
 */
class Waindigo_CustomFields_Extend_Waindigo_SocialGroups_ControllerAdmin_SocialCategory extends XFCP_Waindigo_CustomFields_Extend_Waindigo_SocialGroups_ControllerAdmin_SocialCategory
{

    /**
     *
     * @see Waindigo_SocialGroups_ControllerAdmin_SocialCategory::actionEdit()
     */
    public function actionEdit()
    {
        /* @var $response XenForo_ControllerResponse_View */
        $response = parent::actionEdit();

        if ($response instanceof XenForo_ControllerResponse_View) {
            if (isset($response->params['forum'])) {
                $category = & $response->params['forum'];
            }

            $fieldModel = $this->_getFieldModel();

            $categoryRequiredFields = array();
            if (isset($category['node_id'])) {
                $categoryId = $category['node_id'];

                $categoryFields = array_keys($fieldModel->getSocialForumFieldsInCategory($categoryId));
                if ($category['required_fields']) {
                    $categoryRequiredFields = unserialize($category['required_social_forum_fields']);
                }

                $headers = array(
                    'social_forum_header' => '_header_social_forum_node.' . $categoryId,
                    'social_forum_footer' => '_footer_social_forum_node.' . $categoryId
                );

                $templates = $this->_getTemplateModel()->getTemplatesInStyleByTitles($headers);
            } else {
                $categoryFields = array();
                $templates = array();
            }

            $response->params['socialForumFieldGroups'] = $fieldModel->getSocialForumFieldsByGroups(
                array(
                    'active' => true
                ));
            $response->params['socialForumFieldOptions'] = $fieldModel->getSocialForumFieldOptions(
                array(
                    'active' => true
                ));
            $response->params['nodeRequiredSocialForumFields'] = ($categoryRequiredFields ? $categoryRequiredFields : array(
                0
            ));
            $response->params['nodeSocialForumFields'] = ($categoryFields ? $categoryFields : array(
                0
            ));
            $response->params['customSocialForumFields'] = $fieldModel->prepareSocialForumFields(
                $fieldModel->getSocialForumFields(array(
                    'active' => true
                )), true,
                (isset($category['custom_social_forum_fields']) && $category['custom_social_forum_fields'] ? unserialize(
                    $category['custom_social_forum_fields']) : array()), true);

            foreach ($templates as $headerName => $template) {
                $key = array_search($headerName, $headers);
                if ($key) {
                    $response->params['template'][$key] = $template['template'];
                }
            }
        }

        return $response;
    }

    /**
     *
     * @see Waindigo_SocialGroups_ControllerAdmin_SocialCategory::actionSave()
     */
    public function actionSave()
    {
        $GLOBALS['Waindigo_SocialGroups_ControllerAdmin_SocialCategory'] = $this;

        return parent::actionSave();
    }

    /**
     *
     * @see Waindigo_SocialGroups_ControllerAdmin_SocialCategory::actionValidateField()
     */
    public function actionValidateField()
    {
        $this->_assertPostOnly();

        $field = $this->_getFieldValidationInputParams();

        if (preg_match('/^custom_social_forum_field_([a-zA-Z0-9_]+)$/', $field['name'], $match)) {
            $writer = XenForo_DataWriter::create('XenForo_DataWriter_Forum');

            $writer->setOption(XenForo_DataWriter_User::OPTION_ADMIN_EDIT, true);

            $writer->setCustomSocialForumFields(array(
                $match[1] => $field['value']
            ));

            $errors = $writer->getErrors();
            if ($errors) {
                return $this->responseError($errors);
            }

            return $this->responseRedirect(XenForo_ControllerResponse_Redirect::SUCCESS, '',
                new XenForo_Phrase('redirect_field_validated',
                    array(
                        'name' => $field['name'],
                        'value' => $field['value']
                    )));
        } else {
            // handle normal fields
            return parent::actionValidateField();
        }
    }

    /**
     *
     * @return Waindigo_CustomFields_Model_SocialForumField
     */
    protected function _getFieldModel()
    {
        return $this->getModelFromCache('Waindigo_CustomFields_Model_SocialForumField');
    }

    /**
     *
     * @return XenForo_Model_Template
     */
    protected function _getTemplateModel()
    {
        return $this->getModelFromCache('XenForo_Model_Template');
    }
}
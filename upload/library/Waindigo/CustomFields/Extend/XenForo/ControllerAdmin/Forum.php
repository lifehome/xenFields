<?php

/**
 *
 * @see XenForo_ControllerAdmin_Forum
 */
class Waindigo_CustomFields_Extend_XenForo_ControllerAdmin_Forum extends XFCP_Waindigo_CustomFields_Extend_XenForo_ControllerAdmin_Forum
{

    /**
     *
     * @see XenForo_ControllerAdmin_Forum::actionEdit()
     */
    public function actionEdit()
    {
        $response = parent::actionEdit();

        if ($response instanceof XenForo_ControllerResponse_View) {
            if (isset($response->params['forum'])) {
                $node = & $response->params['forum'];
            }

            $threadFieldModel = $this->_getThreadFieldModel();

            $nodeRequiredThreadFields = array();
            if (isset($node['node_id'])) {
                $nodeId = $node['node_id'];

                $nodeThreadFields = array_keys($threadFieldModel->getThreadFieldsInForum($nodeId));
                if ($node['required_fields']) {
                    $nodeRequiredThreadFields = unserialize($node['required_fields']);
                }

                $headers = array(
                    'thread_header' => '_header_node.' . $nodeId,
                    'thread_footer' => '_footer_node.' . $nodeId
                );

                $templates = $this->_getTemplateModel()->getTemplatesInStyleByTitles($headers);
            } else {
                $nodeThreadFields = array();
                $templates = array();
            }

            $response->params['threadFieldGroups'] = $threadFieldModel->getThreadFieldsByGroups(
                array(
                    'active' => true
                ));
            $response->params['threadFieldOptions'] = $threadFieldModel->getThreadFieldOptions(
                array(
                    'active' => true
                ));
            $response->params['nodeRequiredThreadFields'] = ($nodeRequiredThreadFields ? $nodeRequiredThreadFields : array(
                0
            ));
            $response->params['nodeThreadFields'] = ($nodeThreadFields ? $nodeThreadFields : array(
                0
            ));
            $response->params['customThreadFields'] = $threadFieldModel->prepareThreadFields(
                $threadFieldModel->getThreadFields(array(
                    'active' => true
                )), true,
                (isset($node['custom_fields']) && $node['custom_fields'] ? unserialize($node['custom_fields']) : array()),
                true);

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
     * @see XenForo_ControllerAdmin_Forum::actionSave()
     */
    public function actionSave()
    {
        $GLOBALS['XenForo_ControllerAdmin_Forum'] = $this;

        return parent::actionSave();
    }

    /**
     *
     * @see XenForo_ControllerAdmin_Forum::actionValidateField()
     */
    public function actionValidateField()
    {
        $this->_assertPostOnly();

        $field = $this->_getFieldValidationInputParams();

        if (preg_match('/^custom_field_([a-zA-Z0-9_]+)$/', $field['name'], $match)) {
            $writer = XenForo_DataWriter::create('XenForo_DataWriter_Forum');

            $writer->setOption(XenForo_DataWriter_User::OPTION_ADMIN_EDIT, true);

            $writer->setCustomFields(array(
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
     * @return Waindigo_CustomFields_Model_ThreadField
     */
    protected function _getThreadFieldModel()
    {
        return $this->getModelFromCache('Waindigo_CustomFields_Model_ThreadField');
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
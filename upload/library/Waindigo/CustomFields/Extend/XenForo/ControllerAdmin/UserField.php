<?php

/**
 *
 * @see XenForo_ControllerAdmin_UserField
 */
class Waindigo_CustomFields_Extend_XenForo_ControllerAdmin_UserField extends XFCP_Waindigo_CustomFields_Extend_XenForo_ControllerAdmin_UserField
{

    /**
     *
     * @see XenForo_ControllerAdmin_UserField::_getFieldAddEditResponse()
     */
    protected function _getFieldAddEditResponse(array $field)
    {
        $field = array_merge(array(
        	'search_advanced_user_waindigo' => 1,
        ), $field);

        if ((isset($field['field_choices_callback_class']) && $field['field_choices_callback_class']) &&
             (isset($field['field_choices_callback_method']) && $field['field_choices_callback_method'])) {
            $field['choice_type'] = "callback";
        } else {
            $field['choice_type'] = "custom";
        }

        /* @var $response XenForo_ControllerResponse_View */
        $response = parent::_getFieldAddEditResponse($field);

        if ($response instanceof XenForo_ControllerResponse_View) {
            $addOnModel = $this->_getAddOnModel();
            $response->params['addOnOptions'] = $addOnModel->getAddOnOptionsListIfAvailable();
            $response->params['addOnSelected'] = (isset($field['addon_id']) ? $field['addon_id'] : $addOnModel->getDefaultAddOnId());
        }

        return $response;
    }

    /**
     *
     * @see XenForo_ControllerAdmin_UserField::actionSave()
     */
    public function actionSave()
    {
        $GLOBALS['XenForo_ControllerAdmin_UserField'] = $this;

        return parent::actionSave();
    }

    /**
     *
     * @return XenForo_ControllerResponse_Abstract
     */
    public function actionExport()
    {
        $fieldId = $this->_input->filterSingle('field_id', XenForo_Input::STRING);
        $field = $this->_getFieldOrError($fieldId);

        $this->_routeMatch->setResponseType('xml');

        $viewParams = array(
            'field' => $field,
            'xml' => $this->_getFieldModel()->getFieldXml($field)
        );

        return $this->responseView('Waindigo_CustomFields_ViewAdmin_UserField_Export', '', $viewParams);
    }

    /**
     *
     * @return XenForo_ControllerResponse_Abstract
     */
    public function actionImport()
    {
        $fieldModel = $this->_getFieldModel();

        if ($this->isConfirmedPost()) {
            $input = $this->_input->filter(
                array(
                    'target' => XenForo_Input::STRING,
                    'display_group' => XenForo_Input::STRING,
                    'overwrite_field_id' => XenForo_Input::STRING
                ));

            $upload = XenForo_Upload::getUploadedFile('upload');
            if (!$upload) {
                return $this->responseError(new XenForo_Phrase('please_upload_valid_field_xml_file'));
            }

            if ($input['target'] == 'overwrite') {
                $field = $this->_getFieldOrError($input['overwrite_field_id']);
                $input['display_group'] = $field['display_group'];
            }

            $document = $this->getHelper('Xml')->getXmlFromFile($upload);
            $caches = $fieldModel->importFieldXml($document, $input['display_group'], $input['overwrite_field_id']);

            if (XenForo_Application::$versionId < 1020000) {
                return XenForo_CacheRebuilder_Abstract::getRebuilderResponse($this, $caches,
                    XenForo_Link::buildAdminLink('user-fields'));
            } else {
                return $this->responseRedirect(XenForo_ControllerResponse_Redirect::SUCCESS,
                    XenForo_Link::buildAdminLink('user-fields'));
            }
        } else {
            $fieldModel = $this->_getFieldModel();
            $viewParams = array(
                'displayGroups' => $fieldModel->getUserFieldGroups(),
                'fields' => $fieldModel->prepareUserFields($fieldModel->getUserFields())
            );

            return $this->responseView('Waindigo_CustomFields_ViewAdmin_UserField_Import', 'user_field_import',
                $viewParams);
        }
    }

    /**
     *
     * @return XenForo_Model_AddOn
     */
    protected function _getAddOnModel()
    {
        return $this->getModelFromCache('XenForo_Model_AddOn');
    }
}
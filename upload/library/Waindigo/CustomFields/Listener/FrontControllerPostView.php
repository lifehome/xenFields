<?php

class Waindigo_CustomFields_Listener_FrontControllerPostView extends Waindigo_Listener_FrontControllerPostView
{

    protected function _run()
    {
        switch ($this->_routePath) {
            case 'user-fields':
                $this->_userFields();
                break;
            default:
                if (preg_match("#^user-fields/(.*)/edit#", $this->_routePath, $matches)) {
                    $this->_userFieldsEdit($matches[1]);
                }
        }
    }

    public static function frontControllerPostView(XenForo_FrontController $fc, &$output)
    {
        $output = self::createAndRun('Waindigo_CustomFields_Listener_FrontControllerPostView', $fc, $output);
    }

    protected function _userFields()
    {
        $this->_appendTemplateAfterTopCtrl('waindigo_user_fields_topctrl_import_customfields');
    }

    /**
     *
     * @param string $fieldId
     */
    protected function _userFieldsEdit($fieldId)
    {
        $this->_assertResponseCode(200);
        $viewParams['field'] = array(
            'field_id' => $fieldId
        );
        $this->_appendTemplateAfterTopCtrl('waindigo_user_fields_topctrl_export_customfields', $viewParams);
    }
}
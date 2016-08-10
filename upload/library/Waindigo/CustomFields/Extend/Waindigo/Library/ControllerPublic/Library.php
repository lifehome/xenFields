<?php

/**
 *
 * @see Waindigo_Library_ControllerPublic_Library
 */
class Waindigo_CustomFields_Extend_Waindigo_Library_ControllerPublic_Library extends XFCP_Waindigo_CustomFields_Extend_Waindigo_Library_ControllerPublic_Library
{

    /**
     *
     * @see Waindigo_Library_ControllerPublic_Library::actionCreateArticle()
     */
    public function actionCreateArticle()
    {
        /* @var $response XenForo_ControllerResponse_View */
        $response = parent::actionCreateArticle();

        if ($response instanceof XenForo_ControllerResponse_View) {
            $nodeId = $response->params['library']['node_id'];

            $fieldValues = array();
            if (isset($response->params['library']['custom_fields']) && $response->params['library']['custom_fields']) {
                $fieldValues = unserialize($response->params['library']['custom_fields']);
            }

            $response->params['customThreadFields'] = $this->_getThreadFieldModel()->prepareGroupedThreadFields(
                $this->_getThreadFieldModel()
                    ->getUsableThreadFieldsInForums(array(
                    $nodeId
                )), true, $fieldValues, false,
                ($response->params['library']['required_fields'] ? unserialize(
                    $response->params['library']['required_fields']) : array()));

            if (!isset($response->params['attachmentButtonKey'])) {
                $response->params['attachmentButtonKey'] = 'image';
            }
        }

        return $response;
    }

    /**
     *
     * @see Waindigo_Library_ControllerPublic_Article::actionValidateField()
     */
    public function actionValidateField()
    {
        $this->_assertPostOnly();

        $field = $this->_getFieldValidationInputParams();

        if (preg_match('/^custom_field_([a-zA-Z0-9_]+)$/', $field['name'], $match)) {
            $writer = XenForo_DataWriter::create('Waindigo_CustomFields_DataWriter_Article');

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
     * @see Waindigo_Library_ControllerPublic_Library::actionAddArticle()
     */
    public function actionAddArticle()
    {
        $GLOBALS['Waindigo_Library_ControllerPublic_Library'] = $this;

        return parent::actionAddArticle();
    }

    /**
     *
     * @return Waindigo_CustomFields_Model_ThreadField
     */
    protected function _getThreadFieldModel()
    {
        return $this->getModelFromCache('Waindigo_CustomFields_Model_ThreadField');
    }
}
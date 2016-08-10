<?php

/**
 *
 * @see Waindigo_Library_ControllerPublic_Article
 */
class Waindigo_CustomFields_Extend_Waindigo_Library_ControllerPublic_Article extends XFCP_Waindigo_CustomFields_Extend_Waindigo_Library_ControllerPublic_Article
{

    /**
     *
     * @see Waindigo_Library_ControllerPublic_Article::actionIndex()
     */
    public function actionIndex()
    {
        $response = parent::actionIndex();

        if ($response instanceof XenForo_ControllerResponse_View) {
            $nodeId = $response->params['library']['node_id'];

            $this->_prepareCustomFieldsForArticlePages($response->params['article'], $nodeId);
        }

        return $response;
    }

    /**
     *
     * @param array $article
     * @param int $nodeId
     */
    protected function _prepareCustomFieldsForArticlePages(array &$article, $nodeId)
    {
        $threadFieldsGrouped = $this->_getThreadFieldModel()->getUsableThreadFieldsInForums(
            array(
                $nodeId
            ));
        foreach ($threadFieldsGrouped as $groupId => $threadFields) {
            foreach ($threadFields['fields'] as $threadFieldId => $threadField) {
                if (!$threadField['viewable_thread_view']) {
                    unset($threadFieldsGrouped[$groupId]['fields'][$threadFieldId]);
                }
            }
        }

        $fieldValues = array();
        if (isset($article['custom_fields']) && $article['custom_fields']) {
            $fieldValues = unserialize($article['custom_fields']);
        }

        $article['customFields'] = $this->_getThreadFieldModel()->prepareGroupedThreadFields($threadFieldsGrouped, true,
            $fieldValues, false, array());
    }

    /**
     *
     * @see Waindigo_Library_ControllerPublic_Article::actionEdit()
     */
    public function actionEdit()
    {
        $response = parent::actionEdit();

        if ($response instanceof XenForo_ControllerResponse_View) {
            $nodeId = $response->params['article']['node_id'];

            $fieldValues = array();
            if (isset($response->params['article']['custom_fields']) && $response->params['article']['custom_fields']) {
                $fieldValues = unserialize($response->params['article']['custom_fields']);
            }

            $response->params['customThreadFields'] = $this->_getThreadFieldModel()->prepareGroupedThreadFields(
                $this->_getThreadFieldModel()
                    ->getUsableThreadFieldsInForums(array(
                    $nodeId
                )), true, $fieldValues, false,
                ($response->params['library']['required_fields'] ? unserialize(
                    $response->params['library']['required_fields']) : array()));
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
     * @see Waindigo_Library_ControllerPublic_Article::actionSave()
     */
    public function actionSave()
    {
        $GLOBALS['Waindigo_Library_ControllerPublic_Article'] = $this;

        return parent::actionSave();
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
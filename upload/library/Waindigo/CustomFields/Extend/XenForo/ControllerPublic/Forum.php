<?php

/**
 *
 * @see XenForo_ControllerPublic_Forum
 */
class Waindigo_CustomFields_Extend_XenForo_ControllerPublic_Forum extends XFCP_Waindigo_CustomFields_Extend_XenForo_ControllerPublic_Forum
{

    /**
     *
     * @see XenForo_ControllerPublic_Forum::actionIndex()
     */
    public function actionIndex()
    {
        $response = parent::actionIndex();

        $this->_addCustomThreadFields($response);

        return $response;
    }

    /**
     *
     * @see XenForo_ControllerPublic_Forum::actionForum()
     */
    public function actionForum()
    {
        $response = parent::actionForum();

        $this->_addCustomThreadFields($response);

        return $response;
    }

    /**
     *
     * @param XenForo_ControllerResponse_Abstract $response
     */
    protected function _addCustomThreadFields(XenForo_ControllerResponse_Abstract $response)
    {
        if ($response instanceof XenForo_ControllerResponse_View && isset($response->params['forum'])) {
            $threadFieldModel = $this->_getThreadFieldModel();

            $nodeId = $response->params['forum']['node_id'];

            $usableThreadFields = $threadFieldModel->getUsableThreadFieldsInForums(
                array(
                    $nodeId
                ));
            $threadFieldsGrouped = $usableThreadFields;
            foreach ($threadFieldsGrouped as $groupId => $threadFields) {
                foreach ($threadFields['fields'] as $threadFieldId => $threadField) {
                    if (!$threadField['viewable_forum_view']) {
                        unset($threadFieldsGrouped[$groupId]['fields'][$threadFieldId]);
                    }
                }
            }

            foreach ($response->params['threads'] as $threadId => $thread) {
                $fieldValues = array();
                if (isset($thread['custom_fields']) && $thread['custom_fields']) {
                    $fieldValues = unserialize($thread['custom_fields']);
                }
                $response->params['threads'][$threadId]['customThreadFields'] = $this->_getThreadFieldModel()->prepareGroupedThreadFields(
                    $threadFieldsGrouped, true, $fieldValues, false, array(), $response->params);
            }

            foreach ($response->params['stickyThreads'] as $threadId => $thread) {
                $fieldValues = array();
                if (isset($thread['custom_fields']) && $thread['custom_fields']) {
                    $fieldValues = unserialize($thread['custom_fields']);
                }
                $response->params['stickyThreads'][$threadId]['customThreadFields'] = $this->_getThreadFieldModel()->prepareGroupedThreadFields(
                    $threadFieldsGrouped, true, $fieldValues, false, array(), $response->params);
            }

            $verifyUsability = XenForo_Application::get('options')->waindigo_showSearchUsableOnly_customFields;

            $threadFields = $usableThreadFields;
            if (!$verifyUsability) {
                $threadFields = $threadFieldModel->getUsableThreadFieldsInForums(
                    array(
                        $nodeId
                    ), null, $verifyUsability);
            }
            foreach ($threadFields as $groupId => $group) {
                foreach ($group['fields'] as $threadFieldId => $threadField) {
                    if (!$threadField['search_quick_forum_waindigo']) {
                        unset($threadFields[$groupId]['fields'][$threadFieldId]);
                    }
                }
                if (empty($threadFields[$groupId]['fields'])) {
                    unset($threadFields[$groupId]);
                }
            }

            $response->params['search']['customThreadFields'] = $threadFieldModel->prepareGroupedThreadFields(
                $threadFields, true);
        }
    }

    /**
     *
     * @see XenForo_ControllerPublic_Forum::actionCreateThread()
     */
    public function actionCreateThread()
    {
        $response = parent::actionCreateThread();

        if ($response instanceof XenForo_ControllerResponse_View) {
            $nodeId = $response->params['forum']['node_id'];

            $fieldValues = array();
            if (isset($response->params['forum']['custom_fields']) && $response->params['forum']['custom_fields']) {
                $fieldValues = unserialize($response->params['forum']['custom_fields']);
            }

            $response->params['customThreadFields'] = $this->_getThreadFieldModel()->prepareGroupedThreadFields(
                $this->_getThreadFieldModel()
                    ->getUsableThreadFieldsInForums(array(
                    $nodeId
                )), true, $fieldValues, false,
                ($response->params['forum']['required_fields'] ? unserialize(
                    $response->params['forum']['required_fields']) : array()));

            if (!isset($response->params['attachmentButtonKey'])) {
                $response->params['attachmentButtonKey'] = 'image';
            }
        }

        return $response;
    }

    /**
     *
     * @see XenForo_ControllerPublic_Forum::actionValidateField()
     */
    public function actionValidateField()
    {
        $this->_assertPostOnly();

        $field = $this->_getFieldValidationInputParams();

        if (preg_match('/^custom_field_([a-zA-Z0-9_]+)$/', $field['name'], $match)) {
            $writer = XenForo_DataWriter::create('Waindigo_CustomFields_DataWriter_Forum');

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
     * @see XenForo_ControllerPublic_Forum::actionAddThread()
     */
    public function actionAddThread()
    {
        $GLOBALS['XenForo_ControllerPublic_Forum'] = $this;

        return parent::actionAddThread();
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
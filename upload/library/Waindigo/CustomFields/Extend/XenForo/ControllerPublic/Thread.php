<?php

/**
 *
 * @see XenForo_ControllerPublic_Thread
 */
class Waindigo_CustomFields_Extend_XenForo_ControllerPublic_Thread extends XFCP_Waindigo_CustomFields_Extend_XenForo_ControllerPublic_Thread
{

    /**
     *
     * @see XenForo_ControllerPublic_Thread::actionIndex()
     */
    public function actionIndex()
    {
        $response = parent::actionIndex();

        if ($response instanceof XenForo_ControllerResponse_View) {
            /* @var $response XenForo_ControllerResponse_View */

            $nodeId = $response->params['forum']['node_id'];

            $threadFieldModel = $this->_getThreadFieldModel();

            $verifyUsability = XenForo_Application::get('options')->waindigo_showSearchUsableOnly_customFields;
            $threadFields = $threadFieldModel->getUsableThreadFieldsInForums(
                array(
                    $nodeId
                ), null, $verifyUsability);

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

            $response->params['canEditThreadFields'] = $this->_getThreadModel()->canEditThreadFields(
                $response->params['thread'], $response->params['forum']);

            $this->_prepareCustomFieldsForPosts($response->params['thread'], $nodeId);
        }

        return $response;
    }

    protected function _prepareCustomFieldsForPosts(array &$thread, $nodeId)
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
        if (isset($thread['custom_fields']) && $thread['custom_fields']) {
            $fieldValues = unserialize($thread['custom_fields']);
        }

        $thread['customFields'] = $this->_getThreadFieldModel()->prepareGroupedThreadFields($threadFieldsGrouped, true,
            $fieldValues, false, array());
    }

    /**
     *
     * @see XenForo_ControllerPublic_Thread::actionShowPosts()
     */
    public function actionShowPosts()
    {
        $response = parent::actionShowPosts();

        if ($response instanceof XenForo_ControllerResponse_View) {
            $nodeId = $response->params['thread']['node_id'];

            $this->_prepareCustomFieldsForPosts($response->params['thread'], $nodeId);
        }

        return $response;
    }

    /**
     *
     * @see XenForo_ControllerPublic_Thread::actionEdit()
     */
    public function actionEdit()
    {
        $response = parent::actionEdit();

        if ($response instanceof XenForo_ControllerResponse_View) {
            $nodeId = $response->params['thread']['node_id'];

            $fieldValues = array();
            if (isset($response->params['thread']['custom_fields']) && $response->params['thread']['custom_fields']) {
                $fieldValues = unserialize($response->params['thread']['custom_fields']);
            }

            $response->params['customThreadFields'] = $this->_getThreadFieldModel()->prepareGroupedThreadFields(
                $this->_getThreadFieldModel()
                    ->getUsableThreadFieldsInForums(array(
                    $nodeId
                )), true, $fieldValues, false,
                ($response->params['forum']['required_fields'] ? unserialize(
                    $response->params['forum']['required_fields']) : array()));
        }

        return $response;
    }

    /**
     *
     * @see XenForo_ControllerPublic_Thread::actionSave()
     */
    public function actionSave()
    {
        $GLOBALS['XenForo_ControllerPublic_Thread'] = $this;

        return parent::actionSave();
    }

    /**
     * Displays a form to edit custom fields.
     *
     * @return XenForo_ControllerResponse_Abstract
     */
    public function actionEditCustomFields()
    {
        $this->_assertRegistrationRequired();

        $threadId = $this->_input->filterSingle('thread_id', XenForo_Input::UINT);

        $ftpHelper = $this->getHelper('ForumThreadPost');
        list($thread, $forum) = $ftpHelper->assertThreadValidAndViewable($threadId);

        $threadModel = $this->_getThreadModel();

        if (!$threadModel->canEditThreadFields($thread, $forum, $errorPhraseKey)) {
            throw $this->getErrorOrNoPermissionResponseException($errorPhraseKey);
        }

        if ($this->isConfirmedPost()) {
            $customFields = $this->_input->filterSingle('custom_fields', XenForo_Input::ARRAY_SIMPLE);
            $customFieldsShown = $this->_input->filterSingle('custom_fields_shown', XenForo_Input::STRING,
                array(
                    'array' => true
                ));

            $dw = XenForo_DataWriter::create('XenForo_DataWriter_Discussion_Thread');
            $dw->setExistingData($threadId);
            $dw->setCustomFields($customFields, $customFieldsShown);
            $dw->setExtraData(XenForo_DataWriter_Discussion_Thread::DATA_FORUM, $forum);
            $dw->save();

            $this->_updateModeratorLogThreadEdit($thread, $dw);
            $thread = $dw->getMergedData();

            // regular redirect
            return $this->responseRedirect(XenForo_ControllerResponse_Redirect::SUCCESS,
                XenForo_Link::buildPublicLink('threads', $thread));
        } else {
            $fieldValues = array();
            if (!empty($thread['custom_fields'])) {
                $fieldValues = unserialize($thread['custom_fields']);
            }

            $customThreadFields = $this->_getThreadFieldModel()->prepareGroupedThreadFields(
                $this->_getThreadFieldModel()
                    ->getUsableThreadFieldsInForums(array(
                    $thread['node_id']
                )), true, $fieldValues, false,
                ($forum['required_fields'] ? unserialize($forum['required_fields']) : array()));

            $viewParams = array(
                'thread' => $thread,
                'forum' => $forum,

                'customThreadFields' => $customThreadFields,

                'nodeBreadCrumbs' => $ftpHelper->getNodeBreadCrumbs($forum)
            );

            return $this->responseView('Waindigo_CustomFields_ViewPublic_Thread_EditCustomFields',
                'waindigo_thread_edit_custom_fields_customfields', $viewParams);
        }
    }

    /**
     *
     * @see XenForo_ControllerPublic_Thread::actionValidateField()
     */
    public function actionValidateField()
    {
        $this->_assertPostOnly();

        $field = $this->_getFieldValidationInputParams();

        if (preg_match('/^custom_field_([a-zA-Z0-9_]+)$/', $field['name'], $match)) {
            $writer = XenForo_DataWriter::create('Waindigo_CustomFields_DataWriter_Thread');

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
}
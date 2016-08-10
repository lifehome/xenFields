<?php

/**
 * Controller for managing custom thread fields.
 */
class Waindigo_CustomFields_ControllerAdmin_ThreadField extends Waindigo_CustomFields_ControllerAdmin_Abstract
{

    protected function _preDispatch($action)
    {
        $this->assertAdminPermission('thread');
    }

    /**
     * Displays a list of custom thread fields.
     *
     * @return XenForo_ControllerResponse_Abstract
     */
    public function actionIndex()
    {
        $fieldModel = $this->_getFieldModel();

        $fieldGroups = $fieldModel->getAllThreadFieldGroups();
        $fieldCount = 0;
        $fields = $fieldModel->getThreadFieldsByGroups(array(), array(), $fieldCount);

        $fieldGroups = $fieldModel->mergeThreadFieldsIntoGroups($fields, $fieldGroups);

        $fieldGroupTitles = array();
        foreach ($fieldGroups as $fieldGroupId => $fieldGroup) {
            $fieldGroupTitles[$fieldGroupId] = new XenForo_Phrase(
                $fieldModel->getThreadFieldGroupTitlePhraseName($fieldGroupId));
        }

        $viewParams = array(
            'fieldGroups' => $fieldGroups,
            'fieldCount' => $fieldCount,
            'fieldGroupTitles' => $fieldGroupTitles,
            'fieldTypes' => $fieldModel->getThreadFieldTypes()
        );

        return $this->responseView('Waindigo_CustomFields_ViewAdmin_ThreadField_List', 'thread_field_list', $viewParams);
    }

    /**
     * Gets the add/edit form response for a field.
     *
     * @param array $field
     *
     * @return XenForo_ControllerResponse_Abstract
     */
    protected function _getFieldAddEditResponse(array $field,
        $viewName = 'Waindigo_CustomFields_ViewAdmin_ThreadField_Edit', $templateName = 'thread_field_edit', $viewParams = array())
    {
        $field = array_merge(array(
            'search_advanced_thread_waindigo' => 1,
            'search_quick_forum_waindigo' => 0
        ), $field);

        $userGroups = $this->_getUserGroupModel()->getAllUserGroups();

        $fieldModel = $this->_getFieldModel();

        $typeMap = $fieldModel->getThreadFieldTypeMap();
        $validFieldTypes = $fieldModel->getThreadFieldTypes();

        if ((isset($field['field_choices_callback_class']) && $field['field_choices_callback_class']) &&
             (isset($field['field_choices_callback_method']) && $field['field_choices_callback_method'])) {
            $field['choice_type'] = "callback";
        } else {
            $field['choice_type'] = "custom";
        }

        if (!empty($field['field_id'])) {
            $selNodeIds = $fieldModel->getForumAssociationsByThreadField($field['field_id']);

            $selUserGroupIds = explode(',', $field['allowed_user_group_ids']);
            if (in_array(-1, $selUserGroupIds)) {
                $allUserGroups = true;
                $selUserGroupIds = array_keys($userGroups);
            } else {
                $allUserGroups = false;
            }

            $masterTitle = $fieldModel->getThreadFieldMasterTitlePhraseValue($field['field_id']);
            $masterDescription = $fieldModel->getThreadFieldMasterDescriptionPhraseValue($field['field_id']);

            $existingType = $typeMap[$field['field_type']];
            foreach ($validFieldTypes as $typeId => $type) {
                if ($typeMap[$typeId] != $existingType) {
                    unset($validFieldTypes[$typeId]);
                }
            }
        } else {
            $selNodeIds = array();
            $allUserGroups = true;
            $selUserGroupIds = array_keys($userGroups);
            $masterTitle = '';
            $masterDescription = '';
            $existingType = false;
        }

        if (!$selNodeIds) {
            $selNodeIds = array(
                0
            );
        }

        $addOnModel = $this->_getAddOnModel();

        $viewParams = array_merge(
            array(
                'field' => $field,
                'fieldGroupOptions' => $fieldModel->getThreadFieldGroupOptions($field['field_group_id']),

                'selNodeIds' => $selNodeIds,
                'allUserGroups' => $allUserGroups,
                'selUserGroupIds' => $selUserGroupIds,
                'masterTitle' => $masterTitle,
                'masterDescription' => $masterDescription,
                'masterFieldChoices' => $fieldModel->getThreadFieldChoices($field['field_id'], $field['field_choices'],
                    true),

                'validFieldTypes' => $validFieldTypes,
                'fieldTypeMap' => $typeMap,
                'existingType' => $existingType,

                'nodes' => $this->_getNodeModel()->getAllNodes(),
                'userGroups' => $userGroups,

                'addOnOptions' => $addOnModel->getAddOnOptionsListIfAvailable(),
                'addOnSelected' => (isset($field['addon_id']) ? $field['addon_id'] : $addOnModel->getDefaultAddOnId())
            ), $viewParams);

        return $this->responseView($viewName, $templateName, $viewParams);
    }

    /**
     * Saves a custom thread field.
     *
     * @return XenForo_ControllerResponse_Abstract
     */
    public function actionSave()
    {
        $fieldId = $this->_input->filterSingle('field_id', XenForo_Input::STRING);

        $newFieldId = $this->_input->filterSingle('new_field_id', XenForo_Input::STRING);
        $dwInput = $this->_input->filter(
            array(
                'field_group_id' => XenForo_Input::UINT,
                'display_order' => XenForo_Input::UINT,
                'field_type' => XenForo_Input::STRING,
                'match_type' => XenForo_Input::STRING,
                'match_regex' => XenForo_Input::STRING,
                'match_callback_class' => XenForo_Input::STRING,
                'match_callback_method' => XenForo_Input::STRING,
                'max_length' => XenForo_Input::UINT,
                'viewable_forum_view' => XenForo_Input::UINT,
                'viewable_thread_view' => XenForo_Input::UINT,
                'below_title_on_create' => XenForo_Input::UINT,
                'search_advanced_thread_waindigo' => XenForo_Input::UINT,
                'search_quick_forum_waindigo' => XenForo_Input::UINT,
                'display_template' => XenForo_Input::STRING,
                'display_callback_class' => XenForo_Input::STRING,
                'display_callback_method' => XenForo_Input::STRING,
                'field_choices_callback_class' => XenForo_Input::STRING,
                'field_choices_callback_method' => XenForo_Input::STRING,
                'addon_id' => XenForo_Input::STRING,
                'field_callback_class' => XenForo_Input::STRING,
                'field_callback_method' => XenForo_Input::STRING,
                'export_callback_class' => XenForo_Input::STRING,
                'export_callback_method' => XenForo_Input::STRING
            ));

        $input = $this->_input->filter(
            array(
                'usable_user_group_type' => XenForo_Input::STRING,
                'user_group_ids' => array(
                    XenForo_Input::UINT,
                    'array' => true
                ),
                'node_ids' => array(
                    XenForo_Input::UINT,
                    'array' => true
                )
            ));

        if ($input['usable_user_group_type'] == 'all') {
            $allowedGroupIds = array(
                -1
            ); // -1 is a sentinel for all groups
        } else {
            $allowedGroupIds = $input['user_group_ids'];
        }

        $dw = XenForo_DataWriter::create('Waindigo_CustomFields_DataWriter_ThreadField');
        if ($fieldId) {
            $dw->setExistingData($fieldId);
        } else {
            $dw->set('field_id', $newFieldId);
        }

        $dw->bulkSet($dwInput);

        $dw->set('allowed_user_group_ids', $allowedGroupIds);

        $dw->setExtraData(Waindigo_CustomFields_DataWriter_ThreadField::DATA_TITLE,
            $this->_input->filterSingle('title', XenForo_Input::STRING));
        $dw->setExtraData(Waindigo_CustomFields_DataWriter_ThreadField::DATA_DESCRIPTION,
            $this->_input->filterSingle('description', XenForo_Input::STRING));

        $fieldChoices = $this->_input->filterSingle('field_choice', XenForo_Input::STRING,
            array(
                'array' => true
            ));
        $fieldChoicesText = $this->_input->filterSingle('field_choice_text', XenForo_Input::STRING,
            array(
                'array' => true
            ));
        $fieldChoicesCombined = array();
        foreach ($fieldChoices as $key => $choice) {
            if (isset($fieldChoicesText[$key])) {
                $fieldChoicesCombined[$choice] = $fieldChoicesText[$key];
            }
        }

        $dw->setFieldChoices($fieldChoicesCombined);

        $dw->save();

        $this->_getFieldModel()->updateThreadFieldForumAssociationByThreadField($dw->get('field_id'),
            $input['node_ids']);

        return $this->responseRedirect(XenForo_ControllerResponse_Redirect::SUCCESS,
            XenForo_Link::buildAdminLink('thread-fields') . $this->getLastHash($dw->get('field_id')));
    }

    /**
     * Deletes a custom thread field.
     *
     * @return XenForo_ControllerResponse_Abstract
     */
    public function actionDelete()
    {
        if ($this->isConfirmedPost()) {
            return $this->_deleteData('Waindigo_CustomFields_DataWriter_ThreadField', 'field_id',
                XenForo_Link::buildAdminLink('thread-fields'));
        } else {
            $field = $this->_getFieldOrError($this->_input->filterSingle('field_id', XenForo_Input::STRING));

            $viewParams = array(
                'field' => $field
            );

            return $this->responseView('Waindigo_CustomFields_ViewAdmin_ThreadField_Delete', 'thread_field_delete',
                $viewParams);
        }
    }

    public function actionExport()
    {
        $fieldId = $this->_input->filterSingle('field_id', XenForo_Input::STRING);
        $field = $this->_getFieldOrError($fieldId);

        $this->_routeMatch->setResponseType('xml');

        $viewParams = array(
            'field' => $field,
            'xml' => $this->_getFieldModel()->getFieldXml($field)
        );

        return $this->responseView('Waindigo_CustomFields_ViewAdmin_ThreadField_Export', '', $viewParams);
    }

    public function actionImport()
    {
        $fieldModel = $this->_getFieldModel();

        if ($this->isConfirmedPost()) {
            $input = $this->_input->filter(
                array(
                    'target' => XenForo_Input::STRING,
                    'field_group_id' => XenForo_Input::UINT,
                    'overwrite_field_id' => XenForo_Input::STRING
                ));

            $upload = XenForo_Upload::getUploadedFile('upload');
            if (!$upload) {
                return $this->responseError(new XenForo_Phrase('please_upload_valid_field_xml_file'));
            }

            if ($input['target'] == 'overwrite') {
                $field = $this->_getFieldOrError($input['overwrite_field_id']);
                $input['field_group_id'] = $field['field_group_id'];
            }

            $document = $this->getHelper('Xml')->getXmlFromFile($upload);
            $caches = $fieldModel->importFieldXml($document, $input['field_group_id'], $input['overwrite_field_id']);

            return XenForo_CacheRebuilder_Abstract::getRebuilderResponse($this, $caches,
                XenForo_Link::buildAdminLink('thread-fields'));
        } else {
            $fieldModel = $this->_getFieldModel();
            $viewParams = array(
                'fieldGroupOptions' => $fieldModel->getThreadFieldGroupOptions(),
                'fields' => $fieldModel->prepareThreadFields($fieldModel->getThreadFields())
            );

            return $this->responseView('Waindigo_CustomFields_ViewAdmin_ThreadField_Import', 'thread_field_import',
                $viewParams);
        }
    }

    public function actionQuickSet()
    {
        $this->_assertPostOnly();

        $fieldIds = $this->_input->filterSingle('field_ids', XenForo_Input::STRING,
            array(
                'array' => true
            ));

        if (empty($fieldIds)) {
            // nothing to do, just head back to the field list
            return $this->responseRedirect(XenForo_ControllerResponse_Redirect::SUCCESS,
                XenForo_Link::buildAdminLink('thread-fields'));
        }

        $fieldModel = $this->_getFieldModel();

        if ($this->isConfirmedPost()) {
            $input = $this->_input->filter(
                array(
                    'apply_field_group_id' => XenForo_Input::UINT,
                    'field_group_id' => XenForo_Input::UINT,

                    'apply_user_group_ids' => XenForo_Input::UINT,
                    'usable_user_group_type' => XenForo_Input::STRING,
                    'user_group_ids' => array(
                        XenForo_Input::UINT,
                        'array' => true
                    ),

                    'apply_node_ids' => XenForo_Input::UINT,
                    'node_ids' => array(
                        XenForo_Input::UINT,
                        'array' => true
                    ),

                    'field_id' => XenForo_Input::UINT
                ));

            XenForo_Db::beginTransaction();

            $fieldChanged = false;
            $orderChanged = false;
            foreach ($fieldIds as $fieldId) {
                $dw = XenForo_DataWriter::create('Waindigo_CustomFields_DataWriter_ThreadField');
                $dw->setOption(Waindigo_CustomFields_DataWriter_ThreadField::OPTION_MASS_UPDATE, true);
                $dw->setExistingData($fieldId);

                if ($input['apply_field_group_id']) {
                    $dw->set('field_group_id', $input['field_group_id']);
                    if ($dw->isChanged('field_group_id')) {
                        $orderChanged = true;
                    }
                }

                if ($input['apply_user_group_ids']) {
                    if ($input['usable_user_group_type'] == 'all') {
                        $allowedGroupIds = array(
                            -1
                        ); // -1 is a sentinel for all groups
                    } else {
                        $allowedGroupIds = $input['user_group_ids'];
                    }

                    $dw->set('allowed_user_group_ids', $allowedGroupIds);
                }

                $dw->save();

                if ($input['apply_node_ids']) {
                    $this->_getFieldModel()->updateThreadFieldForumAssociationByThreadField($dw->get('field_id'),
                        $input['node_ids']);
                }
            }

            if ($orderChanged) {
                $fieldModel->rebuildThreadFieldMaterializedOrder();
            }

            $fieldModel->rebuildThreadFieldCache();

            XenForo_Db::commit();

            return $this->responseRedirect(XenForo_ControllerResponse_Redirect::SUCCESS,
                XenForo_Link::buildAdminLink('thread-fields') . $this->getLastHash($input['field_id']));
        } else {
            if ($fieldId = $this->_input->filterSingle('field_id', XenForo_Input::STRING)) {
                if ($fieldId) {
                    $field = $this->_getFieldOrError($fieldId);
                } else {
                    $field = $fieldModel->getDefaultThreadFieldValues();
                }

                $fields = $fieldModel->getThreadFields(
                    array(
                        'field_ids' => $fieldIds
                    ));

                $viewParams = array(
                    'fieldIds' => $fieldIds,
                    'fields' => $fieldModel->prepareThreadFields($fields)
                );

                return $this->_getFieldAddEditResponse($field,
                    'Waindigo_CustomFields_ViewAdmin_ThreadField_QuickSet_Editor', 'thread_field_quickset_editor',
                    $viewParams);
            } else {
                $viewParams = array(
                    'fieldIds' => $fieldIds,
                    'fieldOptions' => $fieldModel->getThreadFieldOptions(
                        array(
                            'field_ids' => $fieldIds
                        ))
                );

                return $this->responseView('Waindigo_CustomFields_ViewAdmin_ThreadField_QuickSet_FieldChooser',
                    'thread_field_quickset_field_chooser', $viewParams);
            }
        }
    }

    public function actionGroups()
    {
        $fieldGroups = $this->_getFieldModel()->getAllThreadFieldGroups();

        $viewParams = array(
            'fieldGroups' => $this->_getFieldModel()->prepareThreadFieldGroups($fieldGroups)
        );

        return $this->responseView('Waindigo_CustomFields_ViewAdmin_ThreadField_Group_List', 'thread_field_group_list',
            $viewParams);
    }

    protected function _getFieldGroupAddEditResponse(array $fieldGroup)
    {
        if (!empty($fieldGroup['field_group_id'])) {
            $masterTitle = $this->_getPhraseModel()->getMasterPhraseValue(
                $this->_getFieldModel()
                    ->getThreadFieldGroupTitlePhraseName($fieldGroup['field_group_id']));
        } else {
            $masterTitle = '';
        }

        $viewParams = array(
            'fieldGroup' => $fieldGroup,
            'masterTitle' => $masterTitle
        );

        return $this->responseView('Waindigo_CustomFields_ViewAdmin_ThreadField_Group_Edit', 'thread_field_group_edit',
            $viewParams);
    }

    public function actionSaveGroup()
    {
        $this->_assertPostOnly();

        $fieldGroupId = $this->_input->filterSingle('field_group_id', XenForo_Input::UINT);

        $input = $this->_input->filter(
            array(
                'title' => XenForo_Input::STRING,
                'display_order' => XenForo_Input::UINT
            ));

        $dw = XenForo_DataWriter::create('Waindigo_CustomFields_DataWriter_ThreadFieldGroup');
        if ($fieldGroupId) {
            $dw->setExistingData($fieldGroupId);
        }
        $dw->set('display_order', $input['display_order']);
        $dw->setExtraData(Waindigo_CustomFields_DataWriter_ThreadField::DATA_TITLE, $input['title']);
        $dw->save();

        return $this->responseRedirect(XenForo_ControllerResponse_Redirect::SUCCESS,
            XenForo_Link::buildAdminLink('thread-fields') . $this->getLastHash('group_' . $dw->get('field_group_id')));
    }

    public function actionDeleteGroup()
    {
        $fieldGroupId = $this->_input->filterSingle('field_group_id', XenForo_Input::UINT);

        if ($this->isConfirmedPost()) {
            $dw = XenForo_DataWriter::create('Waindigo_CustomFields_DataWriter_ThreadFieldGroup');
            $dw->setExistingData($fieldGroupId);
            $dw->delete();

            return $this->responseRedirect(XenForo_ControllerResponse_Redirect::SUCCESS,
                XenForo_Link::buildAdminLink('thread-fields'));
        } else {
            $viewParams = array(
                'fieldGroup' => $this->_getFieldGroupOrError($fieldGroupId)
            );

            return $this->responseView('Waindigo_CustomFields_ViewAdmin_ThreadField_Group_Delete',
                'thread_field_group_delete', $viewParams);
        }
    }

    /**
     * Gets a valid field group or throws an exception.
     *
     * @param integer $fieldGroupId
     *
     * @return array
     */
    protected function _getFieldGroupOrError($fieldGroupId)
    {
        $info = $this->_getFieldModel()->getThreadFieldGroupById($fieldGroupId);
        if (!$info) {
            throw $this->responseException(
                $this->responseError(new XenForo_Phrase('requested_thread_field_group_not_found'), 404));
        }

        return $this->_getFieldModel()->prepareThreadFieldGroup($info);
    }

    /**
     * Gets the specified field or throws an exception.
     *
     * @param string $id
     *
     * @return array
     */
    protected function _getFieldOrError($id)
    {
        $field = $this->getRecordOrError($id, $this->_getFieldModel(), 'getThreadFieldById',
            'requested_field_not_found');

        return $this->_getFieldModel()->prepareThreadField($field);
    }

    /**
     *
     * @return Waindigo_CustomFields_Model_ThreadField
     */
    protected function _getFieldModel()
    {
        return $this->getModelFromCache('Waindigo_CustomFields_Model_ThreadField');
    }

    /**
     *
     * @return XenForo_Model_Node
     */
    protected function _getNodeModel()
    {
        return $this->getModelFromCache('XenForo_Model_Node');
    }
}
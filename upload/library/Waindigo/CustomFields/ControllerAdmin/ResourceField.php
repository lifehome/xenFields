<?php

/**
 * Controller for managing custom resource fields.
 */
class Waindigo_CustomFields_ControllerAdmin_ResourceField extends Waindigo_CustomFields_ControllerAdmin_Abstract
{

    protected function _preDispatch($action)
    {
        $this->assertAdminPermission('resource');
    }

    /**
     * Displays a list of custom resource fields.
     *
     * @return XenForo_ControllerResponse_Abstract
     */
    public function actionIndex()
    {
        $fieldModel = $this->_getFieldModel();

        $fieldGroups = $fieldModel->getAllResourceFieldGroups();
        $fieldCount = 0;
        $fields = $fieldModel->getResourceFieldsByGroups(array(), array(), $fieldCount);

        $fieldGroups = $fieldModel->mergeResourceFieldsIntoGroups($fields, $fieldGroups);

        $fieldGroupTitles = array();
        foreach ($fieldGroups as $fieldGroupId => $fieldGroup) {
            $fieldGroupTitles[$fieldGroupId] = new XenForo_Phrase(
                $fieldModel->getResourceFieldGroupTitlePhraseName($fieldGroupId));
        }

        $viewParams = array(
            'fieldGroups' => $fieldGroups,
            'fieldCount' => $fieldCount,
            'fieldGroupTitles' => $fieldGroupTitles,
            'fieldTypes' => $fieldModel->getResourceFieldTypes()
        );

        return $this->responseView('Waindigo_CustomFields_ViewAdmin_ResourceField_List', 'waindigo_resource_field_list_customfields',
            $viewParams);
    }

    /**
     * Gets the add/edit form response for a field.
     *
     * @param array $field
     *
     * @return XenForo_ControllerResponse_Abstract
     */
    protected function _getFieldAddEditResponse(array $field, $viewName = '', $templateName = '', $viewParams = array())
    {
        if (!$viewName) {
            $viewName = 'Waindigo_CustomFields_ViewAdmin_ResourceField_Edit';
        }
        if (!$templateName) {
            $templateName = 'waindigo_resource_field_edit_customfields';
        }
        $userGroups = $this->_getUserGroupModel()->getAllUserGroups();
        $fieldModel = $this->_getFieldModel();

        $typeMap = $fieldModel->getResourceFieldTypeMap();
        $validFieldTypes = $fieldModel->getResourceFieldTypes();

        if ((isset($field['field_choices_callback_class']) && $field['field_choices_callback_class']) &&
             (isset($field['field_choices_callback_method']) && $field['field_choices_callback_method'])) {
            $field['choice_type'] = "callback";
        } else {
            $field['choice_type'] = "custom";
        }

        if (!empty($field['field_id'])) {
            $selCategoryIds = $fieldModel->getCategoryAssociationsByResourceField($field['field_id']);

            $selUserGroupIds = explode(',', $field['allowed_user_group_ids']);
            if (in_array(-1, $selUserGroupIds)) {
                $allUserGroups = true;
                $selUserGroupIds = array_keys($userGroups);
            } else {
                $allUserGroups = false;
            }

            $masterTitle = $fieldModel->getResourceFieldMasterTitlePhraseValue($field['field_id']);
            $masterDescription = $fieldModel->getResourceFieldMasterDescriptionPhraseValue($field['field_id']);

            $existingType = $typeMap[$field['field_type']];
            foreach ($validFieldTypes as $typeId => $type) {
                if ($typeMap[$typeId] != $existingType) {
                    unset($validFieldTypes[$typeId]);
                }
            }
        } else {
            $selCategoryIds = array();
            $allUserGroups = true;
            $selUserGroupIds = array_keys($userGroups);
            $masterTitle = '';
            $masterDescription = '';
            $existingType = false;
        }

        if (!$selCategoryIds) {
            $selCategoryIds = array(
                0
            );
        }

        $addOnModel = $this->_getAddOnModel();

        $viewParams = array_merge(
            array(
                'field' => $field,
                'fieldGroupOptions' => $fieldModel->getResourceFieldGroupOptions($field['field_group_id']),

                'selCategoryIds' => $selCategoryIds,
                'allUserGroups' => $allUserGroups,
                'selUserGroupIds' => $selUserGroupIds,
                'masterTitle' => $masterTitle,
                'masterDescription' => $masterDescription,
                'masterFieldChoices' => $fieldModel->getResourceFieldChoices($field['field_id'],
                    $field['field_choices'], true),

                'validFieldTypes' => $validFieldTypes,
                'fieldTypeMap' => $typeMap,
                'existingType' => $existingType,

                'categories' => $this->_getCategoryModel()->getAllCategories(),
                'userGroups' => $userGroups,

                'addOnOptions' => $addOnModel->getAddOnOptionsListIfAvailable(),
                'addOnSelected' => (isset($field['addon_id']) ? $field['addon_id'] : $addOnModel->getDefaultAddOnId())
            ), $viewParams);

        return $this->responseView($viewName, $templateName, $viewParams);
    }

    /**
     * Saves a custom resource field.
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
                'viewable_information' => XenForo_Input::UINT,
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
                'resource_category_ids' => array(
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

        $dw = XenForo_DataWriter::create('Waindigo_CustomFields_DataWriter_ResourceField');
        if ($fieldId) {
            $dw->setExistingData($fieldId);
        } else {
            $dw->set('field_id', $newFieldId);
        }

        $dw->bulkSet($dwInput);

        $dw->set('allowed_user_group_ids', $allowedGroupIds);

        $dw->setExtraData(Waindigo_CustomFields_DataWriter_ResourceField::DATA_TITLE,
            $this->_input->filterSingle('title', XenForo_Input::STRING));
        $dw->setExtraData(Waindigo_CustomFields_DataWriter_ResourceField::DATA_DESCRIPTION,
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

        $this->_getFieldModel()->updateResourceFieldCategoryAssociationByResourceField($dw->get('field_id'),
            $input['resource_category_ids']);

        return $this->responseRedirect(XenForo_ControllerResponse_Redirect::SUCCESS,
            XenForo_Link::buildAdminLink('resource-custom-fields') . $this->getLastHash($dw->get('field_id')));
    }

    /**
     * Deletes a custom resource field.
     *
     * @return XenForo_ControllerResponse_Abstract
     */
    public function actionDelete()
    {
        if ($this->isConfirmedPost()) {
            return $this->_deleteData('Waindigo_CustomFields_DataWriter_ResourceField', 'field_id',
                XenForo_Link::buildAdminLink('resource-custom-fields'));
        } else {
            $field = $this->_getFieldOrError($this->_input->filterSingle('field_id', XenForo_Input::STRING));

            $viewParams = array(
                'field' => $field
            );

            return $this->responseView('Waindigo_CustomFields_ViewAdmin_ResourceField_Delete', 'waindigo_resource_field_delete_customfields',
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

        return $this->responseView('Waindigo_CustomFields_ViewAdmin_ResourceField_Export', '', $viewParams);
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
                XenForo_Link::buildAdminLink('resource-custom-fields'));
        } else {
            $fieldModel = $this->_getFieldModel();
            $viewParams = array(
                'fieldGroupOptions' => $fieldModel->getResourceFieldGroupOptions(),
                'fields' => $fieldModel->prepareResourceFields($fieldModel->getResourceFields())
            );

            return $this->responseView('Waindigo_CustomFields_ViewAdmin_ResourceField_Import', 'resource_field_import',
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
                XenForo_Link::buildAdminLink('resource-custom-fields'));
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
                $dw = XenForo_DataWriter::create('Waindigo_CustomFields_DataWriter_ResourceField');
                $dw->setOption(Waindigo_CustomFields_DataWriter_ResourceField::OPTION_MASS_UPDATE, true);
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
                    $this->_getFieldModel()->updateResourceFieldCategoryAssociationByResourceField($dw->get('field_id'),
                        $input['node_ids']);
                }
            }

            if ($orderChanged) {
                $fieldModel->rebuildResourceFieldMaterializedOrder();
            }

            $fieldModel->rebuildResourceFieldCache();

            XenForo_Db::commit();

            return $this->responseRedirect(XenForo_ControllerResponse_Redirect::SUCCESS,
                XenForo_Link::buildAdminLink('resource-custom-fields') . $this->getLastHash($input['field_id']));
        } else {
            if ($fieldId = $this->_input->filterSingle('field_id', XenForo_Input::STRING)) {
                if ($fieldId) {
                    $field = $this->_getFieldOrError($fieldId);
                } else {
                    $field = $fieldModel->getDefaultResourceFieldValues();
                }

                $fields = $fieldModel->getResourceFields(
                    array(
                        'field_ids' => $fieldIds
                    ));

                $viewParams = array(
                    'fieldIds' => $fieldIds,
                    'fields' => $fieldModel->prepareResourceFields($fields)
                );

                return $this->_getFieldAddEditResponse($field,
                    'Waindigo_CustomFields_ViewAdmin_ResourceField_QuickSet_Editor', 'resource_field_quickset_editor',
                    $viewParams);
            } else {
                $viewParams = array(
                    'fieldIds' => $fieldIds,
                    'fieldOptions' => $fieldModel->getResourceFieldOptions(
                        array(
                            'field_ids' => $fieldIds
                        ))
                );

                return $this->responseView('Waindigo_CustomFields_ViewAdmin_ResourceField_QuickSet_FieldChooser',
                    'resource_field_quickset_field_chooser', $viewParams);
            }
        }
    }

    public function actionGroups()
    {
        $fieldGroups = $this->_getFieldModel()->getAllResourceFieldGroups();

        $viewParams = array(
            'fieldGroups' => $this->_getFieldModel()->prepareResourceFieldGroups($fieldGroups)
        );

        return $this->responseView('Waindigo_CustomFields_ViewAdmin_ResourceField_Group_List',
            'resource_field_group_list', $viewParams);
    }

    protected function _getFieldGroupAddEditResponse(array $fieldGroup)
    {
        if (!empty($fieldGroup['field_group_id'])) {
            $masterTitle = $this->_getPhraseModel()->getMasterPhraseValue(
                $this->_getFieldModel()
                    ->getResourceFieldGroupTitlePhraseName($fieldGroup['field_group_id']));
        } else {
            $masterTitle = '';
        }

        $viewParams = array(
            'fieldGroup' => $fieldGroup,
            'masterTitle' => $masterTitle
        );

        return $this->responseView('Waindigo_CustomFields_ViewAdmin_ResourceField_Group_Edit',
            'resource_field_group_edit', $viewParams);
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

        $dw = XenForo_DataWriter::create('Waindigo_CustomFields_DataWriter_ResourceFieldGroup');
        if ($fieldGroupId) {
            $dw->setExistingData($fieldGroupId);
        }
        $dw->set('display_order', $input['display_order']);
        $dw->setExtraData(Waindigo_CustomFields_DataWriter_ResourceField::DATA_TITLE, $input['title']);
        $dw->save();

        return $this->responseRedirect(XenForo_ControllerResponse_Redirect::SUCCESS,
            XenForo_Link::buildAdminLink('resource-custom-fields') . $this->getLastHash('group_' . $dw->get('field_group_id')));
    }

    public function actionDeleteGroup()
    {
        $fieldGroupId = $this->_input->filterSingle('field_group_id', XenForo_Input::UINT);

        if ($this->isConfirmedPost()) {
            $dw = XenForo_DataWriter::create('Waindigo_CustomFields_DataWriter_ResourceFieldGroup');
            $dw->setExistingData($fieldGroupId);
            $dw->delete();

            return $this->responseRedirect(XenForo_ControllerResponse_Redirect::SUCCESS,
                XenForo_Link::buildAdminLink('resource-custom-fields'));
        } else {
            $viewParams = array(
                'fieldGroup' => $this->_getFieldGroupOrError($fieldGroupId)
            );

            return $this->responseView('Waindigo_CustomFields_ViewAdmin_ResourceField_Group_Delete',
                'resource_field_group_delete', $viewParams);
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
        $info = $this->_getFieldModel()->getResourceFieldGroupById($fieldGroupId);
        if (!$info) {
            throw $this->responseException(
                $this->responseError(new XenForo_Phrase('requested_resource_field_group_not_found'), 404));
        }

        return $this->_getFieldModel()->prepareResourceFieldGroup($info);
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
        $field = $this->getRecordOrError($id, $this->_getFieldModel(), 'getResourceFieldById',
            'requested_field_not_found');

        return $this->_getFieldModel()->prepareResourceField($field);
    }

    /**
     *
     * @return Waindigo_CustomFields_Model_ResourceField
     */
    protected function _getFieldModel()
    {
        return $this->getModelFromCache('Waindigo_CustomFields_Model_ResourceField');
    }

    /**
     *
     * @return XenResource_Model_Category
     */
    protected function _getCategoryModel()
    {
        return $this->getModelFromCache('XenResource_Model_Category');
    }
}

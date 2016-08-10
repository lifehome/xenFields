<?php

/**
 * Abstract controller for managing custom fields.
 */
abstract class Waindigo_CustomFields_ControllerAdmin_Abstract extends XenForo_ControllerAdmin_Abstract
{

    protected function _preDispatch($action)
    {
        $this->assertAdminPermission('thread');
    }

    /**
     * Displays a list of custom fields.
     *
     * @return XenForo_ControllerResponse_Abstract
     */
    abstract public function actionIndex();

    /**
     * Gets the add/edit form response for a field.
     *
     * @param array $field
     *
     * @return XenForo_ControllerResponse_Abstract
     */
    abstract protected function _getFieldAddEditResponse(array $field, $viewName = '', $templateName = '',
        $viewParams = array());

    /**
     * Displays form to add a custom field.
     *
     * @return XenForo_ControllerResponse_Abstract
     */
    public function actionAdd()
    {
        return $this->_getFieldAddEditResponse(
            array(
                'field_id' => null,
                'field_group_id' => '0',
                'display_order' => 1,
                'field_type' => 'textbox',
                'match_type' => 'none',
                'max_length' => 0,
                'field_choices' => ''
            ));
    }

    /**
     * Displays form to edit a custom post field.
     *
     * @return XenForo_ControllerResponse_Abstract
     */
    public function actionEdit()
    {
        $field = $this->_getFieldOrError($this->_input->filterSingle('field_id', XenForo_Input::STRING));
        return $this->_getFieldAddEditResponse($field);
    }

    /**
     * Saves a custom field.
     *
     * @return XenForo_ControllerResponse_Abstract
     */
    abstract public function actionSave();

    /**
     * Deletes a custom field.
     *
     * @return XenForo_ControllerResponse_Abstract
     */
    abstract public function actionDelete();

    abstract public function actionExport();

    abstract public function actionImport();

    abstract public function actionQuickSet();

    abstract public function actionGroups();

    abstract protected function _getFieldGroupAddEditResponse(array $fieldGroup);

    public function actionAddGroup()
    {
        return $this->_getFieldGroupAddEditResponse(array(
            'display_order' => 1
        ));
    }

    public function actionEditGroup()
    {
        $fieldGroupId = $this->_input->filterSingle('field_group_id', XenForo_Input::UINT);
        $fieldGroup = $this->_getFieldGroupOrError($fieldGroupId);

        return $this->_getFieldGroupAddEditResponse($fieldGroup);
    }

    abstract public function actionSaveGroup();

    abstract public function actionDeleteGroup();

    /**
     * Gets a valid field group or throws an exception.
     *
     * @param integer $fieldGroupId
     *
     * @return array
     */
    abstract protected function _getFieldGroupOrError($fieldGroupId);

    /**
     * Gets the specified field or throws an exception.
     *
     * @param string $id
     *
     * @return array
     */
    abstract protected function _getFieldOrError($id);

    /**
     *
     * @return XenForo_Model_AddOn
     */
    protected function _getAddOnModel()
    {
        return $this->getModelFromCache('XenForo_Model_AddOn');
    }

    /**
     *
     * @return Waindigo_CustomFields_Model_Abstract
     */
    abstract protected function _getFieldModel();

    /**
     *
     * @return XenForo_Model_UserGroup
     */
    protected function _getUserGroupModel()
    {
        return $this->getModelFromCache('XenForo_Model_UserGroup');
    }

    /**
     *
     * @return XenForo_Model_Phrase
     */
    protected function _getPhraseModel()
    {
        return $this->getModelFromCache('XenForo_Model_Phrase');
    }
}
<?php

/**
 *
 * @see Waindigo_UserSearch_Search_DataHandler_User
 */
class Waindigo_CustomFields_Extend_Waindigo_UserSearch_Search_DataHandler_User extends XFCP_Waindigo_CustomFields_Extend_Waindigo_UserSearch_Search_DataHandler_User
{

    /**
     *
     * @var XenForo_Model_UserField
     */
    protected $_userFieldModel = null;

    /**
     *
     * @see Waindigo_UserSearch_Search_DataHandler_User::getTypeConstraintsFromInput()
     */
    public function getTypeConstraintsFromInput(XenForo_Input $input)
    {
        $constraints = parent::getTypeConstraintsFromInput($input);

        $userFieldModel = $this->_getUserFieldModel();

        $userFields = $userFieldModel->getUserFields();

        $constraints = array_merge($constraints,
            Waindigo_CustomFields_Search_Helper_CustomField::getTypeConstraintsFromInput($input, $userFields, 'user'));

        return $constraints;
    }

    /**
     *
     * @see Waindigo_UserSearch_Search_DataHandler_User::processConstraint()
     */
    public function processConstraint(XenForo_Search_SourceHandler_Abstract $sourceHandler, $constraint, $constraintInfo,
        array $constraints)
    {
        $return = Waindigo_CustomFields_Search_Helper_CustomField::processConstraint($sourceHandler, $constraint,
            $constraintInfo, $constraints, 'user');

        if ($return) {
            return $return;
        }

        return parent::processConstraint($sourceHandler, $constraint, $constraintInfo, $constraints);
    }

    /**
     * Gets the search form controller response for this type.
     *
     * @see XenForo_Search_DataHandler_Abstract::getSearchFormControllerResponse()
     */
    public function getSearchFormControllerResponse(XenForo_ControllerPublic_Abstract $controller, XenForo_Input $input,
        array $viewParams)
    {
        $response = parent::getSearchFormControllerResponse($controller, $input, $viewParams);

        if ($response instanceof XenForo_ControllerResponse_View) {
            $userFieldModel = $this->_getUserFieldModel();

            $userFields = $userFieldModel->getUserFields(
                array(
                    'isSearchAdvancedUser' => true
                ));

            $response->params['search']['customFields'] = $userFieldModel->prepareUserFields($userFields, true);
        }

        return $response;
    }

    /**
     *
     * @see Waindigo_UserSearch_Search_DataHandler_User::getJoinStructures()
     */
    public function getJoinStructures(array $tables)
    {
        $structures = parent::getJoinStructures($tables);

        foreach ($tables as $tableName => $table) {
            if (strlen($tableName) > strlen('user_field_value_') &&
                 substr($tableName, 0, strlen('user_field_value_')) == 'user_field_value_') {
                $structures[$tableName] = array(
                    'table' => 'xf_user_field_value',
                    'key' => 'user_id',
                    'relationship' => array(
                        'search_index',
                        'content_id'
                    )
                );
            }
        }

        return $structures;
    }

    /**
     *
     * @return XenForo_Model_UserField
     */
    protected function _getUserFieldModel()
    {
        if (!$this->_userFieldModel) {
            $this->_userFieldModel = XenForo_Model::create('XenForo_Model_UserField');
        }

        return $this->_userFieldModel;
    }
}
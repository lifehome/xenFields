<?php

/**
 *
 * @see XenResource_Search_DataHandler_Update
 */
class Waindigo_CustomFields_Extend_XenResource_Search_DataHandler_Update extends XFCP_Waindigo_CustomFields_Extend_XenResource_Search_DataHandler_Update
{

    /**
     *
     * @var XenResource_Model_ResourceField
     */
    protected $_resourceFieldModel = null;

    /**
     *
     * @see XenForo_Search_DataHandler_Post::getTypeConstraintsFromInput()
     */
    public function getTypeConstraintsFromInput(XenForo_Input $input)
    {
        $constraints = parent::getTypeConstraintsFromInput($input);

        $resourceFieldModel = $this->_getResourceFieldModel();

        $resourceFields = $resourceFieldModel->getResourceFields();

        $constraints = array_merge($constraints,
            Waindigo_CustomFields_Search_Helper_CustomField::getTypeConstraintsFromInput($input, $resourceFields,
                'resource'));

        return $constraints;
    }

    /**
     *
     * @see XenForo_Search_DataHandler_Post::processConstraint()
     */
    public function processConstraint(XenForo_Search_SourceHandler_Abstract $sourceHandler, $constraint, $constraintInfo,
        array $constraints)
    {
        $return = Waindigo_CustomFields_Search_Helper_CustomField::processConstraint($sourceHandler, $constraint,
            $constraintInfo, $constraints, 'resource');

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
            $resourceFieldModel = $this->_getResourceFieldModel();

            $verifyUsability = XenForo_Application::get('options')->waindigo_showSearchUsableOnly_customFields;
            $resourceFields = $resourceFieldModel->getUsableResourceFields(null, $verifyUsability);

            $response->params['search']['customFields'] = $resourceFieldModel->prepareGroupedResourceFields(
                $resourceFields, true);
        }

        return $response;
    }

    /**
     *
     * @see XenForo_Search_DataHandler_Post::getJoinStructures()
     */
    public function getJoinStructures(array $tables)
    {
        $structures = parent::getJoinStructures($tables);

        foreach ($tables as $tableName => $table) {
            if (strlen($tableName) > strlen('resource_field_value_') &&
                 substr($tableName, 0, strlen('resource_field_value_')) == 'resource_field_value_') {
                $structures[$tableName] = array(
                    'table' => 'xf_resource_field_value',
                    'key' => 'resource_id',
                    'relationship' => array(
                        'search_index',
                        'discussion_id'
                    )
                );
            }
        }

        return $structures;
    }

    /**
     *
     * @return XenResource_Model_ResourceField
     */
    protected function _getResourceFieldModel()
    {
        if (!$this->_resourceFieldModel) {
            $this->_resourceFieldModel = XenForo_Model::create('XenResource_Model_ResourceField');
        }

        return $this->_resourceFieldModel;
    }
}
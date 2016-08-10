<?php

/**
 *
 * @see XenForo_Search_DataHandler_Post
 */
class Waindigo_CustomFields_Extend_XenForo_Search_DataHandler_Post extends XFCP_Waindigo_CustomFields_Extend_XenForo_Search_DataHandler_Post
{

    /**
     *
     * @var Waindigo_CustomFields_Model_ThreadField
     */
    protected $_threadFieldModel = null;

    /**
     *
     * @see XenForo_Search_DataHandler_Post::getTypeConstraintsFromInput()
     */
    public function getTypeConstraintsFromInput(XenForo_Input $input)
    {
        $constraints = parent::getTypeConstraintsFromInput($input);

        $threadFieldModel = $this->_getThreadFieldModel();

        $threadFields = $threadFieldModel->getThreadFields();

        $constraints = array_merge($constraints,
            Waindigo_CustomFields_Search_Helper_CustomField::getTypeConstraintsFromInput($input, $threadFields, 'thread'));

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
            $constraintInfo, $constraints, 'thread');

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
            $threadFieldModel = $this->_getThreadFieldModel();

            $verifyUsability = XenForo_Application::get('options')->waindigo_showSearchUsableOnly_customFields;
            $threadFields = $threadFieldModel->getUsableThreadFields(null, $verifyUsability);

            foreach ($threadFields as $groupId => $group) {
                foreach ($group['fields'] as $threadFieldId => $threadField) {
                    if (empty($threadField['search_advanced_thread_waindigo'])) {
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
            if (strlen($tableName) > strlen('thread_field_value_') &&
                 substr($tableName, 0, strlen('thread_field_value_')) == 'thread_field_value_') {
                $structures[$tableName] = array(
                    'table' => 'xf_thread_field_value',
                    'key' => 'thread_id',
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
     * @return Waindigo_CustomFields_Model_ThreadField
     */
    protected function _getThreadFieldModel()
    {
        if (!$this->_threadFieldModel) {
            $this->_threadFieldModel = XenForo_Model::create('Waindigo_CustomFields_Model_ThreadField');
        }

        return $this->_threadFieldModel;
    }
}
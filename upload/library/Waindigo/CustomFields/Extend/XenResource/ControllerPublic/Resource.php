<?php

/**
 *
 * @see XenResource_ControllerPublic_Resource
 */
class Waindigo_CustomFields_Extend_XenResource_ControllerPublic_Resource_Base extends XFCP_Waindigo_CustomFields_Extend_XenResource_ControllerPublic_Resource
{
}

$rmVersion = 0;
if (XenForo_Application::$versionId >= 1020000) {
    $addOns = XenForo_Application::get('addOns');
    if (isset($addOns['XenResource'])) {
        $rmVersion = $addOns['XenResource'] >= 1010000;
    }
}

if ($rmVersion < 1010000) {

    class Waindigo_CustomFields_Extend_XenResource_ControllerPublic_Resource extends Waindigo_CustomFields_Extend_XenResource_ControllerPublic_Resource_Base
    {

        /**
         *
         * @see XenResource_ControllerPublic_Resource::getResourceViewWrapper()
         */
        protected function _getResourceViewWrapper($selectedTab, array $resource, array $category,
            XenForo_ControllerResponse_View $subView)
        {
            /* @var $response XenForo_ControllerResponse_View */
            $response = parent::_getResourceViewWrapper($selectedTab, $resource, $category, $subView);

            if ($response instanceof XenForo_ControllerResponse_View) {
                $resource = $response->params['resource'];

                $fieldModel = $this->_getFieldModel();
                $customFields = $fieldModel->prepareResourceFields(
                    $fieldModel->getResourceFields(
                        array(
                            'informationView' => true
                        ),
                        array(
                            'valueResourceId' => $resource['resource_id']
                        )));

                $customFieldsGrouped = $fieldModel->groupResourceFields($customFields);

                $response->params['customFieldsGrouped'] = $customFieldsGrouped;
            }

            return $response;
        }

        /**
         *
         * @see XenResource_ControllerPublic_Resource::_getResourceAddOrEditResponse()
         */
        protected function _getResourceAddOrEditResponse(array $resource, array $category, array $attachments = array())
        {
            $response = parent::_getResourceAddOrEditResponse($resource, $category, $attachments);

            if ($response instanceof XenForo_ControllerResponse_View) {
                $categoryId = $response->params['category']['resource_category_id'];

                $fieldValues = array();
                if (isset($response->params['resource']['custom_resource_fields']) &&
                     $response->params['resource']['custom_resource_fields']) {
                    $fieldValues = unserialize($response->params['resource']['custom_resource_fields']);
                } elseif (isset($response->params['category']['category_resource_fields']) &&
                     $response->params['category']['category_resource_fields']) {
                    $fieldValues = unserialize($response->params['category']['category_resource_fields']);
                }

                $response->params['customFields'] = $this->_getFieldModel()->prepareGroupedResourceFields(
                    $this->_getFieldModel()
                        ->getUsableResourceFieldsInCategories(
                        array(
                            $categoryId
                        )), true, $fieldValues, false,
                    ($response->params['category']['required_fields'] ? unserialize(
                        $response->params['category']['required_fields']) : array()));
            }

            return $response;
        }

        /**
         *
         * @see XenResource_ControllerPublic_Resource::actionSave()
         */
        public function actionSave()
        {
            $GLOBALS['XenResource_ControllerPublic_Resource'] = $this;

            return parent::actionSave();
        }

        /**
         *
         * @return Waindigo_CustomFields_Model_ResourceField
         */
        protected function _getFieldModel()
        {
            return $this->getModelFromCache('Waindigo_CustomFields_Model_ResourceField');
        }
    }
} else {

    /**
     *
     * @see XenResource_ControllerPublic_Resource
     */
    class Waindigo_CustomFields_Extend_XenResource_ControllerPublic_Resource extends Waindigo_CustomFields_Extend_XenResource_ControllerPublic_Resource_Base
    {

        /**
         *
         * @see XenResource_ControllerPublic_Resource::actionIndex()
         */
        public function actionIndex()
        {
            $response = parent::actionIndex();

            if ($response instanceof XenForo_ControllerResponse_View) {
                $fieldModel = $this->_getFieldModel();

                $customFields = $fieldModel->getResourceFields();

                $customFieldTabs = array();
                foreach ($customFields as $customFieldId => $field) {
                    if (in_array($field['allow_sort'], array('asc', 'desc'))) {
                        $customFieldTabs['custom_field_' . $customFieldId] = new XenForo_Phrase(
                            $fieldModel->getResourceFieldTitlePhraseName($field['field_id']));
                    }
                }

                $response->params['customFieldTabs'] = $customFieldTabs;
            }

            return $response;
        }

        /**
         *
         * @see XenResource_ControllerPublic_Resource::actionCategory()
         */
        public function actionCategory()
        {
            $response = parent::actionCategory();

            if ($response instanceof XenForo_ControllerResponse_View) {
                $fieldModel = $this->getModelFromCache('Waindigo_CustomFields_Model_ResourceField');

                $customFields = $fieldModel->getResourceFieldsInCategory($response->params['category']['resource_category_id']);

                $customFieldTabs = array();
                foreach ($customFields as $customFieldId => $field) {
                    if (in_array($field['allow_sort'], array('asc', 'desc'))) {
                        $customFieldTabs['custom_field_' . $customFieldId] = new XenForo_Phrase(
                            $fieldModel->getResourceFieldTitlePhraseName($field['field_id']));
                    }
                }

                $response->params['customFieldTabs'] = $customFieldTabs;
            }

            return $response;
        }

        /**
         *
         * @see XenResource_ControllerPublic_Resource::actionFilterMenu()
         */
        public function actionFilterMenu()
        {
            $response = parent::actionFilterMenu();

            /*
             * if ($response instanceof XenForo_ControllerResponse_View) {
             * $resourceFieldModel = $this->_getFieldModel(); $resourceFields =
             * $resourceFieldModel->getUsableResourceFields(); foreach
             * ($resourceFields as $groupId => $group) { if
             * (empty($resourceFields[$groupId]['fields'])) {
             * unset($resourceFields[$groupId]); } }
             * $response->params['customResourceFields'] =
             * $resourceFieldModel->prepareGroupedResourceFields(
             * $resourceFields, true); }
             */

            return $response;
        }

        /**
         *
         * @see XenResource_ControllerPublic_Resource::getResourceViewWrapper()
         */
        protected function _getResourceViewWrapper($selectedTab, array $resource, array $category,
            XenForo_ControllerResponse_View $subView)
        {
            $response = parent::_getResourceViewWrapper($selectedTab, $resource, $category, $subView);

            if ($response instanceof XenForo_ControllerResponse_View) {
                $resource = $response->params['resource'];

                $fieldModel = $this->getModelFromCache('Waindigo_CustomFields_Model_ResourceField');
                $customFields = $fieldModel->prepareResourceFields(
                    $fieldModel->getResourceFields(
                        array(
                            'informationView' => true
                        ),
                        array(
                            'valueResourceId' => $resource['resource_id']
                        )));

                $customFieldsGrouped = $fieldModel->groupResourceFields($customFields);

                $response->params['customFieldsGrouped'] = $customFieldsGrouped;
            }

            return $response;
        }
    }
}
<?php

/**
 *
 * @see XenResource_ControllerAdmin_Category
 */
class Waindigo_CustomFields_Extend_XenResource_ControllerAdmin_Category_Base extends XFCP_Waindigo_CustomFields_Extend_XenResource_ControllerAdmin_Category
{

    /**
     *
     * @see XenResource_ControllerAdmin_Category::_getCategoryAddEditResponse()
     */
    protected function _getCategoryAddEditResponse(array $category)
    {
        /* @var $response XenForo_ControllerResponse_View */
        $response = parent::_getCategoryAddEditResponse($category);

        if ($response instanceof XenForo_ControllerResponse_View) {
            if (isset($response->params['category'])) {
                $category = & $response->params['category'];
            }

            $fieldModel = $this->getModelFromCache('Waindigo_CustomFields_Model_ResourceField');

            $categoryRequiredFields = array();
            if (isset($category['resource_category_id'])) {
                $categoryId = $category['resource_category_id'];

                $categoryFields = array_keys($fieldModel->getResourceFieldsInCategory($categoryId));
                if ($category['required_fields']) {
                    $categoryRequiredFields = unserialize($category['required_fields']);
                }

                $headers = array(
                    'header' => '_header_resource_category.' . $categoryId,
                    'footer' => '_footer_resource_category.' . $categoryId
                );

                $templates = $this->_getTemplateModel()->getTemplatesInStyleByTitles($headers);
            } else {
                $categoryFields = array();
                $templates = array();
            }

            $response->params['fieldGroups'] = $fieldModel->getResourceFieldsByGroups(
                array(
                    'active' => true
                ));
            $response->params['fieldOptions'] = $fieldModel->getResourceFieldOptions(
                array(
                    'active' => true
                ));
            $response->params['categoryRequiredFields'] = ($categoryRequiredFields ? $categoryRequiredFields : array(
                0
            ));
            $response->params['categoryFields'] = ($categoryFields ? $categoryFields : array(
                0
            ));
            $response->params['customFields'] = $fieldModel->prepareResourceFields(
                $fieldModel->getResourceFields(array(
                    'active' => true
                )), true,
                (isset($category['category_resource_fields']) && $category['category_resource_fields'] ? unserialize(
                    $category['category_resource_fields']) : array()), true);

            foreach ($templates as $headerName => $template) {
                $key = array_search($headerName, $headers);
                if ($key) {
                    $response->params['template'][$key] = $template['template'];
                }
            }
        }

        return $response;
    }

    /**
     *
     * @see XenResource_ControllerAdmin_Category::actionSave()
     */
    public function actionSave()
    {
        $GLOBALS['XenResource_ControllerAdmin_Category'] = $this;

        return parent::actionSave();
    }

    /**
     *
     * @return XenForo_Model_Template
     */
    protected function _getTemplateModel()
    {
        return $this->getModelFromCache('XenForo_Model_Template');
    }
}

$rmVersion = 0;
if (XenForo_Application::$versionId >= 1020000) {
    $addOns = XenForo_Application::get('addOns');
    if (isset($addOns['XenResource'])) {
        $rmVersion = $addOns['XenResource'] >= 1010000;
    }
}

if ($rmVersion < 1010000) {

    class Waindigo_CustomFields_Extend_XenResource_ControllerAdmin_Category extends Waindigo_CustomFields_Extend_XenResource_ControllerAdmin_Category_Base
    {

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

    class Waindigo_CustomFields_Extend_XenResource_ControllerAdmin_Category extends Waindigo_CustomFields_Extend_XenResource_ControllerAdmin_Category_Base
    {

        /**
         *
         * @see XenResource_ControllerAdmin_Category::_getCategoryAddEditResponse()
         */
        protected function _getCategoryAddEditResponse(array $category)
        {
            /* @var $response XenForo_ControllerResponse_View */
            $response = parent::_getCategoryAddEditResponse($category);

            if ($response instanceof XenForo_ControllerResponse_View) {
                $response->params['fieldsGrouped'] = array();
            }

            return $response;
        }
    }
}
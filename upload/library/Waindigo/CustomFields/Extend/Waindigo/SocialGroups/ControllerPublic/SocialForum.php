<?php

/**
 * @see Waindigo_SocialGroups_ControllerPublic_SocialForum
 */
class Waindigo_CustomFields_Extend_Waindigo_SocialGroups_ControllerPublic_SocialForum extends XFCP_Waindigo_CustomFields_Extend_Waindigo_SocialGroups_ControllerPublic_SocialForum
{

    /**
     *
     * @see Waindigo_SocialGroups_ControllerPublic_SocialForum::getSocialForumViewWrapper()
     */
    protected function _getWrapper(XenForo_ControllerResponse_View $subView, $forceSidebar = false)
    {
        /* @var $response XenForo_ControllerResponse_View */
        $response = parent::_getWrapper($subView, $forceSidebar);

        if ($response instanceof XenForo_ControllerResponse_View) {
            $socialForum = $response->params['socialForum'];

            $fieldModel = $this->_getFieldModel();
            $customFields = $fieldModel->prepareSocialForumFields(
                $fieldModel->getSocialForumFields(array(
                    'informationView' => true
                ), array(
                    'valueSocialForumId' => $socialForum['social_forum_id']
                )));

            $customFieldsGrouped = $fieldModel->groupSocialForumFields($customFields);

            $response->params['customFieldsGrouped'] = $customFieldsGrouped;
        }

        return $response;
    }

    /**
     *
     * @see Waindigo_SocialGroups_ControllerPublic_SocialForum::actionEdit()
     */
    public function actionEdit()
    {
        $response = parent::actionEdit();

        if ($response instanceof XenForo_ControllerResponse_View) {
            /* @var $response XenForo_ControllerResponse_View */

            $categoryId = $response->params['forum']['node_id'];

            $fieldValues = array();
            if (isset($response->params['socialForum']['custom_social_forum_fields']) &&
                 $response->params['socialForum']['custom_social_forum_fields']) {
                $fieldValues = unserialize($response->params['socialForum']['custom_social_forum_fields']);
            }

            $response->params['customFields'] = $this->_getFieldModel()->prepareGroupedSocialForumFields(
                $this->_getFieldModel()
                    ->getUsableSocialForumFieldsInCategories(array(
                    $categoryId
                )), true, $fieldValues, false,
                ($response->params['forum']['required_social_forum_fields'] ? unserialize(
                    $response->params['forum']['required_social_forum_fields']) : array()));
        }

        return $response;
    }

    /**
     *
     * @see Waindigo_SocialGroups_ControllerPublic_SocialForum::actionSave()
     */
    public function actionSave()
    {
        $GLOBALS['Waindigo_SocialGroups_ControllerPublic_SocialForum'] = $this;

        return parent::actionSave();
    }

    /**
     *
     * @return Waindigo_CustomFields_Model_SocialForumField
     */
    protected function _getFieldModel()
    {
        return $this->getModelFromCache('Waindigo_CustomFields_Model_SocialForumField');
    }
}
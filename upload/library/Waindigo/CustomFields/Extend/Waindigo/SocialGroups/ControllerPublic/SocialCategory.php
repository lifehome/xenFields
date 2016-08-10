<?php

/**
 * @see Waindigo_SocialGroups_ControllerPublic_SocialCategory
 */
class Waindigo_CustomFields_Extend_Waindigo_SocialGroups_ControllerPublic_SocialCategory extends XFCP_Waindigo_CustomFields_Extend_Waindigo_SocialGroups_ControllerPublic_SocialCategory
{

    /**
     *
     * @see Waindigo_SocialGroups_ControllerPublic_SocialCategory::actionCreateSocialForum()
     */
    public function actionCreateSocialForum()
    {
        $response = parent::actionCreateSocialForum();

        if ($response instanceof XenForo_ControllerResponse_View) {
            /* @var $response XenForo_ControllerResponse_View */

            $categoryId = $response->params['forum']['node_id'];

            $fieldValues = array();
            if (isset($response->params['forum']['custom_social_forum_fields']) &&
                 $response->params['forum']['custom_social_forum_fields']) {
                $fieldValues = unserialize($response->params['forum']['custom_social_forum_fields']);
            }

            $response->params['customFields'] = $this->_getFieldModel()->prepareGroupedSocialForumFields(
                $this->_getFieldModel()
                    ->getUsableSocialForumFieldsInCategories(array(
                    $categoryId
                )), true, $fieldValues, false,
                ($response->params['forum']['required_social_forum_fields'] ? unserialize(
                    $response->params['forum']['required_social_forum_fields']) : array()));

            if (!isset($response->params['attachmentButtonKey'])) {
                $response->params['attachmentButtonKey'] = 'image';
            }
        }

        return $response;
    }

    /**
     *
     * @see Waindigo_SocialGroups_ControllerPublic_SocialCategory::actionAddSocialForum()
     */
    public function actionAddSocialForum()
    {
        $GLOBALS['Waindigo_SocialGroups_ControllerPublic_SocialCategory'] = $this;

        return parent::actionAddSocialForum();
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
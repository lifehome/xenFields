<?php

/**
 *
 * @see Waindigo_SocialGroups_ViewPublic_SocialForum_View
 */
class Waindigo_CustomFields_Extend_Waindigo_SocialGroups_ViewPublic_SocialForum_View extends XFCP_Waindigo_CustomFields_Extend_Waindigo_SocialGroups_ViewPublic_SocialForum_View
{

    /**
     *
     * @see Waindigo_SocialGroups_ViewPublic_SocialForum_View::renderHtml()
     */
    public function renderHtml()
    {
        parent::renderHtml();

        if (isset($this->_params['customFieldsGrouped'])) {
            foreach ($this->_params['customFieldsGrouped'] as &$fields) {
                $fields = Waindigo_CustomFields_ViewPublic_Helper_SocialForum::addSocialForumFieldsValueHtml($this,
                    $fields);
            }
        }
    }
}
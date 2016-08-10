<?php

class Waindigo_CustomFields_Extend_Waindigo_SocialGroups_Install_Controller extends XFCP_Waindigo_CustomFields_Extend_Waindigo_SocialGroups_Install_Controller
{

    /**
     *
     * @see Waindigo_SocialGroups_Install_Controller::_getTables()
     */
    protected function _getTables()
    {
        $tables = parent::_getTables();
        $tables['xf_social_forum'] = array_merge($tables['xf_social_forum'],
            $this->_getTableChangesForAddOn('Waindigo_CustomFields', 'xf_social_forum'));
        return $tables;
    }
}
<?php

class Waindigo_CustomFields_Extend_Waindigo_Library_Install_Controller extends XFCP_Waindigo_CustomFields_Extend_Waindigo_Library_Install_Controller
{

    /**
     *
     * @see Waindigo_CustomFields_Extend_Waindigo_Library_Install::_getTables()
     */
    protected function _getTables()
    {
        $tables = parent::_getTables();
        $tables['xf_library'] = array_merge($tables['xf_library'],
            $this->_getTableChangesForAddOn('Waindigo_CustomFields', 'xf_library'));
        $tables['xf_article'] = array_merge($tables['xf_article'],
            $this->_getTableChangesForAddOn('Waindigo_CustomFields', 'xf_article'));
        return $tables;
    }
}
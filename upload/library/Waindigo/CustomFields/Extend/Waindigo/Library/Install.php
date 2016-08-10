<?php

class Waindigo_CustomFields_Extend_Waindigo_Library_Install extends XFCP_Waindigo_CustomFields_Extend_Waindigo_Library_Install
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
        $tables['xf_article_page'] = array_merge($tables['xf_article_page'],
            $this->_getTableChangesForAddOn('Waindigo_CustomFields', 'xf_article_page'));
        return $tables;
    }
}
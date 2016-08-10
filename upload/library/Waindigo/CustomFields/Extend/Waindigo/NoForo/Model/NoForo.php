<?php

class Waindigo_CustomFields_Extend_Waindigo_NoForo_Model_NoForo extends XFCP_Waindigo_CustomFields_Extend_Waindigo_NoForo_Model_NoForo
{

    /**
     *
     * @see Waindigo_NoForo_Model_NoForo::rebuildForum()
     */
    public function rebuildForum()
    {
        parent::rebuildForum();

        // TODO: probably don't need to rebuild the entire add-on
        Waindigo_Install::install(array(), array(
            'addon_id' => 'Waindigo_CustomFields'
        ));
    }
}
<?php

/**
 *
 * @see XenForo_Model_ThreadRedirect
 */
class Waindigo_CustomFields_Extend_XenForo_Model_ThreadRedirect extends XFCP_Waindigo_CustomFields_Extend_XenForo_Model_ThreadRedirect
{

    /**
     *
     * @see XenForo_Model_ThreadRedirect::createRedirectThread
     */
    public function createRedirectThread($targetUrl, array $newThread, $redirectKey = '', $expiryDate = 0)
    {
        unset($newThread['custom_fields']);

        return parent::createRedirectThread($targetUrl, $newThread, $redirectKey, $expiryDate);
    }
}
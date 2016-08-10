<?php

class Waindigo_CustomFields_Listener_ContainerAdminParams
{

    public static function containerAdminParams(array &$params, XenForo_Dependencies_Abstract $dependencies)
    {
        if (XenForo_Application::$versionId >= 1020000) {
            $addOns = XenForo_Application::get('addOns');

            if (isset($addOns['XenResource']) && $addOns['XenResource'] >= 1010000) {
                unset($params['adminNavigation']['sideLinks']['resources']['resourceCustomFields']);
            }
        }
    }
}
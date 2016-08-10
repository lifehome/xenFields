<?php

class Waindigo_CustomFields_Listener_LoadClass extends Waindigo_Listener_LoadClass
{

    protected function _getExtendedClasses()
    {
        return array(
            'Waindigo_CustomFields' => array(
                'controller' => array(
                    'Waindigo_Library_ControllerAdmin_Library',
                    'Waindigo_Library_ControllerPublic_Article',
                    'Waindigo_Library_ControllerPublic_Library',
                    'Waindigo_SocialGroups_ControllerAdmin_SocialCategory',
                    'Waindigo_SocialGroups_ControllerPublic_SocialCategory',
                    'Waindigo_SocialGroups_ControllerPublic_SocialForum',
                    'XenForo_ControllerAdmin_Forum',
                    'XenForo_ControllerAdmin_UserField',
                    'XenForo_ControllerPublic_Forum',
                    'XenForo_ControllerPublic_Thread',
                    'XenResource_ControllerAdmin_Category',
                    'XenResource_ControllerAdmin_Field',
                    'XenResource_ControllerPublic_Resource',
                    'XenForo_ControllerPublic_Search'
                ), 
                'datawriter' => array(
                    'Waindigo_Library_DataWriter_Article',
                    'Waindigo_Library_DataWriter_Library',
                    'Waindigo_SocialGroups_DataWriter_SocialForum',
                    'XenForo_DataWriter_Discussion_Thread',
                    'XenForo_DataWriter_Forum',
                    'XenForo_DataWriter_User',
                    'XenForo_DataWriter_UserField',
                    'XenResource_DataWriter_Resource',
                    'XenResource_DataWriter_ResourceField',
                    'XenResource_DataWriter_Category'
                ), 
                'installer_waindigo' => array(
                    'Waindigo_Library_Install',
                    'Waindigo_Library_Install_Controller',
                    'Waindigo_SocialGroups_Install_Controller'
                ), 
                'model' => array(
                    'Waindigo_NoForo_Model_NoForo',
                    'XenForo_Model_AddOn',
                    'XenForo_Model_Phrase',
                    'XenForo_Model_Thread',
                    'XenForo_Model_UserField',
                    'XenForo_Model_ThreadRedirect',
                    'XenResource_Model_ResourceField',
                    'XenForo_Model_Search',
                    'XenResource_Model_Resource'
                ), 
                'view' => array(
                    'Waindigo_Library_ViewPublic_Article_View',
                    'Waindigo_SocialGroups_ViewPublic_SocialForum_View',
                    'XenForo_ViewPublic_Forum_View',
                    'XenForo_ViewPublic_Thread_View',
                    'XenForo_ViewPublic_Thread_ViewPosts',
                    'XenResource_ViewPublic_Resource_View'
                ), 
                'search_data' => array(
                    'Waindigo_UserSearch_Search_DataHandler_User',
                    'XenForo_Search_DataHandler_Post',
                    'Waindigo_Library_Search_DataHandler_ArticlePage',
                    'XenResource_Search_DataHandler_Update'
                ), 
            ), 
        );
    }

    public static function loadClassController($class, array &$extend)
    {
        $extend = self::createAndRun('Waindigo_CustomFields_Listener_LoadClass', $class, $extend, 'controller');
    }

    public static function loadClassDataWriter($class, array &$extend)
    {
        $extend = self::createAndRun('Waindigo_CustomFields_Listener_LoadClass', $class, $extend, 'datawriter');
    }

    public static function loadClassInstallerWaindigo($class, array &$extend)
    {
        $extend = self::createAndRun('Waindigo_CustomFields_Listener_LoadClass', $class, $extend, 'installer_waindigo');
    }

    public static function loadClassModel($class, array &$extend)
    {
        $extend = self::createAndRun('Waindigo_CustomFields_Listener_LoadClass', $class, $extend, 'model');
    }

    public static function loadClassView($class, array &$extend)
    {
        $extend = self::createAndRun('Waindigo_CustomFields_Listener_LoadClass', $class, $extend, 'view');
    }

    public static function loadClassSearchData($class, array &$extend)
    {
        $extend = self::createAndRun('Waindigo_CustomFields_Listener_LoadClass', $class, $extend, 'search_data');
    }
}
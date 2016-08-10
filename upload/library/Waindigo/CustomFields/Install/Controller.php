<?php

class Waindigo_CustomFields_Install_Controller extends Waindigo_Install
{

    protected $_resourceManagerUrl = 'http://xenforo.com/community/resources/custom-fields-by-waindigo.885/';

    protected function _getTableNameChangesOnInstall()
    {
        return array(
            'xf_resource_category_field' => 'xf_resource_field_category'
        );
    }

    protected function _getFieldNameChanges()
    {
        return array(
            'xf_resource_category' => array(
                'custom_resource_fields' => 'category_resource_fields mediumblob NULL'
            )
        );
    }

    protected function _getTablesOnInstall()
    {
        return array(
            'xf_resource_field' => array(
                'field_id' => 'varbinary(25) NOT NULL PRIMARY KEY',
                'display_group' => 'varchar(25) NOT NULL DEFAULT \'above_info\'',
                'display_order' => 'int UNSIGNED NOT NULL DEFAULT 1',
                'field_type' => 'varchar(25) NOT NULL DEFAULT \'textbox\'',
                'field_choices' => 'blob NOT NULL',
                'match_type' => 'varchar(25) NOT NULL DEFAULT \'none\'',
                'match_regex' => 'varchar(250) NOT NULL DEFAULT \'\'',
                'match_callback_class' => 'varchar(75) NOT NULL DEFAULT \'\'',
                'match_callback_method' => 'varchar(75) NOT NULL DEFAULT  \'\'',
                'max_length' => 'int UNSIGNED NOT NULL DEFAULT \'0\'',
                'required' => 'tinyint UNSIGNED NOT NULL DEFAULT \'0\'',
                'display_template' => 'text NOT NULL'
            ),
            'xf_resource_field_value' => array(
                'resource_id' => 'int UNSIGNED NOT NULL',
                'field_id' => 'varchar(64) NOT NULL',
                'field_value' => 'mediumtext NOT NULL'
            ),
            'xf_resource_field_category' => array(
                'field_id' => 'varbinary(25) NOT NULL',
                'resource_category_id' => 'int NOT NULL'
            )
        );
    }

    protected function _getTablesOnUninstall()
    {
        if (!$this->_isAddOnInstalled('XenResource')) {
            return $this->_getTablesOnInstall();
        }
        
        return array();
    }

    protected function _getTables()
    {
        return array(
            'xf_thread_field' => array(
                'field_id' => 'varchar(64) NOT NULL PRIMARY KEY',
                'field_group_id' => 'int UNSIGNED NOT NULL',
                'display_order' => 'int UNSIGNED NOT NULL DEFAULT \'0\'',
                'materialized_order' => 'int UNSIGNED NOT NULL DEFAULT \'0\'',
                'field_type' => 'enum(\'textbox\',\'textarea\',\'select\',\'radio\',\'checkbox\',\'multiselect\',\'callback\') NOT NULL DEFAULT \'textbox\'',
                'field_choices' => 'blob NOT NULL',
                'match_type' => 'enum(\'none\',\'number\',\'alphanumeric\',\'email\',\'url\',\'regex\',\'callback\') NOT NULL DEFAULT \'none\'',
                'match_regex' => 'varchar(250) NOT NULL DEFAULT \'\'',
                'match_callback_class' => 'varchar(75) NOT NULL DEFAULT \'\'',
                'match_callback_method' => 'varchar(75) NOT NULL DEFAULT  \'\'',
                'max_length' => 'int UNSIGNED NOT NULL DEFAULT \'0\'',
                'viewable_forum_view' => 'tinyint UNSIGNED NOT NULL DEFAULT \'0\'',
                'viewable_thread_view' => 'tinyint UNSIGNED NOT NULL DEFAULT \'0\'',
                'below_title_on_create' => 'tinyint UNSIGNED NOT NULL DEFAULT \'0\'',
                'search_advanced_thread_waindigo' => 'tinyint UNSIGNED NOT NULL DEFAULT \'1\'',
                'search_quick_forum_waindigo' => 'tinyint UNSIGNED NOT NULL DEFAULT \'0\'',
                'display_template' => 'text NOT NULL',
                'display_callback_class' => 'varchar(75) NOT NULL DEFAULT \'\'',
                'display_callback_method' => 'varchar(75) NOT NULL DEFAULT  \'\'',
                'allowed_user_group_ids' => 'blob NOT NULL',
                'addon_id' => 'varchar(25) NOT NULL DEFAULT \'\'',
                'field_choices_callback_class' => 'varchar(75) NOT NULL DEFAULT \'\'',
                'field_choices_callback_method' => 'varchar(75) NOT NULL DEFAULT \'\'',
                'field_callback_class' => 'varchar(75) NOT NULL DEFAULT \'\'',
                'field_callback_method' => 'varchar(75) NOT NULL DEFAULT  \'\'',
                'export_callback_class' => 'varchar(75) NOT NULL DEFAULT \'\'',
                'export_callback_method' => 'varchar(75) NOT NULL DEFAULT  \'\''
            ),
            'xf_thread_field_group' => array(
                'field_group_id' => 'int UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY',
                'display_order' => 'int UNSIGNED NOT NULL'
            ),
            'xf_thread_field_value' => array(
                'thread_id' => 'int UNSIGNED NOT NULL',
                'field_id' => 'varchar(64) NOT NULL',
                'field_value' => 'mediumtext NOT NULL'
            ),
            'xf_forum_field' => array(
                'node_id' => 'int UNSIGNED NOT NULL',
                'field_id' => 'varchar(64) NOT NULL',
                'field_value' => 'mediumtext NOT NULL'
            ),
            'xf_resource_field_group' => array(
                'field_group_id' => 'int UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY',
                'display_order' => 'int UNSIGNED NOT NULL'
            ),
            'xf_social_forum_field' => array(
                'field_id' => 'varchar(64) NOT NULL PRIMARY KEY',
                'field_group_id' => 'int UNSIGNED NOT NULL',
                'display_order' => 'int UNSIGNED NOT NULL DEFAULT \'0\'',
                'materialized_order' => 'int UNSIGNED NOT NULL DEFAULT \'0\'',
                'field_type' => 'enum(\'textbox\',\'textarea\',\'select\',\'radio\',\'checkbox\',\'multiselect\',\'callback\') NOT NULL DEFAULT \'textbox\'',
                'field_choices' => 'blob NOT NULL',
                'match_type' => 'enum(\'none\',\'number\',\'alphanumeric\',\'email\',\'url\',\'regex\',\'callback\') NOT NULL DEFAULT \'none\'',
                'match_regex' => 'varchar(250) NOT NULL DEFAULT \'\'',
                'match_callback_class' => 'varchar(75) NOT NULL DEFAULT \'\'',
                'match_callback_method' => 'varchar(75) NOT NULL DEFAULT  \'\'',
                'max_length' => 'int UNSIGNED NOT NULL DEFAULT \'0\'',
                'viewable_information' => 'tinyint UNSIGNED NOT NULL DEFAULT \'0\'',
                'display_template' => 'text NOT NULL',
                'display_callback_class' => 'varchar(75) NOT NULL DEFAULT \'\'',
                'display_callback_method' => 'varchar(75) NOT NULL DEFAULT  \'\'',
                'allowed_user_group_ids' => 'blob NOT NULL',
                'addon_id' => 'varchar(25) NOT NULL DEFAULT \'\'',
                'field_choices_callback_class' => 'varchar(75) NOT NULL DEFAULT \'\'',
                'field_choices_callback_method' => 'varchar(75) NOT NULL DEFAULT \'\'',
                'field_callback_class' => 'varchar(75) NOT NULL DEFAULT \'\'',
                'field_callback_method' => 'varchar(75) NOT NULL DEFAULT  \'\'',
                'export_callback_class' => 'varchar(75) NOT NULL DEFAULT \'\'',
                'export_callback_method' => 'varchar(75) NOT NULL DEFAULT  \'\''
            ),
            'xf_social_forum_field_group' => array(
                'field_group_id' => 'int UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY',
                'display_order' => 'int UNSIGNED NOT NULL'
            ),
            'xf_social_forum_field_value' => array(
                'social_forum_id' => 'int UNSIGNED NOT NULL',
                'field_id' => 'varchar(64) NOT NULL',
                'field_value' => 'mediumtext NOT NULL'
            ),
            'xf_social_category_field' => array(
                'node_id' => 'int UNSIGNED NOT NULL',
                'field_id' => 'varchar(64) NOT NULL',
                'field_value' => 'mediumtext NOT NULL'
            ),
            'xf_article_field_value' => array(
                'article_id' => 'int UNSIGNED NOT NULL',
                'field_id' => 'varchar(64) NOT NULL',
                'field_value' => 'mediumtext NOT NULL'
            ),
            'xf_article_page_field_value' => array(
                'article_page_id' => 'int UNSIGNED NOT NULL',
                'field_id' => 'varchar(64) NOT NULL',
                'field_value' => 'mediumtext NOT NULL'
            ),
            'xf_custom_field_attachment' => array(
                'field_attachment_id' => 'int UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY',
                'field_id' => 'varchar(64) NOT NULL',
                'custom_field_type' => 'enum(\'user\',\'thread\',\'post\',\'resource\',\'social_forum\',\'article\',\'article_page\') NOT NULL DEFAULT \'user\'',
                'content_id' => 'int UNSIGNED NOT NULL',
                'temp_hash' => 'varchar(32) NOT NULL',
                'unassociated' => 'tinyint(3) UNSIGNED NOT NULL DEFAULT 1',
                'attach_count' => 'int UNSIGNED NOT NULL DEFAULT \'0\''
            )
        );
    }

    protected function _getTableChanges()
    {
        return array(
            'xf_user_field' => array(
                'display_callback_class' => 'varchar(75) NOT NULL DEFAULT \'\'',
                'display_callback_method' => 'varchar(75) NOT NULL DEFAULT  \'\'',
                'addon_id' => 'varchar(25) NOT NULL DEFAULT \'\'',
                'field_choices_callback_class' => 'varchar(75) NOT NULL DEFAULT \'\'',
                'field_choices_callback_method' => 'varchar(75) NOT NULL DEFAULT \'\'',
                'field_callback_class' => 'varchar(75) NOT NULL DEFAULT \'\'',
                'field_callback_method' => 'varchar(75) NOT NULL DEFAULT  \'\'',
                'export_callback_class' => 'varchar(75) NOT NULL DEFAULT \'\'',
                'export_callback_method' => 'varchar(75) NOT NULL DEFAULT  \'\'',
                'search_advanced_user_waindigo' => 'tinyint UNSIGNED NOT NULL DEFAULT 1'
            ),
            'xf_forum' => array(
                'field_cache' => 'mediumblob NULL',
                'custom_fields' => 'mediumblob NULL',
                'required_fields' => 'mediumblob NULL',
                'social_forum_field_cache' => 'mediumblob NULL',
                'custom_social_forum_fields' => 'mediumblob NULL',
                'required_social_forum_fields' => 'mediumblob NULL'
            ),
            'xf_thread' => array(
                'custom_fields' => 'mediumblob NULL'
            ),
            'xf_resource_field' => array(
                'field_group_id' => 'int UNSIGNED NOT NULL DEFAULT \'0\'',
                'materialized_order' => 'int UNSIGNED NOT NULL DEFAULT \'0\'',
                'viewable_information' => 'tinyint UNSIGNED NOT NULL DEFAULT \'0\'',
                'allow_sort' => 'enum(\'none\',\'asc\',\'desc\') NOT NULL DEFAULT \'none\'',
                'display_callback_class' => 'varchar(75) NOT NULL DEFAULT \'\'',
                'display_callback_method' => 'varchar(75) NOT NULL DEFAULT  \'\'',
                'allowed_user_group_ids' => 'blob',
                'addon_id' => 'varchar(25) NOT NULL DEFAULT \'\'',
                'field_choices_callback_class' => 'varchar(75) NOT NULL DEFAULT \'\'',
                'field_choices_callback_method' => 'varchar(75) NOT NULL DEFAULT \'\'',
                'field_callback_class' => 'varchar(75) NOT NULL DEFAULT \'\'',
                'field_callback_method' => 'varchar(75) NOT NULL DEFAULT  \'\'',
                'export_callback_class' => 'varchar(75) NOT NULL DEFAULT \'\'',
                'export_callback_method' => 'varchar(75) NOT NULL DEFAULT  \'\''
            ),
            'xf_resource_field_category' => array(
                'field_value' => 'mediumtext NULL'
            )
        );
    }

    protected function _getAddOnTableChanges()
    {
        return array(
            'Waindigo_Library' => array(
                'xf_library' => array(
                    'field_cache' => 'mediumblob NULL',
                    'custom_fields' => 'mediumblob NULL',
                    'required_fields' => 'mediumblob NULL'
                ),
                'xf_article' => array(
                    'custom_fields' => 'mediumblob NULL'
                )
            ),
            'Waindigo_SocialGroups' => array(
                'xf_social_forum' => array(
                    'custom_social_forum_fields' => 'mediumblob NULL'
                )
            )
        );
    }

    protected function _getAddOnTableChangesOnInstall()
    {
        return array(
            'XenResource' => array(
                'xf_resource_category' => array(
                    'field_cache' => 'mediumblob NULL',
                    'prefix_cache' => 'mediumblob NULL',
                    'require_prefix' => 'tinyint UNSIGNED NOT NULL DEFAULT \'0\'',
                    'featured_count' => 'smallint UNSIGNED NOT NULL DEFAULT \'0\'',
                    'category_resource_fields' => 'mediumblob NULL',
                    'required_fields' => 'mediumblob NULL'
                ),
                'xf_resource' => array(
                    'custom_resource_fields' => 'mediumblob NULL',
                    'prefix_id' => 'int UNSIGNED NOT NULL DEFAULT \'0\'',
                    'icon_date' => 'int UNSIGNED NOT NULL DEFAULT \'0\''
                )
            )
        );
    }

    protected function _getPrimaryKeys()
    {
        return array(
            'xf_thread_field_value' => array(
                'thread_id',
                'field_id'
            ),
            'xf_forum_field' => array(
                'node_id',
                'field_id'
            ),
            'xf_article_field_value' => array(
                'article_id',
                'field_id'
            ),
            'xf_article_page_field_value' => array(
                'article_page_id',
                'field_id'
            ),
            'xf_resource_field_value' => array(
                'resource_id',
                'field_id'
            ),
            'xf_social_forum_field_value' => array(
                'social_forum_id',
                'field_id'
            )
        );
    }

    protected function _getKeys()
    {
        return array(
            'xf_thread_field' => array(
                'materialized_order' => array(
                    'materialized_order'
                )
            ),
            'xf_thread_field_value' => array(
                'field_id' => array(
                    'field_id'
                )
            ),
            'xf_forum_field' => array(
                'field_id' => array(
                    'field_id'
                )
            ),
            'xf_article_field_value' => array(
                'field_id' => array(
                    'field_id'
                )
            ),
            'xf_article_page_field_value' => array(
                'field_id' => array(
                    'field_id'
                )
            ),
            'xf_resource_field' => array(
                'materialized_order' => array(
                    'materialized_order'
                ),
                'display_group_order' => array(
                    'display_group',
                    'display_order'
                )
            ),
            'xf_resource_field_value' => array(
                'field_id' => array(
                    'field_id'
                )
            ),
            'xf_social_forum_field' => array(
                'materialized_order' => array(
                    'materialized_order'
                )
            ),
            'xf_social_forum_field_value' => array(
                'field_id' => array(
                    'field_id'
                )
            )
        );
    }

    protected function _getEnumValues()
    {
        return array(
            'xf_user_field' => array(
                'field_type' => array(
                    'add' => array(
                        'callback'
                    )
                )
            )
        );
    }

    /**
     * Gets the content types to be created for this add-on.
     * See parent for explanation.
     *
     * @return array Format: [content type] => array(addon id, fields =>
     * array([field_name] => [field_value])
     */
    protected function _getContentTypes()
    {
        return array(
            'custom_field' => array(
                'addon_id' => 'Waindigo_CustomFields',
                'fields' => array(
                    'attachment_handler_class' => 'Waindigo_CustomFields_AttachmentHandler_CustomField'
                )
            )
        );
    }

    protected function _preUninstall()
    {
        $this->_db->delete('xf_user_field', "field_type = 'callback'");
    }
}
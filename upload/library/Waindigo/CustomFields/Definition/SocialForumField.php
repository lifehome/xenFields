<?php

/**
 * Custom social forum field definition.
 */
class Waindigo_CustomFields_Definition_SocialForumField extends Waindigo_CustomFields_Definition_Abstract
{

    /**
     * Gets the structure of the custom field record.
     *
     * @return array
     */
    protected function _getFieldStructure()
    {
        return array(
            'table' => 'xf_social_forum_field', 
        );
    }
}
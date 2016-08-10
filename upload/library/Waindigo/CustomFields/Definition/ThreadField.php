<?php

/**
 * Custom thread field definition.
 */
class Waindigo_CustomFields_Definition_ThreadField extends Waindigo_CustomFields_Definition_Abstract
{

    /**
     * Gets the structure of the custom field record.
     *
     * @return array
     */
    protected function _getFieldStructure()
    {
        return array(
            'table' => 'xf_thread_field', 
        );
    }
}
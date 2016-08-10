<?php

abstract class Waindigo_CustomFields_Definition_Abstract
{

    /**
     * Contains the structure returned from {@link _getFieldStructure()}.
     *
     * @var array
     */
    protected $_structure = array();

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->_structure = $this->_getFieldStructure();
    }

    /**
     * Gets the structure of the discussion record.
     * This only includes parts that are variable. Keys returned:
     * * table - name of the table (eg, xf_thread_field)
     *
     * @return array
     */
    abstract protected function _getFieldStructure();

    /**
     * Gets the full field structure array.
     * See {@link _getFieldStructure()} for
     * data returned.
     *
     * @return array
     */
    public function getFieldStructure()
    {
        return $this->_structure;
    }

    public function getFieldTableName()
    {
        return $this->_structure['table'];
    }
}
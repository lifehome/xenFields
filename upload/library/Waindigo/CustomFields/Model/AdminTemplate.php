<?php

class Waindigo_CustomFields_Model_AdminTemplate extends XenForo_Model
{

    /**
     * Appends the admin template XML for admin templates in the specified
     * custom field.
     *
     * @param DOMElement $rootNode
     * @param array $field
     */
    public function appendAdminTemplatesFieldXml(DOMElement $rootNode, array $field)
    {
        $document = $rootNode->ownerDocument;

        $titles = array();
        if ($field['export_callback_class'] && $field['export_callback_method']) {
            $error = '';
            $exportInfo = call_user_func_array(array(
                $field['export_callback_class'],
                $field['export_callback_method']
            ), array(
                $field,
                &$error
            ));
            $titles = $exportInfo['admin_templates'];
        }
        if (!empty($titles)) {
            $templates = $this->_getAdminTemplateModel()->getAdminTemplatesByTitles($titles);
            foreach ($templates as $template) {
                $templateNode = $document->createElement('template');
                $templateNode->setAttribute('title', $template['title']);
                $templateNode->setAttribute('addon_id', $template['addon_id']);
                $templateNode->appendChild(
                    XenForo_Helper_DevelopmentXml::createDomCdataSection($document, $template['template']));

                $rootNode->appendChild($templateNode);
            }
        }
    }

    /**
     * Imports admin templates.
     * It does not check for conflicts.
     *
     * @param SimpleXMLElement $xml
     */
    public function importAdminTemplatesFieldXml(SimpleXMLElement $xml)
    {
        $db = $this->_getDb();

        if ($xml->template === null) {
            return;
        }

        XenForo_Db::beginTransaction($db);

        foreach ($xml->template as $template) {
            $templateName = (string) $template['title'];

            $dw = XenForo_DataWriter::create('XenForo_DataWriter_AdminTemplate');
            $existingTemplate = $this->_getAdminTemplateModel()->getAdminTemplateByTitle($templateName);
            if ($existingTemplate) {
                $dw->setExistingData($existingTemplate);
            }
            $dw->setOption(XenForo_DataWriter_AdminTemplate::OPTION_DEV_OUTPUT_DIR, '');
            $dw->setOption(XenForo_DataWriter_AdminTemplate::OPTION_FULL_COMPILE, false);
            $dw->setOption(XenForo_DataWriter_AdminTemplate::OPTION_TEST_COMPILE, false);
            $dw->bulkSet(
                array(
                    'title' => (string) $template['title'],
                    'template' => XenForo_Helper_DevelopmentXml::processSimpleXmlCdata($template),
                    'addon_id' => (string) $template['addon_id']
                ));
            $dw->save();
        }

        XenForo_Db::commit($db);
    }

    /**
     *
     * @return XenForo_Model_AdminTemplate
     */
    protected function _getAdminTemplateModel()
    {
        return $this->getModelFromCache('XenForo_Model_AdminTemplate');
    }
}
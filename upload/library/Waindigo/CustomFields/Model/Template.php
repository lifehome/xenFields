<?php

class Waindigo_CustomFields_Model_Template extends XenForo_Model
{

    /**
     * Appends the template XML for templates in the specified custom field.
     *
     * @param DOMElement $rootNode
     * @param array $field
     */
    public function appendTemplatesFieldXml(DOMElement $rootNode, array $field)
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
            $titles = $exportInfo['templates'];
        }
        if (!empty($titles)) {
            $templates = $this->_getTemplateModel()->getTemplatesInStyleByTitles($titles);
            foreach ($templates as $template) {
                $templateNode = $document->createElement('template');
                $templateNode->setAttribute('title', $template['title']);
                $templateNode->setAttribute('addon_id', $template['addon_id']);
                $templateNode->setAttribute('version_id', $template['version_id']);
                $templateNode->setAttribute('version_string', $template['version_string']);
                $templateNode->appendChild(
                    XenForo_Helper_DevelopmentXml::createDomCdataSection($document, $template['template']));

                $rootNode->appendChild($templateNode);
            }
        }
    }

    /**
     * Imports templates.
     * It does not check for conflicts.
     *
     * @param SimpleXMLElement $xml
     */
    public function importTemplatesFieldXml(SimpleXMLElement $xml)
    {
        $db = $this->_getDb();

        if ($xml->template === null) {
            return;
        }

        XenForo_Db::beginTransaction($db);

        foreach ($xml->template as $template) {
            $templateName = (string) $template['title'];

            $dw = XenForo_DataWriter::create('XenForo_DataWriter_Template');
            $existingTemplate = $this->_getTemplateModel()->getTemplateInStyleByTitle($templateName);
            if ($existingTemplate) {
                $dw->setExistingData($existingTemplate);
            }
            $dw->setOption(XenForo_DataWriter_Template::OPTION_DEV_OUTPUT_DIR, '');
            $dw->setOption(XenForo_DataWriter_Template::OPTION_FULL_COMPILE, false);
            $dw->setOption(XenForo_DataWriter_Template::OPTION_TEST_COMPILE, false);
            $dw->setOption(XenForo_DataWriter_Template::OPTION_CHECK_DUPLICATE, false);
            $dw->setOption(XenForo_DataWriter_Template::OPTION_REBUILD_TEMPLATE_MAP, false);
            $dw->bulkSet(
                array(
                    'template' => XenForo_Helper_DevelopmentXml::processSimpleXmlCdata($template),
                    'addon_id' => (string) $template['addon_id'],
                    'style_id' => 0,
                    'version_id' => (int) $template['version_id'],
                    'version_string' => (string) $template['version_string'],
                    'title' => $templateName
                ));
            $dw->save();
        }

        XenForo_Db::commit($db);
    }

    /**
     *
     * @return XenForo_Model_Template
     */
    protected function _getTemplateModel()
    {
        return $this->getModelFromCache('XenForo_Model_Template');
    }
}
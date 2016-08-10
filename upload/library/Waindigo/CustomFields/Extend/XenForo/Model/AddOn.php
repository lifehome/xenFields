<?php

/**
 *
 * @see XenForo_Model_AddOn
 */
class Waindigo_CustomFields_Extend_XenForo_Model_AddOn extends XFCP_Waindigo_CustomFields_Extend_XenForo_Model_AddOn
{

    /**
     *
     * @see XenForo_Model_AddOn::verifyAddOnIsInstallable()
     */
    public function verifyAddOnIsInstallable($addOnData, $upgradeAddOnId = false)
    {
        $existingAddOn = parent::verifyAddOnIsInstallable($addOnData, $upgradeAddOnId);

        $addOnId = $addOnData['addon_id'];

        if ($addOnData['addon_id'] != 'XenResource') {
            return $existingAddOn;
        }

        if (!$existingAddOn || $existingAddOn['version_id'] >= 1010000) {
            return $existingAddOn;
        }

        $this->_getDb()->query('
            ALTER TABLE xf_resource_category DROP field_cache
        ');

        $this->_getDb()->query('
            ALTER TABLE xf_resource DROP custom_resource_fields
        ');

        return $existingAddOn;
    }

    /**
     *
     * @see XenForo_Model_AddOn::getAddOnXml()
     */
    public function getAddOnXml(array $addOn)
    {
        /* @var $document DOMDocument */
        $document = parent::getAddOnXml($addOn);

        $rootNode = $document->getElementsByTagName('addon')->item(0);
        $addOnId = $rootNode->attributes->getNamedItem('addon_id')->textContent;

        $dataNode = $document->createElement('user_fields');
        $this->getModelFromCache('XenForo_Model_UserField')->appendFieldsAddOnXml($dataNode, $addOnId);
        $this->_appendNodeAlphabetically($rootNode, $dataNode);

        $dataNode = $document->createElement('thread_fields');
        $this->getModelFromCache('Waindigo_CustomFields_Model_ThreadField')->appendFieldsAddOnXml($dataNode, $addOnId);
        $this->_appendNodeAlphabetically($rootNode, $dataNode);

        if (XenForo_Application::$versionId > 1020000) {
            $addOns = XenForo_Application::get('addOns');
            $isRmInstalled = !empty($addOns['XenResource']);
            $isSgInstalled = !empty($addOns['Waindigo_SocialGroups']);
        } else {
            $isRmInstalled = $this->getAddOnById('XenResource') ? true : false;
            $isSgInstalled = $this->getAddOnById('Waindigo_SocialGroups') ? true : false;
        }

        if ($isRmInstalled) {
            $dataNode = $document->createElement('resource_fields');
            $this->getModelFromCache('Waindigo_CustomFields_Model_ResourceField')->appendFieldsAddOnXml($dataNode,
                $addOnId);
            $this->_appendNodeAlphabetically($rootNode, $dataNode);
        }

        if ($isSgInstalled) {
            $dataNode = $document->createElement('social_forum_fields');
            $this->getModelFromCache('Waindigo_CustomFields_Model_SocialForumField')->appendFieldsAddOnXml($dataNode,
                $addOnId);
            $this->_appendNodeAlphabetically($rootNode, $dataNode);
        }

        return $document;
    }

    /**
     *
     * @param DOMElement $rootNode
     * @param DOMElement $newNode
     */
    protected function _appendNodeAlphabetically(DOMElement $rootNode, DOMElement $newNode)
    {
        if ($newNode->hasChildNodes()) {
            $refNode = null;
            foreach ($rootNode->childNodes as $child) {
                if ($child instanceof DOMElement && $child->tagName > $newNode->tagName) {
                    $refNode = $child;
                    break;
                }
            }
            if ($refNode) {
                $rootNode->insertBefore($newNode, $refNode);
            } else {
                $rootNode->appendChild($newNode);
            }
        }
    }

    /**
     *
     * @see XenForo_Model_AddOn::installAddOnXml()
     */
    public function installAddOnXml(SimpleXMLElement $xml, $upgradeAddOnId = false)
    {
        $installed = parent::installAddOnXml($xml, $upgradeAddOnId);

        if ($installed) {
            $addOnId = (string) $xml['addon_id'];

            if ($addOnId == 'XenResource') {
                // TODO: probably don't need to rebuild the entire add-on
                Waindigo_Install::install(array(),
                    array(
                        'addon_id' => 'Waindigo_CustomFields'
                    ));
            }
        }
    }

    /**
     *
     * @see XenForo_Model_AddOn::importAddOnExtraDataFromXml()
     */
    public function importAddOnExtraDataFromXml(SimpleXMLElement $xml, $addOnId)
    {
        parent::importAddOnExtraDataFromXml($xml, $addOnId);

        try {
            $this->getModelFromCache('XenForo_Model_UserField')->importFieldsAddOnXml($xml->user_fields, $addOnId);
            $this->getModelFromCache('Waindigo_CustomFields_Model_ThreadField')->importFieldsAddOnXml(
                $xml->thread_fields, $addOnId);
            if (XenForo_Application::$versionId > 1020000) {
                $addOns = XenForo_Application::get('addOns');
                $isRmInstalled = !empty($addOns['XenResource']);
                $isSgInstalled = !empty($addOns['Waindigo_SocialGroups']);
            } else {
                $isRmInstalled = $this->getAddOnById('XenResource') ? true : false;
                $isSgInstalled = $this->getAddOnById('Waindigo_SocialGroups') ? true : false;
            }
            if ($isRmInstalled) {
                $this->getModelFromCache('Waindigo_CustomFields_Model_ResourceField')->importFieldsAddOnXml(
                    $xml->resource_fields, $addOnId);
            }
            if ($isSgInstalled) {
                $this->getModelFromCache('Waindigo_CustomFields_Model_SocialForumField')->importFieldsAddOnXml(
                    $xml->social_forum_fields, $addOnId);
            }
        } catch (Exception $e) {
            // do nothing
        }
    }
}
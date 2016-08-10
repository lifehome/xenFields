<?php

/**
 *
 * @see XenForo_Model_Phrase
 */
class Waindigo_CustomFields_Extend_XenForo_Model_Phrase extends XFCP_Waindigo_CustomFields_Extend_XenForo_Model_Phrase
{

    /**
     * Appends the language (phrase) XML for phrases in the specified custom
     * field.
     *
     * @param DOMElement $rootNode
     * @param array $field
     */
    public function appendPhrasesFieldXml(DOMElement $rootNode, array $field)
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
            $titles = $exportInfo['phrases'];
        }
        if (!empty($titles)) {
            $phrases = $this->getMasterPhrasesByTitles($titles);
            foreach ($phrases as $phrase) {
                $phraseNode = $document->createElement('phrase');
                $phraseNode->setAttribute('title', $phrase['title']);
                if ($phrase['global_cache']) {
                    $phraseNode->setAttribute('global_cache', $phrase['global_cache']);
                }
                $phraseNode->setAttribute('version_id', $phrase['version_id']);
                $phraseNode->setAttribute('addon_id', $phrase['addon_id']);
                $phraseNode->setAttribute('version_string', $phrase['version_string']);
                $phraseNode->appendChild($document->createCDATASection($phrase['phrase_text']));
                $rootNode->appendChild($phraseNode);
            }
        }
    }

    /**
     * Gets the master (language 0) phrases with the specified titles.
     *
     * @param array $titles
     *
     * @return array Format: [title] => info
     */
    public function getMasterPhrasesByTitles($titles)
    {
        return $this->fetchAllKeyed(
            '
                SELECT *
                FROM xf_phrase
                WHERE title IN (?)
                AND language_id = 0
                ORDER BY title
            ', 'title', $titles);
    }
}
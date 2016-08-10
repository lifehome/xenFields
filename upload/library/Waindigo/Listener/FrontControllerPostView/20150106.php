<?php

abstract class Waindigo_Listener_FrontControllerPostView extends Waindigo_Listener_Template
{

    /**
     *
     * @var XenForo_FrontController
     */
    protected $_fc = null;

    protected $_routePath = null;

    /**
     *
     * @param XenForo_FrontController $fc
     * @param string $contents
     */
    public function __construct(XenForo_FrontController $fc, &$contents)
    {
        $this->_fc = $fc;
        $this->_routePath = $this->_fetchRoutePath();
        parent::__construct($contents, null);
    }
    
    // This only works on PHP 5.3+, so method should be overridden for now
    public static function frontControllerPostView(XenForo_FrontController $fc, &$contents)
    {
        if (function_exists('get_called_class')) {
            $className = get_called_class();
        } else {
            $className = get_class();
        }
        
        $contents = self::createAndRun($className, $fc, $contents);
    }

    /**
     *
     * @return true if successful, false otherwise
     * @param string $templateName
     * @param array|null $viewParams
     * @param string|null $contents
     */
    protected function _appendTemplateAtTopCtrl($templateName, $viewParams = null, &$contents = null, $after = true)
    {
        $rendered = $this->_render($templateName, $viewParams);
        
        preg_match('#<div class="topCtrl">(.*)</div>#Us', $rendered, $matches);
        
        if (isset($matches[1])) {
            if ($after) {
                $replacement = '$1' . $matches[1];
            } else {
                $replacement = $matches[1] . '$1';
            }
            $this->_contents = preg_replace('#<div class="topCtrl">(.*)</div>#Us', 
                '<div class="topCtrl">' . $replacement . '</div>', $this->_contents, 1, $count);
            if ($count)
                return true;
        }
        
        // START legacy code
        preg_match('#<h1>(.*)</h1>#Us', $rendered, $matches);
        if (isset($matches[1])) {
            $this->_contents = preg_replace('#<div class="titleBar">(.*)</div>#Us', 
                '<div class="titleBar">' . $matches[1] . '$1</div>', $this->_contents, 1, $count);
            if ($count)
                return true;
        }
        // END legacy code
        
        preg_match('#<div class="titleBar">(.*)</div>#s', $rendered, $matches);
        if (isset($matches[1])) {
            $this->_contents = preg_replace('#<div class="titleBar">(.*)</div>#Us', 
                '<div class="titleBar">' . $matches[1] . '$1</div>', $this->_contents, 1, $count);
            if ($count)
                return true;
        }
        
        return false;
    }

    /**
     *
     * @return true if successful, false otherwise
     * @param string $templateName
     * @param array|null $viewParams
     * @param string|null $contents
     */
    protected function _appendTemplateAfterTopCtrl($templateName, $viewParams = null, &$contents = null)
    {
        return $this->_appendTemplateAtTopCtrl($templateName, $viewParams, $contents, true);
    }

    /**
     *
     * @return true if successful, false otherwise
     * @param string $templateName
     * @param array|null $viewParams
     * @param string|null $contents
     */
    protected function _appendTemplateBeforeTopCtrl($templateName, $viewParams = null, &$contents = null)
    {
        return $this->_appendTemplateAtTopCtrl($templateName, $viewParams, $contents, false);
    }

    /**
     *
     * @return boolean true if response code match, false otherwise
     * @param int $responseCode
     */
    protected function _assertResponseCode($responseCode)
    {
        if ($this->_fc->getResponse()->getHttpResponseCode() != $responseCode) {
            throw new XenForo_Exception('Incorrect response code');
        }
    }

    /**
     *
     * @return string
     */
    protected function _fetchRoutePath()
    {
        return rtrim($this->_fc->getRequest()->getParam('_matchedRoutePath'), "/");
    }

    /**
     *
     * @see Waindigo_Listener_Template::_render()
     */
    protected function _render($templateName, $viewParams = null)
    {
        if (!$viewParams)
            $viewParams = $this->_fetchViewParams();
        return $this->_fc->getDependencies()
            ->createTemplateObject($templateName, $viewParams)
            ->render();
    }

    /**
     * Factory method to get the named front controller post-view listener.
     * The class must exist or be autoloadable or an exception will be thrown.
     *
     * @param string $className Class to load
     * @param XenForo_FrontController $fc
     * @param string $contents
     *
     * @return Waindigo_Listener_FrontControllerPostView
     */
    public static function create($className, XenForo_FrontController $fc, &$contents)
    {
        $createClass = XenForo_Application::resolveDynamicClass($className, 'listener_waindigo');
        if (!$createClass) {
            throw new XenForo_Exception("Invalid listener '$className' specified");
        }
        
        return new $createClass($fc, $contents);
    }

    /**
     *
     * @param string $className Class to load
     * @param XenForo_FrontController $fc
     * @param string $contents
     *
     * @return array
     */
    public static function createAndRun($className, XenForo_FrontController $fc, &$contents)
    {
        $createClass = self::create($className, $fc, $contents);
        
        if (XenForo_Application::debugMode()) {
            return $createClass->run();
        }
        try {
            return $createClass->run();
        } catch (Exception $e) {
            return $this->_contents;
        }
    }
}
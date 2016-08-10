<?php

/**
 * Route prefix handler for resource fields in the admin control panel.
 */
class Waindigo_CustomFields_Route_PrefixAdmin_ResourceFields implements XenForo_Route_Interface
{

    /**
     * Match a specific route for an already matched prefix.
     *
     * @see XenForo_Route_Interface::match()
     */
    public function match($routePath, Zend_Controller_Request_Http $request, XenForo_Router $router)
    {
        $action = $router->resolveActionWithStringParam($routePath, $request, 'field_id');
        return $router->getRouteMatch('Waindigo_CustomFields_ControllerAdmin_ResourceField', $action, 'resourceFields');
    }

    /**
     * Method to build a link to the specified page/action with the provided
     * data and params.
     *
     * @see XenForo_Route_BuilderInterface
     */
    public function buildLink($originalPrefix, $outputPrefix, $action, $extension, $data, array &$extraParams)
    {
        return XenForo_Link::buildBasicLinkWithStringParam($outputPrefix, $action, $extension, $data, 'field_id');
    }
}
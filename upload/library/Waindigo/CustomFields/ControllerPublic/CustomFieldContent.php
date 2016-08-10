<?php

class Waindigo_CustomFields_ControllerPublic_CustomFieldContent extends XenForo_ControllerPublic_Abstract
{

    /**
     *
     * @return XenForo_ControllerResponse_Redirect
     */
    public function actionIndex()
    {
        $attachmentModel = $this->_getAttachmentModel();

        $fieldAttachmentId = $this->_input->filterSingle('field_attachment_id', XenForo_Input::UINT);

        $fieldAttachment = $attachmentModel->getFieldAttachmentById($fieldAttachmentId);

        if (!$fieldAttachment) {
            return $this->responseNoPermission();
        }

        switch ($fieldAttachment['custom_field_type']) {
            case 'user':
                $userModel = $this->getModelFromCache('XenForo_Model_User');
                $user = $userModel->getUserById($fieldAttachment['content_id']);
                return $this->responseRedirect(XenForo_ControllerResponse_Redirect::RESOURCE_CANONICAL_PERMANENT,
                    XenForo_Link::buildPublicLink('members', $user));
            case 'thread':
                $threadModel = $this->getModelFromCache('XenForo_Model_Thread');
                $thread = $threadModel->getThreadById($fieldAttachment['content_id']);
                return $this->responseRedirect(XenForo_ControllerResponse_Redirect::RESOURCE_CANONICAL_PERMANENT,
                    XenForo_Link::buildPublicLink('threads', $thread));
            case 'post':
                $postModel = $this->getModelFromCache('XenForo_Model_Post');
                $post = $postModel->getPostById($fieldAttachment['content_id']);
                return $this->responseRedirect(XenForo_ControllerResponse_Redirect::RESOURCE_CANONICAL_PERMANENT,
                    XenForo_Link::buildPublicLink('posts', $post));
            case 'resource':
                $resourceModel = $this->getModelFromCache('XenResource_Model_Resource');
                $resource = $resourceModel->getResourceById($fieldAttachment['content_id']);
                return $this->responseRedirect(XenForo_ControllerResponse_Redirect::RESOURCE_CANONICAL_PERMANENT,
                    XenForo_Link::buildPublicLink('resources', $resource));
            case 'social_forum':
                $socialForumModel = $this->getModelFromCache('Waindigo_SocialGroups_Model_SocialForum');
                $socialForum = $socialForumModel->getSocialForumById($fieldAttachment['content_id']);
                return $this->responseRedirect(XenForo_ControllerResponse_Redirect::RESOURCE_CANONICAL_PERMANENT,
                    XenForo_Link::buildPublicLink('social-forums', $socialForum));
        }

        return $this->responseNoPermission();
    }

    /**
     *
     * @return Waindigo_CustomFields_Model_Attachment
     */
    protected function _getAttachmentModel()
    {
        return $this->getModelFromCache('Waindigo_CustomFields_Model_Attachment');
    }
}
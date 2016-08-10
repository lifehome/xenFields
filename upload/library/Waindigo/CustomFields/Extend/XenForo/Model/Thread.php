<?php

/**
 *
 * @see XenForo_Model_Thread
 */
class Waindigo_CustomFields_Extend_XenForo_Model_Thread extends XFCP_Waindigo_CustomFields_Extend_XenForo_Model_Thread
{

    const FETCH_THREAD_FIELD_VALUE = 0x01;

    /**
     *
     * @see XenForo_Model_Thread::prepareThreadFetchOptions()
     */
    public function prepareThreadFetchOptions(array $fetchOptions)
    {
        $selectFields = '';
        $joinTables = '';
        $orderBy = '';

        if (!empty($fetchOptions['order'])) {
            switch ($fetchOptions['order']) {
                case 'field_value':
                    $orderBy = 'thread_field_value.field_value';
                    $fetchOptions['waindigo_join'] = self::FETCH_THREAD_FIELD_VALUE;
                    break;
            }
            if ($orderBy) {
                if (!isset($fetchOptions['orderDirection']) || $fetchOptions['orderDirection'] == 'desc') {
                    $orderBy .= ' DESC';
                } else {
                    $orderBy .= ' ASC';
                }
            }
        }

        if (!empty($fetchOptions['waindigo_join'])) {
            if ($fetchOptions['waindigo_join'] & self::FETCH_THREAD_FIELD_VALUE) {
                $selectFields .= ',
                    thread_field_value.field_id, thread_field_value.field_value';
                $joinTables .= '
                    LEFT JOIN xf_thread_field_value AS thread_field_value ON
                    (thread_field_value.thread_id = thread.thread_id)';
            }
        }

        $threadFetchOptions = parent::prepareThreadFetchOptions($fetchOptions);

        return array(
            'selectFields' => $threadFetchOptions['selectFields'] . $selectFields,
            'joinTables' => $joinTables . $threadFetchOptions['joinTables'],
            'orderClause' => ($orderBy ? "ORDER BY $orderBy" : $threadFetchOptions['orderClause'])
        );
    }

    /**
     *
     * @see XenForo_Model_Thread::prepareThreadConditions()
     */
    public function prepareThreadConditions(array $conditions, array &$fetchOptions)
    {
        $sqlConditions = array();
        $db = $this->_getDb();

        $sqlConditions[] = parent::prepareThreadConditions($conditions, $fetchOptions);

        if (isset($conditions['field_id'])) {
            $sqlConditions[] = 'thread_field_value.field_id = ' . $db->quote($conditions['field_id']);
            $fetchOptions['waindigo_join'] = self::FETCH_THREAD_FIELD_VALUE;
        }

        if (isset($conditions['field_value'])) {
            $sqlConditions[] = 'thread_field_value.field_value = ' . $db->quote($conditions['field_value']);
            $fetchOptions['waindigo_join'] = self::FETCH_THREAD_FIELD_VALUE;
        }

        if (isset($conditions['field_values']) && !empty($conditions['field_values'])) {
            $sqlConditions[] = 'thread_field_value.field_value IN (' . $db->quote($conditions['field_values']) . ')';
            $fetchOptions['waindigo_join'] = self::FETCH_THREAD_FIELD_VALUE;
        }

        return $this->getConditionsForClause($sqlConditions);
    }

    /**
     * Determines if custom thread fields be edited with the given permissions.
     * This does not check thread viewing permissions.
     *
     * @param array $thread Info about the thread
     * @param array $forum Info about the forum the thread is in
     * @param string $errorPhraseKey Returned phrase key for a specific error
     * @param array|null $nodePermissions
     * @param array|null $viewingUser
     *
     * @return boolean
     */
    public function canEditThreadFields(array $thread, array $forum, &$errorPhraseKey = '', array $nodePermissions = null,
        array $viewingUser = null)
    {
        $this->standardizeViewingUserReferenceForNode($thread['node_id'], $viewingUser, $nodePermissions);

        if (!$viewingUser['user_id']) {
            return false;
        }

        if (!$thread['discussion_open'] &&
             !$this->canLockUnlockThread($thread, $forum, $errorPhraseKey, $nodePermissions, $viewingUser)) {
            $errorPhraseKey = 'you_may_not_perform_this_action_because_discussion_is_closed';
            return false;
        }

        if (XenForo_Permission::hasContentPermission($nodePermissions, 'manageAnyThread')) {
            return true;
        }

        if ($thread['user_id'] == $viewingUser['user_id'] &&
             XenForo_Permission::hasContentPermission($nodePermissions, 'editOwnPost')) {
            $editLimit = XenForo_Permission::hasContentPermission($nodePermissions, 'editOwnPostTimeLimit');

            if ($editLimit != -1 && (!$editLimit || $thread['post_date'] < XenForo_Application::$time - 60 * $editLimit)) {
                $errorPhraseKey = array(
                    'message_edit_time_limit_expired',
                    'minutes' => $editLimit
                );
                return false;
            }

            if (empty($forum['allow_posting'])) {
                $errorPhraseKey = 'you_may_not_perform_this_action_because_forum_does_not_allow_posting';
                return false;
            }

            return XenForo_Permission::hasContentPermission($nodePermissions, 'editOwnThreadFields');
        }

        return false;
    }
}
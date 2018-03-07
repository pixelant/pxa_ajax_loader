<?php
declare(strict_types=1);

namespace Pixelant\PxaAjaxLoader\Hooks;

use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class DataHandler
 * @package Pixelant\PxaAjaxLoader\Hooks
 */
class DataHandler
{
    /**
     * Need to set ajax container from colPos added in PageLayoutViewHook
     * for drag and drop function
     *
     * @param array $fields
     * @param string $table
     * @param $uid
     * @param \TYPO3\CMS\Core\DataHandling\DataHandler $pObj
     */
    public function processDatamap_preProcessFieldArray(
        array &$fields,
        string $table,
        $uid,
        \TYPO3\CMS\Core\DataHandling\DataHandler $pObj
    ) {
        if ($table === 'tt_content' && isset($fields['colPos']) && is_string($fields['colPos'])) {
            $ajaxContainer = $this->determinateAjaxContainer($fields['colPos']);

            // If it's drag & drop and there was ajax container
            if ($ajaxContainer > 0) {
                $fields['colPos'] = PageLayoutViewHook::COL_POS;
                $fields[PageLayoutViewHook::DB_FIELD_CONTAINER_NAME] = $ajaxContainer;
            } else {
                // Reset container
                $fields[PageLayoutViewHook::DB_FIELD_CONTAINER_NAME] = 0;
            }
        }
    }

    /**
     * This will fix container ID on pastAfter context menu action
     *
     * @param string $command
     * @param string $table
     * @param $uid
     * @param $value
     * @param \TYPO3\CMS\Core\DataHandling\DataHandler $pObj
     */
    public function processCmdmap_preProcess(
        string $command,
        string $table,
        $uid,
        $value,
        \TYPO3\CMS\Core\DataHandling\DataHandler $pObj
    ) {
        if ($command === 'move' && $table === 'tt_content') {
            $value = (int)$value;

            if ($value < 0) {
                // Copy ajax container from target element
                $ajaxContainer = $this->getAjaxContainerValueForRecord(abs($value));

                $connection = $this->getConnection('tt_content');
                $connection->update(
                    'tt_content',
                    [PageLayoutViewHook::DB_FIELD_CONTAINER_NAME => $ajaxContainer],
                    ['uid' => $uid],
                    [Connection::PARAM_INT]
                );
            }
        }
    }

    /**
     * Get ajax container
     *
     * @param string $colPos
     * @return int
     */
    protected function determinateAjaxContainer(string $colPos): int
    {
        list($colPos, $ajaxContainer) = GeneralUtility::intExplode('|', $colPos, true, 2);

        if ($colPos === PageLayoutViewHook::COL_POS && $ajaxContainer > 0) {
            return $ajaxContainer;
        }

        return 0;
    }

    /**
     * Get container value
     *
     * @param int $uid
     * @return int
     */
    protected function getAjaxContainerValueForRecord(int $uid): int
    {
        $row = BackendUtility::getRecord(
            'tt_content',
            $uid,
            PageLayoutViewHook::DB_FIELD_CONTAINER_NAME
        );

        if (is_array($row)) {
            return (int)$row[PageLayoutViewHook::DB_FIELD_CONTAINER_NAME];
        }

        return 0;
    }

    /**
     * Get connection builder
     *
     * @param string $table
     * @return Connection
     */
    protected function getConnection(string $table): Connection
    {
        return GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable($table);
    }
}

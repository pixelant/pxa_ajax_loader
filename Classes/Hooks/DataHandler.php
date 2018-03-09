<?php
declare(strict_types=1);

namespace Pixelant\PxaAjaxLoader\Hooks;

use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Database\Query\Restriction\BackendWorkspaceRestriction;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class DataHandler
 * @package Pixelant\PxaAjaxLoader\Hooks
 */
class DataHandler implements SingletonInterface
{
    /**
     * Keep old PID value of container
     *
     * @var array
     */
    protected $moveAjaxContainerPid = [];

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
    )
    {
        if ($table === 'tt_content' && isset($fields['colPos']) && is_string($fields['colPos'])) {
            $ajaxContainer = $this->determinateAjaxContainer($fields['colPos']);

            // If it's drag & drop and there was ajax container
            if ($ajaxContainer > 0) {
                $fields['colPos'] = PageLayoutViewHook::COL_POS;
                $fields[PageLayoutViewHook::DB_FIELD_CONTAINER_NAME] = $ajaxContainer;
            } elseif (!isset($fields[PageLayoutViewHook::DB_FIELD_CONTAINER_NAME])) {
                // Reset container
                $fields[PageLayoutViewHook::DB_FIELD_CONTAINER_NAME] = 0;
            }
        }
    }

    /**
     * This will fix container ID on pastAfter context menu action and move container action
     *
     * @param string $command
     * @param string $table
     * @param int $uid
     * @param $value
     * @param \TYPO3\CMS\Core\DataHandling\DataHandler $pObj
     */
    public function processCmdmap_preProcess(
        string $command,
        string $table,
        $uid,
        $value,
        \TYPO3\CMS\Core\DataHandling\DataHandler $pObj
    )
    {
        if ($command === 'move' && $table === 'tt_content') {
            $value = (int)$value;

            $record = BackendUtility::getRecord('tt_content', $uid, 'pid,CType,list_type');

            if ($record['CType'] === 'list'
                && $record['list_type'] === 'pxaajaxloader_loader') {
                $this->moveAjaxContainerPid[$uid] = $record['pid'];
            }

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
     * If ajax container copied, copy all it's elements
     * If moved, set new pid for elements
     *
     * @param string $command
     * @param string $table
     * @param $uid
     * @param $value
     * @param \TYPO3\CMS\Core\DataHandling\DataHandler $pObj
     */
    public function processCmdmap_postProcess(
        string $command,
        string $table,
        $uid,
        $value,
        \TYPO3\CMS\Core\DataHandling\DataHandler $pObj
    )
    {
        if ($command === 'copy' && $table === 'tt_content') {
            // Copy conteiner elements to new one
            $record = BackendUtility::getRecord('tt_content', $uid);

            if ($record['CType'] !== 'list'
                || $record['list_type'] !== 'pxaajaxloader_loader') {
                return;
            }

            $procId = $pObj->copyMappingArray[$table][$uid];

            $statement = $this->getAjaxContainerElements($record);
            $contentRows = [];
            $cmd = [];

            while ($row = $statement->fetch()) {
                $cmd['tt_content'][$row['uid']]['copy'] = (int)$value;
                $contentRows[] = $row['uid'];
            }

            if (!empty($cmd)) {
                /** @var \TYPO3\CMS\Core\DataHandling\DataHandler $dataHandler */
                $dataHandler = GeneralUtility::makeInstance(\TYPO3\CMS\Core\DataHandling\DataHandler::class);
                $dataHandler->start([], $cmd);
                $dataHandler->process_cmdmap();

                $copiesUids = [];
                foreach ($contentRows as $contentRow) {
                    $copiesUids[] = $dataHandler->copyMappingArray_merged[$table][$contentRow];
                }
                if (!empty($copiesUids)) {
                    $queryBuilder = $this->getQueryBuilder($table);
                    $queryBuilder
                        ->update($table)
                        ->where(
                            $queryBuilder->expr()->in(
                                'uid',
                                $queryBuilder->createNamedParameter(
                                    $copiesUids,
                                    Connection::PARAM_INT_ARRAY
                                )
                            )
                        )
                        ->set(PageLayoutViewHook::DB_FIELD_CONTAINER_NAME, $procId)
                        ->set('colPos', PageLayoutViewHook::COL_POS)
                        ->execute();
                }
            }
        } elseif ($command === 'move' && $table === 'tt_content') {
            // Move container elements within container
            $record = BackendUtility::getRecord('tt_content', $uid);

            if ($record['CType'] !== 'list'
                || $record['list_type'] !== 'pxaajaxloader_loader') {
                return;
            }

            $newPid = $record['pid'];

            // Restore old pid to get elements
            $record['pid'] = $this->moveAjaxContainerPid[$uid];
            // Move to new pid
            $statement = $this->getAjaxContainerElements($record);
            $cmd = [];

            while ($row = $statement->fetch()) {
                $cmd['tt_content'][$row['uid']]['move'] = $newPid;
            }

            if (!empty($cmd)) {
                /** @var \TYPO3\CMS\Core\DataHandling\DataHandler $dataHandler */
                $dataHandler = GeneralUtility::makeInstance(\TYPO3\CMS\Core\DataHandling\DataHandler::class);
                $dataHandler->start([], $cmd);
                $dataHandler->process_cmdmap();
            }
        } elseif (($command === 'copyToLanguage' || $command === 'localize') && $table === 'tt_content') {
            // Copy to language container elements within container
            $record = BackendUtility::getRecord('tt_content', $uid);

            if ($record['CType'] !== 'list'
                || $record['list_type'] !== 'pxaajaxloader_loader') {
                return;
            }

            $statement = $this->getAjaxContainerElements($record);
            $cmd = [];
            $contentRows = [];

            while ($row = $statement->fetch()) {
                $cmd['tt_content'][$row['uid']][$command] = $value;
                $contentRows[] = $row;
            }

            if (!empty($cmd)) {
                /** @var \TYPO3\CMS\Core\DataHandling\DataHandler $dataHandler */
                $dataHandler = GeneralUtility::makeInstance(\TYPO3\CMS\Core\DataHandling\DataHandler::class);
                $dataHandler->start([], $cmd);
                $dataHandler->process_cmdmap();

                if ($command === 'copyToLanguage') {
                    $field = $GLOBALS['TCA']['tt_content']['ctrl']['translationSource'];
                } elseif ($command === 'localize') {
                    $field = $GLOBALS['TCA']['tt_content']['ctrl']['transOrigPointerField'];
                }
                $value = (int)$value;

                $localizedContainer = $this->getContentLocalization($record, $field ?? '', $value);
                $localizedElementsUids = [];

                foreach ($contentRows as $contentRow) {
                    $localizedElement = $this->getContentLocalization($contentRow, $field ?? '', $value);
                    if ($localizedElement !== false) {
                        $localizedElementsUids[] = $localizedElement['uid'];
                    }
                }
                if (!empty($localizedElementsUids)) {
                    $queryBuilder = $this->getQueryBuilder($table);
                    $queryBuilder
                        ->update($table)
                        ->where(
                            $queryBuilder->expr()->in(
                                'uid',
                                $queryBuilder->createNamedParameter(
                                    $localizedElementsUids,
                                    Connection::PARAM_INT_ARRAY
                                )
                            )
                        )
                        ->set(PageLayoutViewHook::DB_FIELD_CONTAINER_NAME, $localizedContainer['uid'])
                        ->set('colPos', PageLayoutViewHook::COL_POS)
                        ->execute();
                }
            }
        }
    }

    /**
     * If plugin was delete, delete children content
     *
     * @param string $table
     * @param int $id
     * @param array $recordToDelete
     */
    public function processCmdmap_deleteAction(string $table, $id, array $recordToDelete)
    {
        if ($table === 'tt_content'
            && $recordToDelete['CType'] === 'list'
            && $recordToDelete['list_type'] === 'pxaajaxloader_loader'
        ) {
            $cmd = [];
            $statement = $this->getAjaxContainerElements($recordToDelete);

            while ($row = $statement->fetch()) {
                $cmd['tt_content'][$row['uid']]['delete'] = true;
            }

            if (!empty($cmd)) {
                /** @var \TYPO3\CMS\Core\DataHandling\DataHandler $dataHandler */
                $dataHandler = GeneralUtility::makeInstance(\TYPO3\CMS\Core\DataHandling\DataHandler::class);
                $dataHandler->start([], $cmd);
                $dataHandler->process_cmdmap();
            }
        }
    }

    /**
     * Get elements for container
     *
     * @param array $ajaxContainerRow
     * @return \Doctrine\DBAL\Driver\Statement|int
     */
    protected function getAjaxContainerElements(array $ajaxContainerRow)
    {
        $queryBuilder = $this->getQueryBuilder('tt_content');
        $queryBuilder->getRestrictions()->removeAll()
            ->add(GeneralUtility::makeInstance(DeletedRestriction::class))
            ->add(GeneralUtility::makeInstance(BackendWorkspaceRestriction::class));

        return $queryBuilder
            ->select('uid', 'pid')
            ->from('tt_content')
            ->where(
                $queryBuilder->expr()->eq(
                    PageLayoutViewHook::DB_FIELD_CONTAINER_NAME,
                    $queryBuilder->createNamedParameter($ajaxContainerRow['uid'], Connection::PARAM_INT)
                ),
                $queryBuilder->expr()->eq(
                    'sys_language_uid',
                    $queryBuilder->createNamedParameter($ajaxContainerRow['sys_language_uid'], Connection::PARAM_INT)
                )
            )
            ->execute();
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
     * Fetches the localization for a given content element.
     *
     * @param array $originRow
     * @param string $translationPointerField
     * @param int $language
     * @return array|bool Localized record or false on fail
     */
    public function getContentLocalization(array $originRow, string $translationPointerField, int $language)
    {
        $queryBuilder = $this->getQueryBuilder('tt_content');
        $queryBuilder->getRestrictions()
            ->removeAll()
            ->add(GeneralUtility::makeInstance(DeletedRestriction::class))
            ->add(GeneralUtility::makeInstance(BackendWorkspaceRestriction::class));

        $queryBuilder->select('*')
            ->from('tt_content')
            ->where(
                $queryBuilder->expr()->eq(
                    $translationPointerField,
                    $queryBuilder->createNamedParameter((int)$originRow['uid'], \PDO::PARAM_INT)
                ),
                $queryBuilder->expr()->eq(
                    'pid',
                    $queryBuilder->createNamedParameter((int)$originRow['pid'], \PDO::PARAM_INT)
                ),
                $queryBuilder->expr()->eq(
                    $GLOBALS['TCA']['tt_content']['ctrl']['languageField'],
                    $queryBuilder->createNamedParameter($language, \PDO::PARAM_INT)
                )
            )
            ->setMaxResults(1);

        $result = $queryBuilder->execute()->fetchAll();

        return is_array($result) ? $result[0] : false;
    }

    /**
     * Get query builder
     *
     * @param string $table
     * @return QueryBuilder
     */
    protected function getQueryBuilder(string $table): QueryBuilder
    {
        return GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($table);
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

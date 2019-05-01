<?php

namespace Fab\Messenger\Controller;

/*
 * This file is part of the Fab/Mailing project under GPLv2 or later.
 *
 * For the full copyright and license information, please read the
 * LICENSE.md file that was distributed with this source code.
 */

use Fab\Messenger\Domain\Model\Message;
use Fab\Messenger\Domain\Repository\QueueRepository;
use Fab\Messenger\Queue\QueueManager;
use Fab\Messenger\Service\SenderProvider;
use Fab\Messenger\Utility\Algorithms;
use Fab\Vidi\Domain\Model\Content;
use Fab\Vidi\Persistence\MatcherObjectFactory;
use Fab\Vidi\Service\ContentService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Lang\LanguageService;

/**
 * Class MessageQueueController
 */
class MessageQueueController extends ActionController
{

    /**
     * @var string
     */
    protected $tableName = 'tx_messenger_domain_model_queue';

    /**
     * @param array $matches
     * @return string
     */
    public function confirmAction(array $matches = array()): string
    {
        // Instantiate the Matcher object according different rules.
        $matcher = MatcherObjectFactory::getInstance()->getMatcher($matches, $this->tableName);

        // Fetch objects via the Content Service.
        $contentObjects = $this->getContentService()->findBy($matcher)->getObjects();
        $numberOfRecipients = count($contentObjects);

        $label = $numberOfRecipients > 1
            ? $this->getLanguageService()->sL('LLL:EXT:messenger/Resources/Private/Language/locallang.xlf:send.messages.sure?')
            : $this->getLanguageService()->sL('LLL:EXT:messenger/Resources/Private/Language/locallang.xlf:send.message.sure?');

        return sprintf($label, $numberOfRecipients);
    }

    /**
     * @param array $matches
     * @return string
     */
    public function dequeueAction(array $matches = array()): string
    {
        // Instantiate the Matcher object according different rules.
        $matcher = MatcherObjectFactory::getInstance()->getMatcher($matches, $this->tableName);

        // Fetch objects via the Content Service.
        $contentObjects = $this->getContentService()->findBy($matcher)->getObjects();

        $numberOfSentEmails = 0;
        $numberOfRecipients = count($contentObjects);

        foreach ($contentObjects as $contentObject) {
            $isSent = $this->getQueueManager()->dequeueOne($contentObject['uid']);
            if ($isSent) {
                $numberOfSentEmails++;
            }
        }

        return sprintf(
            '%s %s / %s. %s',
            $this->getLanguageService()->sL('LLL:EXT:messenger/Resources/Private/Language/locallang.xlf:message.success'),
            $numberOfSentEmails,
            $numberOfRecipients,
            $numberOfSentEmails !== $numberOfRecipients
                ? $this->getLanguageService()->sL('LLL:EXT:messenger/Resources/Private/Language/locallang.xlf:message.invalidEmails')
                : ''
        );
    }

    /**
     * @return LanguageService
     */
    protected function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }

    /**
     * @return object|QueueManager
     */
    protected function getQueueManager(): QueueManager
    {
        return GeneralUtility::makeInstance(QueueManager::class);
    }

    /**
     * @return ContentService|object
     */
    protected function getContentService()
    {
        return GeneralUtility::makeInstance(ContentService::class, $this->tableName);
    }

}
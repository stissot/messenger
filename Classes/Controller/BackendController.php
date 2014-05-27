<?php
namespace Vanilla\Messenger\Controller;
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2014 Fabien Udriot <fabien.udriot@typo3.org>
 *
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/
use Vanilla\Messenger\Utility\BackendUserPreference;

/**
 * A controller for the BE module.
 *
 * @deprecated
 * @todo remove me
 */
class BackendController extends \TYPO3\CMS\Extbase\Mvc\Controller\ActionController {

	/**
	 * @var \Vanilla\Messenger\MessengerInterface\ListableInterface
	 */
	protected $listManager;

	/**
	 * @var \Vanilla\Messenger\Formatter\Recipient
	 * @inject
	 */
	protected $recipientFormatter;

	/**
	 * @var \Vanilla\Messenger\Domain\Repository\MessageTemplateRepository
	 * @inject
	 */
	protected $messageTemplateRepository;

	/**
	 * Initializes the controller before invoking an action method.
	 * Override this method to solve tasks which all actions have in
	 * common.
	 *
	 * @return void
	 */
	public function initializeAction() {

		try {
			$this->listManager = \Vanilla\Messenger\ListManager\Factory::getInstance();

			/** @var $listManagerValidator Vanilla\Messenger\Validator\ListManagerValidator */
			$listManagerValidator = $this->objectManager->get('Vanilla\Messenger\Validator\ListManagerValidator');
			$listManagerValidator->validate($this->listManager);
		} catch(Exception $e ) {
			$this->flashMessageContainer->add('List Manager validation error: ' . $e->getMessage(), '', t3lib_FlashMessage::ERROR);
			$this->redirect('list', 'ListManager');
		}
	}

	/**
	 * send a message
	 *
	 * @param int $messageTemplateUid
	 * @param string $recipientUid
	 * @return string
	 */
	public function sendMessageAction($messageTemplateUid = 0, $recipientUid = '') {

		$result = 'The message could not be sent!';
		$status = 'error';
		if ($messageTemplateUid > 0) {

			$recipients = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',', $recipientUid);
			$mapping = $this->listManager->getMapping();

			$result = count($recipients);
			$status = 'success';

			foreach ($recipients as $recipientUid) {
				$recipient = $this->listManager->findByUid($recipientUid);

				/** @var $message \Vanilla\Messenger\Domain\Model\Message */
				$message = $this->objectManager->get('Vanilla\Messenger\Domain\Model\Message');
				$isSent = $message->setMessageTemplate($messageTemplateUid)
					->setTo($this->recipientFormatter->format($recipient, $mapping))
					->setMarkers($recipient)
					->send();

				// Block the loop if anything goes wrong.
				if (! $isSent) {
					$result = sprintf('The message could not be sent for recipient uid %s. It could be more error besides...', $recipientUid);
					$status = 'error';
					break;
				}
			}
		}
		$this->request->setFormat('json'); // I would have expected to send a json header... but not the case.
		header("Content-Type: text/json");
		return json_encode(array('message' => $result, 'status' => $status));
	}

	/**
	 * send a message for testing
	 *
	 * @param int $messageTemplateUid
	 * @param string $testEmail
	 * @return void
	 */
	public function sendMessageTestAction($messageTemplateUid = 0, $testEmail = '') {

		$result = 'The message could not be sent! Missing email?';
		$status = 'error';
		if ($messageTemplateUid > 0 && $testEmail != '') {

			/** @var $message \Vanilla\Messenger\Domain\Model\Message */
			$message = $this->objectManager->get('Vanilla\Messenger\Domain\Model\Message');
			$isSent = $message->setMessageTemplate($messageTemplateUid)
				->setTo($testEmail)
				->send();

			// value "1" corresponds to the number of email sent
			$result = $isSent ? '1' : 'The message could not be sent! Contact an administrator.';
			$status = $isSent ? 'success' : $status;

			// save email address as preference
			BackendUserPreference::set('messenger_testing_email', $testEmail);
		}
		$this->request->setFormat('json'); // I would have expected to send a json header... but not the case.
		header("Content-Type: text/json");
		return json_encode(array('message' => $result, 'status' => $status));
	}

	/**
	 * @param \Vanilla\Messenger\QueryElement\Matcher $matcher
	 * @param \Vanilla\Messenger\QueryElement\Order $order
	 * @param \Vanilla\Messenger\QueryElement\Pager $pager
	 * @return void
	 * @validate $matcher \Vanilla\Messenger\Domain\Validator\MatcherValidator
	 */
	public function indexAction(\Vanilla\Messenger\QueryElement\Matcher $matcher = NULL, \Vanilla\Messenger\QueryElement\Order $order = NULL, \Vanilla\Messenger\QueryElement\Pager $pager = NULL) {

		$matcher = $matcher === NULL ? $this->objectManager->get('Vanilla\Messenger\QueryElement\Matcher') : $matcher;
		$order = $order === NULL ? $this->objectManager->get('Vanilla\Messenger\QueryElement\Order') : $order;
		$pager = $pager === NULL ? $this->objectManager->get('Vanilla\Messenger\QueryElement\Pager') : $pager;

		$messageIdentifier = BackendUserPreference::get('messenger_message_template');
		$messageTemplate = $this->messageTemplateRepository->findByUid($messageIdentifier);

		$this->view->assign('recipients', $this->listManager->findBy($matcher, $order, $pager->getLimit(), $pager->getOffset()));
		$this->view->assign('filters', $this->listManager->getFilters());
		$this->view->assign('messageTemplate', $messageTemplate);
		$this->view->assign('matcher', $matcher);
		$this->view->assign('order', $order);
		$this->view->assign('pager', $pager);
	}
}

<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2017 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Polls\Controller;

use OCA\Polls\Db\Poll;
use OCA\Polls\Model\Acl as Acl;
use OCA\Polls\Model\Settings\AppSettings;
use OCA\Polls\Service\MailService;
use OCA\Polls\Service\OptionService;
use OCA\Polls\Service\PollService;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IRequest;
use OCP\Server;

/**
 * @psalm-api
 */
class PollController extends BaseController {
	public function __construct(
		string $appName,
		IRequest $request,
		private Acl $acl,
		private MailService $mailService,
		private OptionService $optionService,
		private PollService $pollService,
	) {
		parent::__construct($appName, $request);
	}

	/**
	 * Get list of polls
	 */
	#[NoAdminRequired]
	public function list(): JSONResponse {
		return $this->response(function () {
			$appSettings = Server::get(AppSettings::class);
			return [
				'list' => $this->pollService->list(),
				'permissions' => [
					'pollCreationAllowed' => $appSettings->getPollCreationAllowed(),
					'comboAllowed' => $appSettings->getComboAllowed(),
				],
			];
		});
	}

	/**
	 * get complete poll
	 * @param int $pollId Poll id
	 */
	#[NoAdminRequired]
	public function get(int $pollId): JSONResponse {
		return $this->response(fn () => [
			'poll' => $this->pollService->get($pollId),
			'acl' => $this->acl,
		]);
	}

	/**
	 * Add poll
	 * @param string $title Poll title
	 * @param string $type Poll type ('datePoll', 'textIndPoll','textRankPoll')
	 */
	#[NoAdminRequired]
	public function add(string $type, string $title): JSONResponse {
		return $this->responseCreate(fn () => $this->pollService->add($type, $title));
	}

	/**
	 * Update poll configuration
	 * @param int $pollId Poll id
	 * @param array $poll poll config
	 */
	#[NoAdminRequired]
	public function update(int $pollId, array $poll): JSONResponse {
		return $this->response(fn () => [
			'poll' => $this->pollService->update($pollId, $poll),
			'acl' => $this->acl,
		]);
	}

	/**
	 * Send confirmation mails
	 * @param int $pollId Poll id
	 */
	#[NoAdminRequired]
	public function sendConfirmation(int $pollId): JSONResponse {
		return $this->response(fn () => [
			'confirmations' => $this->mailService->sendConfirmations($pollId),
		]);
	}

	/**
	 * Switch deleted status (move to deleted polls)
	 * @param int $pollId Poll id
	 */
	#[NoAdminRequired]
	public function toggleArchive(int $pollId): JSONResponse {
		return $this->response(fn () => $this->pollService->toggleArchive($pollId));
	}

	/**
	 * Delete poll
	 * @param int $pollId Poll id
	 */
	#[NoAdminRequired]

	public function delete(int $pollId): JSONResponse {
		return $this->responseDeleteTolerant(fn () => $this->pollService->delete($pollId));
	}

	/**
	 * Close poll
	 * @param int $pollId Poll id
	 */
	#[NoAdminRequired]
	public function close(int $pollId): JSONResponse {
		return $this->response(fn () => [
			'poll' => $this->pollService->close($pollId),
			'acl' => $this->acl,
		]);
	}

	/**
	 * Reopen poll
	 * @param int $pollId Poll id
	 */
	#[NoAdminRequired]
	public function reopen(int $pollId): JSONResponse {
		return $this->response(fn () => [
			'poll' => $this->pollService->reopen($pollId),
			'acl' => $this->acl,
		]);
	}

	/**
	 * Clone poll
	 * @param int $pollId Poll id
	 */
	#[NoAdminRequired]
	public function clone(int $pollId): JSONResponse {
		return $this->response(fn () => $this->clonePoll($pollId));
	}

	private function clonePoll(int $pollId): JSONResponse {
		$poll = $this->pollService->clone($pollId);
		$this->optionService->clone($pollId, $poll->getId());
		return $this->get($pollId);
	}

	/**
	 * Transfer polls between users
	 * @param string $sourceUser User to transfer polls from
	 * @param string $targetUser User to transfer polls to
	 */
	public function transferPolls(string $sourceUser, string $targetUser): JSONResponse {
		return $this->response(fn () => $this->pollService->transferPolls($sourceUser, $targetUser));
	}

	/**
	 * Collect email addresses from particitipants
	 * @param int $pollId Poll id
	 */
	#[NoAdminRequired]
	public function getParticipantsEmailAddresses(int $pollId): JSONResponse {
		return $this->response(fn () => $this->pollService->getParticipantsEmailAddresses($pollId));
	}
}

<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Polls\Reference;

use OCA\Polls\AppInfo\Application;
use OCA\Polls\Exceptions\ForbiddenException;
use OCA\Polls\Exceptions\NotFoundException;
use OCA\Polls\Service\PollService;
use OCP\Collaboration\Reference\IReference;
use OCP\Collaboration\Reference\IReferenceProvider;
use OCP\Collaboration\Reference\Reference;
use OCP\IL10N;
use OCP\IURLGenerator;

class PollReferenceProvider implements IReferenceProvider {

	public function __construct(
        private PollService $pollService,
		private IURLGenerator $urlGenerator,
		private IL10N $l10n,
		private ?string $userId
        ) {
        }

	/**
	 * @inheritDoc
	 */
	public function matchReference(string $referenceText): bool {
		$start = $this->urlGenerator->getAbsoluteURL('/apps/' . Application::APP_ID);
		$startIndex = $this->urlGenerator->getAbsoluteURL('/index.php/apps/' . Application::APP_ID);

		// link example: https://nextcloud.local/index.php/apps/polls/vote/100
		$noIndexMatch = preg_match('/^' . preg_quote($start, '/') . '?\/vote\/[0-9]+$/', $referenceText) === 1;
		$indexMatch = preg_match('/^' . preg_quote($startIndex, '/') . '?\/vote\/[0-9]+$/', $referenceText) === 1;

		return $noIndexMatch || $indexMatch;
	}

	/**
	 * @inheritDoc
	 */
	public function resolveReference(string $referenceText): ?IReference {
		if ($this->matchReference($referenceText)) {
			$pollId = $this->getPollId($referenceText);
			if ($pollId !== null) {
				try {
					$poll = $this->pollService->get($pollId); //->jsonSerialize();
				} catch (ForbiddenException $e) {
					// Skip throwing if user has no permissions
					return null;
				} catch (NotFoundException $e) {
					return $this->gonePollReference($pollId);
				}
				/** @var IReference $reference */
				$reference = new Reference($referenceText);
				$reference->setTitle($this->l10n->t('Poll') . ': ' . $poll->getTitle());
				$ownerDisplayName = $poll->getUser()->getDisplayName();
				// $reference->setDescription($this->l10n->t('By %1$s', [$ownerDisplayName]));
				$reference->setDescription($poll->getDescription());
				$imageUrl = $this->urlGenerator->getAbsoluteURL(
					$this->urlGenerator->imagePath(Application::APP_ID, 'app.svg')
				);
				$reference->setImageUrl($imageUrl);
				$reference->setRichObject(Application::APP_ID . '-poll', [
					'id' => $pollId,
					'poll' => [
						'id' => $pollId,
						'title' => $poll->getTitle(),
						'description' => $poll->getDescription(),
						'ownerDisplayName' => $ownerDisplayName,
						'ownerId' => $poll->getowner(),
						'url' => $poll->getVoteUrl(),
					],
				]);
				return $reference;
			}
		}

		return null;
	}

	private function gonePollReference($pollId): IReference {
		$reference = new Reference('gone');
		$reference->setTitle($this->l10n->t('Poll not found'));
		$reference->setDescription($this->l10n->t('This poll does not exist (anymore).'));
		$imageUrl = $this->urlGenerator->getAbsoluteURL(
			$this->urlGenerator->imagePath(Application::APP_ID, 'app.svg')
		);
		$reference->setImageUrl($imageUrl);
				$reference->setRichObject(Application::APP_ID . '-poll', [
					'id' => $pollId,
					'poll' => [
						'id' => $pollId,
						'title' => $this->l10n->t('Poll not found'),
						'description' => $this->l10n->t('This poll does not exist (anymore).'),
						'ownerDisplayName' => $this->l10n->t('No one.'),
						'ownerId' => $this->l10n->t('No one.'),
						'url' => null,
					],
				]);
		return $reference;

	}
	private function getPollId(string $url): ?int {
		$start = $this->urlGenerator->getAbsoluteURL('/apps/' . Application::APP_ID);
		$startIndex = $this->urlGenerator->getAbsoluteURL('/index.php/apps/' . Application::APP_ID);

		preg_match('/^' . preg_quote($start, '/') . '?\/vote\/([0-9]+)$/', $url, $matches);
		if (!$matches) {
			preg_match('/^' . preg_quote($startIndex, '/') . '?\/vote\/([0-9]+)$/', $url, $matches);
		}
		if ($matches && count($matches) > 1) {
			return (int)$matches[1];
		}

		return null;
	}

	public function getCachePrefix(string $referenceId): string {
		$pollId = $this->getPollId($referenceId);
		if ($pollId !== null) {
			return (string)$pollId;
		}

		return $referenceId;
	}

	public function getCacheKey(string $referenceId): ?string {
		return $this->userId ?? '';
	}
}

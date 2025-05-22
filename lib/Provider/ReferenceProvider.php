<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Polls\Provider;

use Exception;
use OCA\Polls\AppInfo\Application;
use OCA\Polls\Exceptions\ForbiddenException;
use OCA\Polls\Exceptions\NotFoundException;
use OCA\Polls\Service\PollService;
use OCP\Collaboration\Reference\ADiscoverableReferenceProvider;
use OCP\Collaboration\Reference\IReference;
use OCP\Collaboration\Reference\ISearchableReferenceProvider;
use OCP\Collaboration\Reference\Reference;
use OCP\IL10N;
use OCP\IURLGenerator;

class ReferenceProvider extends ADiscoverableReferenceProvider implements ISearchableReferenceProvider {

	/** @psalm-suppress PossiblyUnusedMethod */
	public function __construct(
		private PollService $pollService,
		private IURLGenerator $urlGenerator,
		private IL10N $l10n,
		private ?string $userId,
	) {
	}

	/**
	 * @inheritDoc
	 */
	public function matchReference(string $referenceText): bool {
		// validate url by checking if it contains a poll id; This is valid for internal polls
		return ($this->extractPollId($referenceText) !== 0);
	}

	public function extractPollId($referenceText): int {
		$matchingUrls = [
			$this->urlGenerator->getAbsoluteURL('/apps/' . Application::APP_ID . '/vote'), // poll url base without index.php
			$this->urlGenerator->getAbsoluteURL('/index.php/apps/' . Application::APP_ID . '/vote'), // poll url base with index.php
		];

		foreach ($matchingUrls as $url) {
			preg_match('/^' . preg_quote($url, '/') . '?\/([0-9]+)$/', $referenceText, $matches);
			if ($matches && count($matches) > 1) {
				return (int)$matches[1];
			}
		}
		return 0;
	}


	/**
	 * @inheritDoc
	 */
	public function resolveReference(string $referenceText): ?IReference {
		if ($this->matchReference($referenceText)) {
			$pollId = $this->extractPollId($referenceText);

			if ($pollId) {
				try {
					$poll = $this->pollService->get($pollId);
					$title = $this->l10n->t('Poll') . ': ' . $poll->getTitle();
					$description = $poll->getDescription();
					$ownerId = $poll->getUser()->getId();
					$ownerDisplayName = $poll->getUser()->getDisplayName();
					$url = $poll->getVoteUrl();

				} catch (NotFoundException $e) {
					$pollId = 0;
					$title = $this->l10n->t('404 - Poll not found');
					$description = $this->l10n->t('This poll does not exist (anymore).');
					$ownerId = null;
					$ownerDisplayName = $this->l10n->t('No one.');
					$url = null;

				} catch (ForbiddenException $e) {
					$owner = $this->pollService->getPollOwnerFromDB($pollId);
					$title = $this->l10n->t('Access denied');
					$ownerDisplayName = $owner->getDisplayName();
					$description = $this->l10n->t('You have no access to this poll. Contact %s if you think this is a mistake.', $ownerDisplayName);
					$ownerId = $owner->getId();
					$url = $referenceText;

				} catch (Exception $e) {
					// skip the reference silently
					return null;
				}

				$reference = new Reference($referenceText);
				$reference->setTitle($title);
				$reference->setDescription($description ? $description : $this->l10n->t('No description available.'));
				$reference->setImageUrl($this->getIconUrl());
				$reference->setRichObject(Application::APP_ID . '_reference_widget', [
					'id' => $pollId,
					'poll' => [
						'id' => $pollId,
						'title' => $title,
						'description' => $description ? $description : $this->l10n->t('No description available.'),
						'ownerDisplayName' => $ownerDisplayName,
						'ownerId' => $ownerId,
						'url' => $url,
					],
				]);
				return $reference;
			}
		}

		return null;
	}

	public function getCachePrefix(string $referenceId): string {
		$pollId = $this->extractPollId($referenceId);
		if ($pollId !== 0) {
			return (string)$pollId;
		}

		return $referenceId;
	}

	public function getCacheKey(string $referenceId): ?string {
		return $this->userId ?? '';
	}
	public function getId(): string {
		return Application::APP_ID;
	}
	public function getTitle(): string {
		return $this->l10n->t('Poll');
	}

	public function getIconUrl(): string {
		return $this->urlGenerator->imagePath(Application::APP_ID, 'polls.svg');
	}

	public function getOrder(): int {
		return 51;
	}

	public function getSupportedSearchProviderIds(): array {
		return ['search-poll'];
	}
}

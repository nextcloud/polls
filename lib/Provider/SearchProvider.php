<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2020 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);


namespace OCA\Polls\Provider;

use OCA\Polls\AppConstants;
use OCA\Polls\Db\Poll;
use OCA\Polls\Model\Search\PollsSearchResultEntry;
use OCA\Polls\Service\PollService;
use OCP\IL10N;
use OCP\IUser;
use OCP\Search\IProvider;
use OCP\Search\ISearchQuery;
use OCP\Search\SearchResult;

class SearchProvider implements IProvider {
	/** @psalm-suppress PossiblyUnusedMethod */
	public function __construct(
		private IL10N $l10n,
		// private IURLGenerator $urlGenerator,
		private PollService $pollService,
	) {
	}

	public function getId(): string {
		return 'poll';
	}

	public function getName(): string {
		return $this->l10n->t('Polls');
	}

	public function search(IUser $user, ISearchQuery $query): SearchResult {
		$polls = $this->pollService->search($query);

		$results = array_map(function (Poll $poll) {
			return [
				'object' => $poll,
				'entry' => new PollsSearchResultEntry($poll)
			];
		}, $polls);

		$resultEntries = array_map(function (array $result) {
			return $result['entry'];
		}, $results);

		return SearchResult::complete(
			$this->l10n->t('Polls'),
			$resultEntries
		);
	}

	public function getOrder(string $route, array $routeParameters): int {
		if (in_array(strtolower($route), [AppConstants::APP_ID . '.page.indexindex', AppConstants::APP_ID . '.page.vote'])) {
			return -5;
		}
		return 51;
	}
}

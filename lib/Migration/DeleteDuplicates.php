<?php
/**
 * @copyright Copyright (c) 2017 Julius Härtl <jus@bitgrid.net>
 *
 * @author Julius Härtl <jus@bitgrid.net>
 * @author René Gieling <github@dartcafe.de>
 *
 * @license GNU AGPL version 3 or any later version
 *
 *  This program is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU Affero General Public License as
 *  published by the Free Software Foundation, either version 3 of the
 *  License, or (at your option) any later version.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU Affero General Public License for more details.
 *
 *  You should have received a copy of the GNU Affero General Public License
 *  along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */


namespace OCA\Polls\Migration;

use OCA\Polls\Db\LogMapper;
use OCA\Polls\Db\OptionMapper;
use OCA\Polls\Db\PreferencesMapper;
use OCA\Polls\Db\ShareMapper;
use OCA\Polls\Db\SubscriptionMapper;
use OCA\Polls\Db\VoteMapper;
use OCP\Migration\IRepairStep;
use OCP\Migration\IOutput;

class DeleteDuplicates implements IRepairStep {
	/** @var LogMapper */
	private $logMapper;

	/** @var OptionMapper */
	private $optionMapper;

	/** @var PreferencesMapper */
	private $preferencesMapper;

	/** @var ShareMapper */
	private $shareMapper;

	/** @var SubscriptionMapper */
	private $subscriptionMapper;

	/** @var VoteMapper */
	private $voteMapper;

	public function __construct(
		LogMapper $logMapper,
		OptionMapper $optionMapper,
		PreferencesMapper $preferencesMapper,
		ShareMapper $shareMapper,
		SubscriptionMapper $subscriptionMapper,
		VoteMapper $voteMapper
	) {
		$this->logMapper = $logMapper;
		$this->optionMapper = $optionMapper;
		$this->preferencesMapper = $preferencesMapper;
		$this->shareMapper = $shareMapper;
		$this->subscriptionMapper = $subscriptionMapper;
		$this->voteMapper = $voteMapper;
	}

	/*
	 * @inheritdoc
	 */
	public function getName() {
		return 'Delete duplicates';
	}

	/**
	 * @inheritdoc
	 */
	public function run(IOutput $output) {
		$this->logMapper->removeDuplicates();
		$this->optionMapper->removeDuplicates();
		$this->preferencesMapper->removeDuplicates();
		$this->shareMapper->removeDuplicates();
		$this->subscriptionMapper->removeDuplicates();
		$this->voteMapper->removeDuplicates();
	}
}

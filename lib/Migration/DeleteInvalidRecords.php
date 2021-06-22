<?php
/**
 * @copyright Copyright (c) 2021 René Gieling <github@dartcafe.de>
 *
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

use OC\DB\Connection;
use OCP\IConfig;
use OCP\Migration\IRepairStep;
use OCP\Migration\IOutput;

use OCA\Polls\Db\LogMapper;
use OCA\Polls\Db\OptionMapper;
use OCA\Polls\Db\PreferencesMapper;
use OCA\Polls\Db\ShareMapper;
use OCA\Polls\Db\SubscriptionMapper;
use OCA\Polls\Db\VoteMapper;

/**
 * Preparation before migration
 * Remove all invalid records to avoid erros while adding indices ans constraints
 */
class DeleteInvalidRecords implements IRepairStep {
	/** @var IConfig */
	protected $config;

	/** @var Connection */
	protected $connection;

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

	protected $childTables = [
		'polls_comments',
		'polls_log',
		'polls_notif',
		'polls_options',
		'polls_share',
		'polls_votes',
	];

	public function __construct(
		IConfig $config,
		Connection $connection,
		LogMapper $logMapper,
		OptionMapper $optionMapper,
		PreferencesMapper $preferencesMapper,
		ShareMapper $shareMapper,
		SubscriptionMapper $subscriptionMapper,
		VoteMapper $voteMapper
	) {
		$this->config = $config;
		$this->connection = $connection;
		$this->logMapper = $logMapper;
		$this->optionMapper = $optionMapper;
		$this->preferencesMapper = $preferencesMapper;
		$this->shareMapper = $shareMapper;
		$this->subscriptionMapper = $subscriptionMapper;
		$this->voteMapper = $voteMapper;
	}

	public function getName():string {
		return 'Polls - Delete duplicates and orphaned records';
	}

	public function run(IOutput $output):void {
		$this->removeOrphaned();
		$this->logMapper->removeDuplicates($output);
		$this->optionMapper->removeDuplicates($output);
		$this->preferencesMapper->removeDuplicates($output);
		$this->shareMapper->removeDuplicates($output);
		$this->subscriptionMapper->removeDuplicates($output);
		$this->voteMapper->removeDuplicates($output);
	}

	/**
	 * delete all orphaned entries by selecting all rows
	 * those poll_ids are not present in the polls table
	 *
	 * we have to use a raw query, because NOT EXISTS is not
	 * part of doctrine's expression builder
	 */
	private function removeOrphaned():void {
		// polls 1.4 -> introduced contraints
		// Version0104Date20200205104800
		// get table prefix, as we are running a raw query
		$prefix = $this->config->getSystemValue('dbtableprefix', 'oc_');
		// check for orphaned entries in all tables referencing
		// the main polls table
		foreach ($this->childTables as $tableName) {
			$child = "$prefix$tableName";
			$query = "DELETE
                FROM $child
                WHERE NOT EXISTS (
                    SELECT NULL
                    FROM {$prefix}polls_polls polls
                    WHERE polls.id = {$child}.poll_id
                )";
			$stmt = $this->connection->prepare($query);
			$stmt->execute();
		}
	}
}

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


namespace OCA\Polls\Migration\RepairSteps;

use OCA\Polls\Db\Poll;
use OCA\Polls\Db\TableManager;
use OCA\Polls\Db\LogMapper;
use OCA\Polls\Db\OptionMapper;
use OCA\Polls\Db\PreferencesMapper;
use OCA\Polls\Db\ShareMapper;
use OCA\Polls\Db\SubscriptionMapper;
use OCA\Polls\Db\VoteMapper;
use OCA\Polls\Db\WatchMapper;
use OCP\IDBConnection;
use OCP\IConfig;
use OCP\Migration\IRepairStep;
use OCP\Migration\IOutput;

/**
 * Preparation before migration
 * Remove all invalid records to avoid erros while adding indices ans constraints
 */
class DeleteInvalidRecords implements IRepairStep {

	public function __construct(
		private IConfig $config,
		private IDBConnection $connection,
		private LogMapper $logMapper,
		private OptionMapper $optionMapper,
		private PreferencesMapper $preferencesMapper,
		private ShareMapper $shareMapper,
		private SubscriptionMapper $subscriptionMapper,
		private VoteMapper $voteMapper,
		private WatchMapper $watchMapper,
		private TableManager $tableManager
	) {
	}

	public function getName():string {
		return 'Polls - Delete duplicates and orphaned records';
	}

	public function run(IOutput $output):void {
		if ($this->connection->tableExists(Poll::TABLE)) {
			// secure, that the schema is updated to the current status
			$this->tableManager->refreshSchema();

			$this->tableManager->removeOrphaned();

			$this->logMapper->removeDuplicates($output);
			$this->optionMapper->removeDuplicates($output);
			$this->preferencesMapper->removeDuplicates($output);
			$this->shareMapper->removeDuplicates($output);
			$this->subscriptionMapper->removeDuplicates($output);
			$this->voteMapper->removeDuplicates($output);
			// TODO: Obsolete, since we reset the table on every app enabling
			$this->watchMapper->deleteOldEntries(time());
		}
	}
}

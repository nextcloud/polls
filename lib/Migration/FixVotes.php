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

use OCA\Polls\Db\LogMapper;
use OCA\Polls\Db\OptionMapper;
use OCA\Polls\Db\VoteMapper;
use OC\DB\Connection;
use OC\DB\SchemaWrapper;
use OCP\Migration\IRepairStep;
use OCP\Migration\IOutput;

class FixVotes implements IRepairStep {
	/** @var Connection */
	protected $connection;

	/** @var LogMapper */
	private $logMapper;

	/** @var OptionMapper */
	private $optionMapper;

	/** @var VoteMapper */
	private $voteMapper;

	public function __construct(
		Connection $connection,
		LogMapper $logMapper,
		OptionMapper $optionMapper,
		VoteMapper $voteMapper
	) {
		$this->connection = $connection;
		$this->logMapper = $logMapper;
		$this->optionMapper = $optionMapper;
		$this->voteMapper = $voteMapper;
	}

	/*
	 * @inheritdoc
	 */
	public function getName() {
		return 'Polls repairstep - Fix votes with duration options';
	}

	/**
	 * @inheritdoc
	 *
	 * @return void
	 */
	public function run(IOutput $output) {
		$schema = new SchemaWrapper($this->connection);
		if ($schema->hasTable(OptionMapper::TABLE)) {
			$table = $schema->getTable(OptionMapper::TABLE);
			if ($table->hasColumn('duration')) {
				$foundOptions = $this->optionMapper->findOptionsWithDuration();
				foreach ($foundOptions as $option) {
					$this->voteMapper->fixVoteOptionText(
						$option->getPollId(),
						$option->getId(),
						$option->getPollOptionTextStart(),
						$option->getPollOptionText(),
					);
				}
			}
		}
	}
}

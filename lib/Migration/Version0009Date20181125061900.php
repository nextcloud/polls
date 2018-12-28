<?php
/**
 * @copyright Copyright (c) 2017 René Gieling <github@dartcafe.de>
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
 *  along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OCA\Polls\Migration;

use Doctrine\DBAL\Exception\TableNotFoundException;
use Doctrine\DBAL\Platforms\PostgreSqlPlatform;
use Doctrine\DBAL\Types\Type;
use OCP\DB\ISchemaWrapper;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IConfig;
use OCP\IDBConnection;
use OCP\Migration\SimpleMigrationStep;
use OCP\Migration\IOutput;

/**
 * Installation class for the polls app.
 * Initial db creation
 */
class Version0009Date20181125061900 extends SimpleMigrationStep {

	/** @var IDBConnection */
	protected $connection;

	/** @var IConfig */
	protected $config;

	/**
	 * @param IDBConnection $connection
	 * @param IConfig $config
	 */
	public function __construct(IDBConnection $connection, IConfig $config) {
		$this->connection = $connection;
		$this->config = $config;
	}

	/**
	 * @param IOutput $output
	 * @param \Closure $schemaClosure The `\Closure` returns a `ISchemaWrapper`
	 * @param array $options
	 * @return null|ISchemaWrapper
	 * @since 13.0.0
	 */
	public function changeSchema(IOutput $output, \Closure $schemaClosure, array $options) {
		/** @var ISchemaWrapper $schema */
		$schema = $schemaClosure();

		if (!$schema->hasTable('polls_events')) {
			$table = $schema->createTable('polls_events');
			$table->addColumn('id', Type::INTEGER, [
				'autoincrement' => true,
				'notnull' => true,
			]);
			$table->addColumn('hash', Type::STRING, [
				'notnull' => false,
				'length' => 64,
			]);
			$table->addColumn('type', Type::BIGINT, [
				'notnull' => false,
				'length' => 16,
			]);
			$table->addColumn('title', Type::STRING, [
				'notnull' => true,
				'length' => 128,
			]);
			$table->addColumn('description', Type::STRING, [
				'notnull' => true,
				'length' => 1024,
			]);
			$table->addColumn('owner', Type::STRING, [
				'notnull' => true,
				'length' => 64,
			]);
			$table->addColumn('created', Type::DATETIME, [
				'notnull' => false,
			]);
			$table->addColumn('access', Type::STRING, [
				'notnull' => false,
				'length' => 1024,
			]);
			$table->addColumn('expire', Type::DATETIME, [
				'notnull' => false,
			]);
			$table->addColumn('is_anonymous', Type::INTEGER, [
				'notnull' => false,
				'default' => 0,
			]);
			$table->addColumn('full_anonymous', Type::INTEGER, [
				'notnull' => false,
				'default' => 0,
			]);
			$table->addColumn('allow_maybe', Type::INTEGER, [
				'notnull' => false,
				'default' => 1,
			]);
			$table->setPrimaryKey(['id']);
		} else {

			$table = $schema->getTable('polls_events');
			if (!$table->hasColumn('allow_maybe')) {
				$table->addColumn('allow_maybe', Type::INTEGER, [
					'notnull' => false,
					'default' => 1,
				]);
			}

		}

		if (!$schema->hasTable('polls_options')) {
			$table = $schema->createTable('polls_options');
			$table->addColumn('id', Type::INTEGER, [
				'autoincrement' => true,
				'notnull' => true,
			]);
			$table->addColumn('poll_id', Type::INTEGER, [
				'notnull' => false,
			]);
			$table->addColumn('poll_option_text', Type::STRING, [
				'notnull' => false, // maybe true?
				'length' => 256,
			]);
			$table->addColumn('timestamp', Type::INTEGER, [
				'notnull' => false,
				'default' => 0
			]);
			$table->setPrimaryKey(['id']);
		}

		if (!$schema->hasTable('polls_votes')) {
			$table = $schema->createTable('polls_votes');
			$table->addColumn('id', Type::INTEGER, [
				'autoincrement' => true,
				'notnull' => true,
			]);
			$table->addColumn('poll_id', Type::INTEGER, [
				'notnull' => false,
			]);
			$table->addColumn('user_id', Type::STRING, [
				'notnull' => true,
				'length' => 64,
			]);
			$table->addColumn('vote_option_id', Type::INTEGER, [
				'notnull' => true,
				'default' => 0,
				'length' => 64,
			]);
			$table->addColumn('vote_option_text', Type::STRING, [
				'notnull' => false, // maybe true?
				'length' => 256,
			]);
			$table->addColumn('vote_answer', Type::STRING, [
				'notnull' => false,
				'length' => 64,
			]);
			$table->setPrimaryKey(['id']);
		}

		if (!$schema->hasTable('polls_comments')) {
			$table = $schema->createTable('polls_comments');
			$table->addColumn('id', Type::INTEGER, [
				'autoincrement' => true,
				'notnull' => true,
			]);
			$table->addColumn('poll_id', Type::INTEGER, [
				'notnull' => false,
			]);
			$table->addColumn('user_id', Type::STRING, [
				'notnull' => true,
				'length' => 64,
			]);
			$table->addColumn('dt', Type::STRING, [
				'notnull' => true,
				'length' => 32,
			]);
			$table->addColumn('comment', Type::STRING, [
				'notnull' => false,
				'length' => 1024,
			]);
			$table->setPrimaryKey(['id']);
		}

		if (!$schema->hasTable('polls_notif')) {
			$table = $schema->createTable('polls_notif');
			$table->addColumn('id', Type::INTEGER, [
				'autoincrement' => true,
				'notnull' => true,
			]);
			$table->addColumn('poll_id', Type::INTEGER, [
				'notnull' => false,
			]);
			$table->addColumn('user_id', Type::STRING, [
				'notnull' => true,
				'length' => 64,
			]);
			$table->setPrimaryKey(['id']);
		}

		return $schema;
	}

	/**
	 * @param IOutput $output
	 * @param \Closure $schemaClosure The `\Closure` returns a `ISchemaWrapper`
	 * @param array $options
	 * @since 13.0.0
	 */
	public function postSchemaChange(IOutput $output, \Closure $schemaClosure, array $options) {
		/** @var ISchemaWrapper $schema */
		$schema = $schemaClosure();

		if ($schema->hasTable('polls_dts')) {
			$this->copyDateOptions();
			$this->copyDateVotes();
		}
		if ($schema->hasTable('polls_txts')) {
			$this->copyTextOptions();
			$this->copyTextVotes();
		}
	}

	/**
	 * Copy date options
	 */
	protected function copyDateOptions() {
		$insert = $this->connection->getQueryBuilder();
		$insert->insert('polls_options')
			->values([
				'poll_id' => $insert->createParameter('poll_id'),
				// Decide between one of both
				// 'poll_date' => $insert->createParameter('poll_date'),
				'poll_option_text' => $insert->createParameter('poll_option_text'),
			]);
		$query = $this->connection->getQueryBuilder();
		$query->select('*')
			->from('polls_dts');
		$result = $query->execute();
		while ($row = $result->fetch()) {
			$insert
				->setParameter('poll_id', $row['poll_id'])
				// Decide between one of both
				// ->setParameter('poll_date', $row['dt'])
				->setParameter('poll_option_text', date($row['dt']));
			$insert->execute();
		}
		$result->closeCursor();
	}

	/**
	 * Copy text options
	 */
	protected function copyTextOptions() {
		$insert = $this->connection->getQueryBuilder();
		$insert->insert('polls_options')
			->values([
				'poll_id' => $insert->createParameter('poll_id'),
				// Decide between one of both
				// 'poll_text' => $insert->createParameter('poll_text'),
				'poll_option_text' => $insert->createParameter('poll_option_text'),
			]);
		$query = $this->connection->getQueryBuilder();
		$query->select('*')
			->from('polls_txts');
		$result = $query->execute();
		while ($row = $result->fetch()) {
			$insert
				->setParameter('poll_id', $row['poll_id'])
				// Decide between one of both
				// ->setParameter('poll_text', $row['text'])
				->setParameter('poll_option_text', preg_replace("/_\d*$/", "$1", $row['text']));
			$insert->execute();
		}
		$result->closeCursor();
	}

	/**
	 * Copy date votes
	 */
	protected function copyDateVotes() {
		$insert = $this->connection->getQueryBuilder();
		$insert->insert('polls_votes')
			->values([
				'poll_id' => $insert->createParameter('poll_id'),
				'user_id' => $insert->createParameter('user_id'),
				'vote_option_id' => $insert->createParameter('vote_option_id'),
				'vote_option_text' => $insert->createParameter('vote_option_text'),
				'vote_answer' => $insert->createParameter('vote_answer'),
			]);
		$query = $this->connection->getQueryBuilder();
		$query->select('*')
			->from('polls_particip');
		$result = $query->execute();
		while ($row = $result->fetch()) {
			$insert
				->setParameter('poll_id', $row['poll_id'])
				->setParameter('user_id', $row['user_id'])
				->setParameter('vote_option_id', $this->findOptionId($row['poll_id'], $row['dt']))
				->setParameter('vote_option_text', date($row['dt']))
				->setParameter('vote_answer', $this->translateVoteTypeToAnswer($row['type']));
			$insert->execute();
		}
		$result->closeCursor();
	}

	/**
	 * Copy text votes
	 */
	protected function copyTextVotes() {
		$insert = $this->connection->getQueryBuilder();
		$insert->insert('polls_votes')
			->values([
				'poll_id' => $insert->createParameter('poll_id'),
				'user_id' => $insert->createParameter('user_id'),
				'vote_option_id' => $insert->createParameter('vote_option_id'),
				'vote_option_text' => $insert->createParameter('vote_option_text'),
				'vote_answer' => $insert->createParameter('vote_answer'),
			]);
		$query = $this->connection->getQueryBuilder();
		$query->select('*')
			->from('polls_particip_text');
		$result = $query->execute();
		while ($row = $result->fetch()) {
			$insert
				->setParameter('poll_id', $row['poll_id'])
				->setParameter('user_id', $row['user_id'])
				->setParameter('vote_option_id', $this->findOptionId($row['poll_id'], preg_replace("/_\d*$/", "$1", $row['text'])))
				->setParameter('vote_option_text', preg_replace("/_\d*$/", "$1", $row['text']))
				->setParameter('vote_answer', $this->translateVoteTypeToAnswer($row['type']));
			$insert->execute();
		}
		$result->closeCursor();
	}

	/**
	 * @param int $voteType
	 * @return string
	 */
	protected function findOptionId($pollId, $text) {
		$queryFind = $this->connection->getQueryBuilder();
		$queryFind->select(['id'])
			->from('polls_options')
			->where('poll_id = :pollId')
			->andWhere('poll_option_text = :text')
			->setParameter('pollId', $pollId)
			->setParameter('text', $text);

		$resultFind = $queryFind->execute();
		$row = $resultFind->fetch();

		if ($row['id'] === null) {
			// seems, we have an orphaned vote, return 0 as vote_option_id
			return 0;
		} else {
			return $row['id'];
		}
	}

	protected function translateVoteTypeToAnswer($voteType) {
		switch ($voteType) {
			case 0:
				$answer = "no";
				break;
			case 1:
				$answer = "yes";
				break;
			case 2:
				$answer = "maybe";
				break;
			default:
				$answer = "no";
		}
		return $answer;
	}
}

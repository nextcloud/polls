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
 */
class Version009000Date20171202105141 extends SimpleMigrationStep {
	
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
			$table->setPrimaryKey(['id']);
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
			$table->addColumn('poll_date', Type::DATETIME, [
				'notnull' => false,
				'length' => 32,
			]);
			$table->addColumn('poll_text', Type::STRING, [
				'notnull' => false,
				'length' => 256,
			]);
			$table->addColumn('poll_option', Type::STRING, [
				'notnull' => false,  // maybe true?
				'length' => 256,
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
			$table->addColumn('option_id', Type::STRING, [
				'notnull' => true,
				'length' => 64,
			]);
			$table->addColumn('vote_date', Type::DATETIME, [
				'notnull' => false,
			]);
			$table->addColumn('vote_text', Type::STRING, [
				'notnull' => false,
				'length' => 256,
			]);
			$table->addColumn('vote_option', Type::STRING, [
				'notnull' => false, // maybe true?
				'length' => 256,
			]);
			//remove
			$table->addColumn('vote_type', Type::INTEGER, [
				'notnull' => false,
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
// -------------- Depricated tables
		// remove
		if (!$schema->hasTable('polls_dts')) {
			$table = $schema->createTable('polls_dts');
			$table->addColumn('id', Type::INTEGER, [
				'autoincrement' => true,
				'notnull' => true,
			]);
			$table->addColumn('poll_id', Type::INTEGER, [
				'notnull' => false,
			]);
			$table->addColumn('dt', Type::DATETIME, [
				'notnull' => false,
				'length' => 32,
			]);
			$table->setPrimaryKey(['id']);
		}

		// remove
		if (!$schema->hasTable('polls_txts')) {
			$table = $schema->createTable('polls_txts');
			$table->addColumn('id', Type::INTEGER, [
				'autoincrement' => true,
				'notnull' => true,
			]);
			$table->addColumn('poll_id', Type::INTEGER, [
				'notnull' => false,
			]);
			$table->addColumn('text', Type::STRING, [
				'notnull' => false,
				'length' => 256,
			]);
			$table->setPrimaryKey(['id']);
		}
		
		//remove
		if (!$schema->hasTable('polls_particip')) {
			$table = $schema->createTable('polls_particip');
			$table->addColumn('id', Type::INTEGER, [
				'autoincrement' => true,
				'notnull' => true,
			]);
			$table->addColumn('poll_id', Type::INTEGER, [
				'notnull' => false,
			]);
			$table->addColumn('dt', Type::DATETIME, [
				'notnull' => false,
			]);
			$table->addColumn('type', Type::INTEGER, [
				'notnull' => false,
			]);
			$table->addColumn('user_id', Type::STRING, [
				'notnull' => true,
				'length' => 64,
			]);
			$table->setPrimaryKey(['id']);
		}

		//remove
		if (!$schema->hasTable('polls_particip_text')) {
			$table = $schema->createTable('polls_particip_text');
			$table->addColumn('id', Type::INTEGER, [
				'autoincrement' => true,
				'notnull' => true,
			]);
			$table->addColumn('poll_id', Type::INTEGER, [
				'notnull' => false,
			]);
			$table->addColumn('text', Type::STRING, [
				'notnull' => false,
				'length' => 256,
			]);
			$table->addColumn('user_id', Type::STRING, [
				'notnull' => true,
				'length' => 64,
			]);
			$table->addColumn('type', Type::INTEGER, [
				'notnull' => false,
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
		$this->copyDateOptions();
		$this->copyTextOptions();
		$this->copyDateVotes();
		$this->copyTextVotes();
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
				'poll_date' => $insert->createParameter('poll_date'),
				'poll_option' => $insert->createParameter('poll_option'),
			]);
		$query = $this->connection->getQueryBuilder();
		$query->select('*')
			->from('polls_dts');
		$result = $query->execute();
		while ($row = $result->fetch()) {
			$insert
				->setParameter('poll_id', $row['poll_id'])
				// Decide between one of both
				->setParameter('poll_date', $row['dt'])
				->setParameter('poll_option', date($row['dt']));
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
				'poll_text' => $insert->createParameter('poll_text'),
				'poll_option' => $insert->createParameter('poll_option'),
			]);
		$query = $this->connection->getQueryBuilder();
		$query->select('*')
			->from('polls_txts');
		$result = $query->execute();
		while ($row = $result->fetch()) {
			$insert
				->setParameter('poll_id', $row['poll_id'])
				// Decide between one of both
				->setParameter('poll_text', $row['text'])
				->setParameter('poll_option', preg_replace("/_\d*$/", "$1", $row['text']));
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
				'option_id' => $insert->createParameter('option_id'),
				'vote_type' => $insert->createParameter('vote_type'),
				'vote_answer' => $insert->createParameter('vote_answer'),
				'vote_date' => $insert->createParameter('vote_date'),
				'vote_option' => $insert->createParameter('vote_option'),
			]);
		$query = $this->connection->getQueryBuilder();
		$query->select('*')
			->from('polls_particip');
		$result = $query->execute();
		while ($row = $result->fetch()) {
			$insert
				->setParameter('poll_id', $row['poll_id'])
				->setParameter('user_id', $row['user_id'])
				->setParameter('option_id', $this->findOptionId($row['poll_id'], $row['dt']))
				->setParameter('vote_type', $row['type'])
				->setParameter('vote_answer', $this->translateVoteTypeToAnswer($row['type']))
				->setParameter('vote_date', $row['dt'])
				->setParameter('vote_option', date($row['dt']));
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
				'option_id' => $insert->createParameter('option_id'),
				'vote_type' => $insert->createParameter('vote_type'),
				'vote_answer' => $insert->createParameter('vote_answer'),
				'vote_text' => $insert->createParameter('vote_text'),
				'vote_option' => $insert->createParameter('vote_option'),
			]);
		$query = $this->connection->getQueryBuilder();
		$query->select('*')
			->from('polls_particip_text');
		$result = $query->execute();
		while ($row = $result->fetch()) {
			$insert
				->setParameter('poll_id', $row['poll_id'])
				->setParameter('user_id', $row['user_id'])
				->setParameter('option_id', $this->findOptionId($row['poll_id'], preg_replace("/_\d*$/", "$1", $row['text'])))
				->setParameter('vote_type', $row['type'])
				->setParameter('vote_answer', $this->translateVoteTypeToAnswer($row['type']))
				->setParameter('vote_text', $row['text'])
				->setParameter('vote_option', preg_replace("/_\d*$/", "$1", $row['text']));
			$insert->execute();
		}
		$result->closeCursor();
	}

	/**
	 * @param int $voteType
	 * @return string
	 */
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

	/**
	 * @param String $pollId
	 * @param String $option
	 * @return string
	 */
	protected function findOptionId($pollId, $option) {
		$queryFind = $this->connection->getQueryBuilder();
		$queryFind->select(['id'])
			->from('polls_options')
			->where('poll_id = "'. $pollId .'"')
			->andWhere('poll_option ="' .$option .'"');

		$resultFind = $queryFind->execute();
		$row = $resultFind->fetch();
		return $row['id'];
	}
}

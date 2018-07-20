<?php
namespace OCA\Polls\Migration;

use Doctrine\DBAL\Exception\TableNotFoundException;
use Doctrine\DBAL\Platforms\PostgreSqlPlatform;
use Doctrine\DBAL\Types\Type;
use OCP\DB\ISchemaWrapper;
use OCP\IDBConnection;
use OCP\IConfig;
use OCP\Migration\SimpleMigrationStep;
use OCP\Migration\IOutput;

/**
 * Adding sharing structure
 */
class Version009000Date20180625070613 extends SimpleMigrationStep {
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
	 * @since 13.0.0
	 */
	public function preSchemaChange(IOutput $output, \Closure $schemaClosure, array $options) {
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

		if (!$schema->hasTable('polls_share')) {
			$table = $schema->createTable('polls_share');
			$table->addColumn('share_type', Type::INTEGER, [
				/** share_type 0 = user; 1 = group; 3 = public link; 4 = external (not implemented); 6 = federated cloud share (not implemented) */
				'notnull' => true,
				'default' => 0
			]);
			$table->addColumn('share_with', Type::STRING, [
				/** User id, group id, email address, federation address */
				'notnull' => false,
				'length' => 255
			]);
			$table->addColumn('item_type', Type::STRING, [
				/** item_type 'polls_event' or 'polls_vote' */
				'notnull' => true,
				'length' => 64,
				'default' => 'polls_event'
			]);
			$table->addColumn('share_hash', Type::STRING, [
				'notnull' => true,
				'length' => 64,
			]);
			$table->addColumn('share_pollid', Type::INTEGER, [
				/** id of the shared poll */
				'notnull' => true
			]);
			$table->addColumn('share_voteid', Type::INTEGER, [
				/** For editing the vote for external users, empty, if item_type = 'polls_event' */
				'notnull' => false
			]);
			$table->addColumn('mail_sent', Type::INTEGER, [
				/** check if mail was sent */
				'notnull' => false,
				'default' => 0
			]);
			$table->addColumn('uid_owner', Type::INTEGER, [
				'notnull' => true,
				'length' => 64,
			]);
			$table->addColumn('uid_initiator', Type::INTEGER, [
				/** can differ from uid_owner if the share was not created from the owner */
				'notnull' => true,
				'length' => 255,
			]);
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
	}
}

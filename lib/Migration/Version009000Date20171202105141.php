<?php
namespace OCA\Polls\Migration;

use Doctrine\DBAL\Schema\Schema;
use OCP\Migration\SimpleMigrationStep;
use OCP\Migration\IOutput;

/**
 * Auto-generated migration step: Please modify to your needs!
 */
class Version009000Date20171202105141 extends SimpleMigrationStep {

	/**
	 * @param IOutput $output
	 * @param \Closure $schemaClosure The `\Closure` returns a `Schema`
	 * @param array $options
	 * @since 13.0.0
	 */
	public function preSchemaChange(IOutput $output, \Closure $schemaClosure, array $options) {
	}

	/**
	 * @param IOutput $output
	 * @param \Closure $schemaClosure The `\Closure` returns a `Schema`
	 * @param array $options
	 * @return null|Schema
	 * @since 13.0.0
	 */
	public function changeSchema(IOutput $output, \Closure $schemaClosure, array $options) {
		/** @var Schema $schema */
		$schema = $schemaClosure();

		if (!$schema->hasTable('polls_events')) {
			$table = $schema->createTable('polls_events');
			$table->addColumn('id', 'integer', [
				'autoincrement' => true,
				'notnull' => true,
			]);
			$table->addColumn('hash', 'string', [
				'notnull' => false,
				'length' => 64,
			]);
			$table->addColumn('type', 'bigint', [
				'notnull' => false,
				'length' => 16,
			]);
			$table->addColumn('title', 'string', [
				'notnull' => true,
				'length' => 128,
			]);
			$table->addColumn('description', 'string', [
				'notnull' => true,
				'length' => 1024,
			]);
			$table->addColumn('owner', 'string', [
				'notnull' => true,
				'length' => 64,
			]);
			$table->addColumn('created', 'datetime', [
				'notnull' => false,
			]);
			$table->addColumn('access', 'string', [
				'notnull' => false,
				'length' => 1024,
			]);
			$table->addColumn('expire', 'datetime', [
				'notnull' => false,
			]);
			$table->addColumn('is_anonymous', 'integer', [
				'notnull' => false,
				'default' => 0,
			]);
			$table->addColumn('full_anonymous', 'integer', [
				'notnull' => false,
				'default' => 0,
			]);
			$table->setPrimaryKey(['id']);
		}

		if (!$schema->hasTable('polls_dts')) {
			$table = $schema->createTable('polls_dts');
			$table->addColumn('id', 'integer', [
				'autoincrement' => true,
				'notnull' => true,
			]);
			$table->addColumn('poll_id', 'integer', [
				'notnull' => false,
			]);
			$table->addColumn('dt', 'datetime', [
				'notnull' => false,
				'length' => 32,
			]);
			$table->setPrimaryKey(['id']);
		}

		if (!$schema->hasTable('polls_txts')) {
			$table = $schema->createTable('polls_txts');
			$table->addColumn('id', 'integer', [
				'autoincrement' => true,
				'notnull' => true,
			]);
			$table->addColumn('poll_id', 'integer', [
				'notnull' => false,
			]);
			$table->addColumn('text', 'string', [
				'notnull' => false,
				'length' => 256,
			]);
			$table->setPrimaryKey(['id']);
		}

		if (!$schema->hasTable('polls_particip')) {
			$table = $schema->createTable('polls_particip');
			$table->addColumn('id', 'integer', [
				'autoincrement' => true,
				'notnull' => true,
			]);
			$table->addColumn('poll_id', 'integer', [
				'notnull' => false,
			]);
			$table->addColumn('dt', 'datetime', [
				'notnull' => false,
			]);
			$table->addColumn('type', 'integer', [
				'notnull' => false,
			]);
			$table->addColumn('user_id', 'string', [
				'notnull' => true,
				'length' => 64,
			]);
			$table->setPrimaryKey(['id']);
		}

		if (!$schema->hasTable('polls_particip_text')) {
			$table = $schema->createTable('polls_particip_text');
			$table->addColumn('id', 'integer', [
				'autoincrement' => true,
				'notnull' => true,
			]);
			$table->addColumn('poll_id', 'integer', [
				'notnull' => false,
			]);
			$table->addColumn('text', 'string', [
				'notnull' => false,
				'length' => 256,
			]);
			$table->addColumn('user_id', 'string', [
				'notnull' => true,
				'length' => 64,
			]);
			$table->addColumn('type', 'integer', [
				'notnull' => false,
			]);
			$table->setPrimaryKey(['id']);
		}

		if (!$schema->hasTable('polls_comments')) {
			$table = $schema->createTable('polls_comments');
			$table->addColumn('id', 'integer', [
				'autoincrement' => true,
				'notnull' => true,
			]);
			$table->addColumn('poll_id', 'integer', [
				'notnull' => false,
			]);
			$table->addColumn('user_id', 'string', [
				'notnull' => true,
				'length' => 64,
			]);
			$table->addColumn('dt', 'string', [
				'notnull' => true,
				'length' => 32,
			]);
			$table->addColumn('comment', 'string', [
				'notnull' => false,
				'length' => 1024,
			]);
			$table->setPrimaryKey(['id']);
		}

		if (!$schema->hasTable('polls_notif')) {
			$table = $schema->createTable('polls_notif');
			$table->addColumn('id', 'integer', [
				'autoincrement' => true,
				'notnull' => true,
			]);
			$table->addColumn('poll_id', 'integer', [
				'notnull' => false,
			]);
			$table->addColumn('user_id', 'string', [
				'notnull' => true,
				'length' => 64,
			]);
			$table->setPrimaryKey(['id']);
		}
		return $schema;
	}

	/**
	 * @param IOutput $output
	 * @param \Closure $schemaClosure The `\Closure` returns a `Schema`
	 * @param array $options
	 * @since 13.0.0
	 */
	public function postSchemaChange(IOutput $output, \Closure $schemaClosure, array $options) {
	}
}

<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2025 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Polls\Db\V3;

use Doctrine\DBAL\Schema\Schema;
use Exception;
use OCA\Polls\Exceptions\InvalidClassException;
use OCP\DB\ISchemaWrapper;
use OCP\IConfig;
use OCP\IDBConnection;

abstract class DbManager {

	protected Schema|ISchemaWrapper $schema;
	protected string $dbPrefix;

	/** @psalm-suppress PossiblyUnusedMethod */
	public function __construct(
		protected IConfig $config,
		protected IDBConnection $connection,
	) {
		$this->dbPrefix = $this->config->getSystemValue('dbtableprefix', 'oc_');
	}

	/**
	 * Set the schema.
	 * This method is used to set the schema for the database manager.
	 * It can be used to overwrite the current schema.
	 * It must be called before any other methods that require a schema.
	 * @param Schema|ISchemaWrapper $schema
	 * @return void
	 */
	public function setSchema(Schema|ISchemaWrapper &$schema): void {
		$this->schema = $schema;
	}

	/**
	 * Create a new schema.
	 * This method is used to create a new schema instance.
	 * It must be called before any other methods that require a schema.
	 *
	 * @throws Exception if the schema cannot be created
	 */
	public function createSchema(): void {
		$this->schema = $this->connection->createSchema();
	}

	/**
	 * Migrate the database to the current schema.
	 * This method is used to apply the schema changes to the database.
	 * It must be called after the schema is set.
	 *
	 * @throws InvalidClassException if the schema is not an instance of Schema class
	 */
	public function migrateToSchema() : void {
		// Schema must be of class Schema
		$this->needsSchema(allowISchemWrapperClass: false);
		$this->connection->migrateToSchema($this->schema);
	}

	/**
	 * Set the database connection.
	 * Use it to overwrite the managers own connection.
	 *
	 * @param IDBConnection $connection
	 */
	public function setConnection(IDBConnection &$connection): void {
		$this->connection = $connection;
	}

	/**
	 * Get the table name with the prefix.
	 * If the schema is an instance of Schema, we need to prefix the table name.
	 * ISchemaWrapper already uses the prefixed table name, but Schema does not.
	 *
	 * @param string $tableName without prefix
	 * @return string|null
	 */
	protected function getTableName(string $tableName): ?string {
		if ($this->schema instanceof Schema) {
			// If the schema is an instance of Schema, we need to prefix the table name
			return $this->dbPrefix . $tableName;
		}
		return $tableName;
	}

	/**
	 * Use this as a predetermined breaking point to ensure if a method needs a schema to be set.
	 *
	 * @param bool $allowSchemaClass allow schema to be an instance of Schema (default is true)
	 * @param bool $allowISchemWrapperClass allow schema to be an instance of ISchemaWrapper (default is true)
	 * @throws InvalidClassException if the schema is not set or not of a required class
	 */
	protected function needsSchema(bool $allowSchemaClass = true, bool $allowISchemWrapperClass = true): void {
		if (($this->schema instanceof Schema) && $allowSchemaClass) {
			return;
		}

		if (($this->schema instanceof ISchemaWrapper) && $allowISchemWrapperClass) {
			return;
		}

		if ($allowSchemaClass && $allowISchemWrapperClass) {
			// If the schema is not set or not an instance of Schema or ISchemaWrapper, throw an exception
			throw new InvalidClassException('Schema is not set or not an instance of Schema or ISchemaWrapper (caller: ' . self::formatCaller() . ')');
		}
		if ($allowSchemaClass) {
			// If the schema is not set or not an instance of Schema, throw an exception
			throw new InvalidClassException('Schema is not set or not an instance of Schema (caller: ' . self::formatCaller() . ')');
		}
		if ($allowISchemWrapperClass) {
			// If the schema is not set or not an instance of ISchemaWrapper, throw an exception
			throw new InvalidClassException('Schema is not set or not an instance of ISchemaWrapper(caller: ' . self::formatCaller() . ')');
		}
		throw new InvalidClassException('Unexpected. Schema is an instance of ' . get_class($this->schema) . '(caller: ' . self::formatCaller() . ')');
	}

	private static function formatCaller(int $skip = 1): string {
		$bt = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, $skip + 2);
		$f = $bt[$skip + 0] ?? null; // Frame of this method (0)
		$c = $bt[$skip + 1] ?? null; // Frame of the caller (1)

		$cls = $c['class'] ?? '';
		$typ = $c['type'] ?? '';
		$fn = $c['function'] ?? '??';
		$fil = $c['file'] ?? ($f['file'] ?? '??');
		$ln = $c['line'] ?? ($f['line'] ?? 0);

		return sprintf('%s%s%s@%s:%d', $cls, $typ, $fn, self::short($fil), $ln);
	}

	private static function short(string $path): string {
		$norm = str_replace('\\', '/', $path);
		$pos = strpos($norm, '/lib/');
		return $pos === false ? basename($norm) : substr($norm, $pos + 1); // "lib/…"
	}
}

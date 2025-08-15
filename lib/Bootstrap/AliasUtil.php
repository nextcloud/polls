<?php

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Polls\Bootstrap;

final class AliasUtil {
	private const MAP = [
		\OCA\Polls\Db\TableManager::class => \OCA\Polls\Db\V2\TableManager::class,
		\OCA\Polls\Db\IndexManager::class => \OCA\Polls\Db\V2\IndexManager::class,
		\OCA\Polls\Migration\TableSchema::class => \OCA\Polls\Migration\V2\TableSchema::class,
	];

	/**
	 * Set class aliases early in the bootstrap phase and verify them.
	 *
	 * @return array<string,array{ok:bool,loaded:bool,file:?string,note:string}>
	 */
	public static function applyAliases(?\Psr\Log\LoggerInterface $logger = null): array {
		$results = [];

		$map = self::MAP;

		foreach ($map as $old => $new) {
			// Case 1: old class is already defined in the current process
			if (class_exists($old, false)) {
				$fileOld = (new \ReflectionClass($old))->getFileName();
				// try to load the new class to compare
				$loadedNew = class_exists($new, true);
				$fileNew = $loadedNew ? (new \ReflectionClass($new))->getFileName() : null;

				$alreadyOk = is_string($fileOld) && is_string($fileNew)
					&& realpath($fileOld) === realpath($fileNew);

				$oldShort = self::short($fileOld);
				$newShort = self::short($fileNew);

				if ($alreadyOk) {
					$logger?->debug("Alias ALREADY SET: $old -> $new | file=$oldShort", ['app' => 'polls']);
					$results[$old] = ['ok' => true, 'loaded' => true, 'file' => $oldShort, 'note' => 'already set'];
				} else {
					$logger?->warning("Alias SKIPPED: $old already loaded from $oldShort (differs from $newShort)", ['app' => 'polls']);
					$results[$old] = ['ok' => false, 'loaded' => true, 'file' => $oldShort, 'note' => 'alias too late'];
				}				continue;
			}

			// Case 2: old class is NOT defined yet - we can set the alias now
			try {
				if (!@class_alias($new, $old)) {
					$logger?->error("Alias FAILED: $old -> $new", ['app' => 'polls']);
					$results[$old] = ['ok' => false, 'loaded' => false, 'file' => null, 'note' => 'class_alias failed'];
					continue;
				}

				// verify
				$loadedOld = class_exists($old, true);
				$loadedNew = class_exists($new, true);
				$fileOld = $loadedOld ? (new \ReflectionClass($old))->getFileName() : null;
				$fileNew = $loadedNew ? (new \ReflectionClass($new))->getFileName() : null;

				$ok = is_string($fileOld) && is_string($fileNew)
					&& realpath($fileOld) === realpath($fileNew);

				$oldShort = self::short($fileOld);
				$newShort = self::short($fileNew);

				$msg = sprintf('Alias %s: %s -> %s | oldFile=%s | newFile=%s', $ok ? 'OK' : 'WARN', $old, $new, $oldShort, $newShort);
				$ok ? $logger?->debug($msg, ['app' => 'polls']) : $logger?->warning($msg, ['app' => 'polls']);

				$results[$old] = [
					'ok' => $ok,
					'loaded' => $loadedOld,
					'file' => $oldShort,
					'note' => $ok ? '' : 'old/new files differ',
				];

			} catch (\Throwable $e) {
				$logger?->error("Alias ERROR: $old -> $new | " . $e->getMessage(), ['app' => 'polls']);
				$results[$old] = ['ok' => false, 'loaded' => false, 'file' => null, 'note' => 'exception'];
			}
		}

		return $results;
	}

	private static function short(null|string|false $path): string {
		if (!is_string($path) || $path === '') {
			return 'n/a';
		}
		$norm = str_replace('\\', '/', $path);
		$pos = strpos($norm, '/lib/');
		return $pos === false ? basename($norm) : substr($norm, $pos + 1); // ab "lib/..."
	}
}

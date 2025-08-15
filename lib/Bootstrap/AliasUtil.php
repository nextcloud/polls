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

				$alreadyOk = $loadedNew && $fileOld && $fileNew
					&& realpath($fileOld) === realpath($fileNew);

				if ($alreadyOk) {
					$msg = "Alias ALREADY SET: $old -> $new | file=$fileOld";
					$logger?->info($msg, ['app' => 'polls']);
					$results[$old] = ['ok' => true, 'loaded' => true, 'file' => $fileOld, 'note' => 'already set'];
				} else {
					$msg = "Alias SKIPPED: $old already loaded from $fileOld (differs from $fileNew)";
					$logger?->warning($msg, ['app' => 'polls']);
					$results[$old] = ['ok' => false, 'loaded' => true, 'file' => $fileOld, 'note' => 'alias too late'];
				}
				continue;
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

				$ok = $loadedOld && $loadedNew && $fileOld && $fileNew
					&& realpath($fileOld) === realpath($fileNew);

				$logger?->{ $ok ? 'info' : 'warning' }(
					sprintf('Alias %s: %s -> %s | oldFile=%s | newFile=%s',
						$ok ? 'OK' : 'WARN', $old, $new, $fileOld ?? 'n/a', $fileNew ?? 'n/a'
					),
					['app' => 'polls']
				);

				$results[$old] = [
					'ok' => $ok,
					'loaded' => $loadedOld,
					'file' => $fileOld,
					'note' => $ok ? '' : 'old/new files differ',
				];
			} catch (\Throwable $e) {
				$logger?->error("Alias ERROR: $old -> $new | " . $e->getMessage(), ['app' => 'polls']);
				$results[$old] = ['ok' => false, 'loaded' => false, 'file' => null, 'note' => 'exception'];
			}
		}

		return $results;
	}
}

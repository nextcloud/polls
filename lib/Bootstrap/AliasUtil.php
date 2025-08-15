<?php

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Polls\Bootstrap;

use Psr\Log\LoggerInterface;

final class AliasUtil {
	private const MAP = [
		'\OCA\Polls\Db\TableManager' => '\OCA\Polls\Db\V2\TableManager',
		'\OCA\Polls\Db\IndexManager' => '\OCA\Polls\Db\V2\IndexManager',
		'\OCA\Polls\Migration\TableSchema' => '\OCA\Polls\Migration\V2\TableSchema',
	];

	/**
	 * Set class aliases early in the bootstrap phase and verify them.
	 *
	 * @return array<string,array{ok:bool,loaded:bool,file:?string,note:string}>
	 */
	public static function applyAliases(?LoggerInterface $logger = null): array {
		$results = [];

		/** @var array<class-string, class-string> $map */
		$map = self::MAP;

		foreach ($map as $old => $new) {
			// Too late to set the alias if the old class is already loaded
			/** @psalm-suppress TypeDoesNotContainType */
			if (class_exists($old, false)) {
				$file = (new \ReflectionClass($old))->getFileName();
				$logger?->warning("Alias SKIPPED: $old already loaded | file=$file", ['app' => 'polls']);
				$results[$old] = ['ok' => false, 'loaded' => true, 'file' => $file, 'note' => 'alias too late'];
				continue;
			}

			try {
				if (!@class_alias($new, $old)) {
					$logger?->error("Alias FAILED: $old -> $new", ['app' => 'polls']);
					$results[$old] = ['ok' => false, 'loaded' => false, 'file' => null, 'note' => 'class_alias failed'];
					continue;
				}

				// Verify (now allow autoloading to check if the alias works correctly)
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

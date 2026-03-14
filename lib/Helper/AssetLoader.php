<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2026 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Polls\Helper;

use OCA\Polls\AppInfo\Application;
use Psr\Log\LoggerInterface;

/**
 * Resolves Vite entry point names to their content-hashed filenames
 * by reading the generated js/manifest.json at runtime.
 */
class AssetLoader {
	/** @var array<string, mixed>|null null = file not present, array = file was read */
	private static ?array $manifest = null;
	private static bool $manifestChecked = false;

	/**
	 * Returns the script name for use with Util::addScript().
	 *
	 * @param string $entry Vite entry key (e.g. 'main', 'dashboard')
	 */
	public static function getScript(string $entry): string {
		// Vite uses source paths as manifest keys (e.g. 'src/main.ts'),
		// so we search by output filename: entryFileNames = 'js/[name]-[hash].mjs'
		// where [name] always equals the entry alias from rollupOptions.input.
		$manifest = self::getManifest();

		if ($manifest !== null) {
			foreach ($manifest as $asset) {
				if (($asset['isEntry'] ?? false) === true
					&& isset($asset['file'])
					&& preg_match('#^js/' . preg_quote($entry, '#') . '-[^/]+\.mjs$#', $asset['file'])
				) {
					return substr($asset['file'], 3, -4); // strip 'js/' and '.mjs'
				}
			}
			// Manifest was found but the entry is missing — real configuration error
			try {
				\OCP\Server::get(LoggerInterface::class)
					->warning("Entry '$entry' not found in Vite manifest", ['app' => Application::APP_ID]);
			} catch (\Throwable) {
				// Not in NC context (e.g. unit tests)
			}
		}

		// Fallback: manifest absent (CI/dev without build) or entry not found
		return $entry;
	}

	/** @return array<string, mixed>|null null when manifest file does not exist */
	private static function getManifest(): ?array {
		if (!self::$manifestChecked) {
			self::$manifestChecked = true;
			$path = __DIR__ . '/../../js/manifest.json';
			$content = @file_get_contents($path);
			if ($content !== false) {
				self::$manifest = json_decode($content, true) ?? [];
			}
		}
		return self::$manifest;
	}
}

<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2026 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Polls\Helper;

/**
 * Resolves Vite entry point names to their content-hashed filenames
 * by reading the generated js/manifest.json at runtime.
 */
class AssetLoader {
	private static ?array $manifest = null;

	/**
	 * Returns the script name for use with Util::addScript().
	 *
	 * @param string $entry Vite entry key (e.g. 'main', 'dashboard')
	 */
	public static function getScript(string $entry): string {
		// Vite uses source paths as manifest keys (e.g. 'src/main.ts'),
		// so we search by output filename: entryFileNames = 'js/[name]-[hash].mjs'
		// where [name] always equals the entry alias from rollupOptions.input.
		foreach (self::getManifest() as $asset) {
			if (($asset['isEntry'] ?? false) === true
				&& isset($asset['file'])
				&& preg_match('#^js/' . preg_quote($entry, '#') . '-[^/]+\.mjs$#', $asset['file'])
			) {
				return substr($asset['file'], 3, -4); // strip 'js/' and '.mjs'
			}
		}

		throw new \RuntimeException("Entry '$entry' not found in Vite manifest");
	}

	private static function getManifest(): array {
		if (self::$manifest === null) {
			$path = __DIR__ . '/../../js/manifest.json';
			$content = @file_get_contents($path);
			self::$manifest = $content !== false ? (json_decode($content, true) ?? []) : [];
		}
		return self::$manifest;
	}
}

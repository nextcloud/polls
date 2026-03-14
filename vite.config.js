/**
 * SPDX-FileCopyrightText: 2024 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import { createAppConfig } from '@nextcloud/vite-config'
import { join, resolve } from 'path'

const customConfig = {
	resolve: {
		alias: {
			'@': resolve('src'),
		},
	},
	css: {
		preprocessorOptions: {
			scss: {
				api: 'modern-compiler',
			},
		},
	},
	build: {
		// rollup-plugin-esbuild-minify runs in renderChunk (before Rollup resolves
		// !~{NNN}~ placeholder hashes) and renames variables to short names that
		// collide across chunk sections, causing vite:esbuild-transpile to fail with
		// "'_s' is not declared in this file". Use terser instead: it runs in
		// generateBundle after all placeholders are resolved.
		minify: 'terser',
		manifest: 'js/manifest.json',
		rollupOptions: {
			output: {
				// Use content hash instead of version prefix for cache busting
				entryFileNames: 'js/[name]-[hash].mjs',
			},
		},
	},
}
export default createAppConfig(
	{
		main: resolve(join('src', 'main.ts')),
		userSettings: resolve(join('src', 'userSettings.ts')),
		adminSettings: resolve(join('src', 'adminSettings.ts')),
		dashboard: resolve(join('src', 'dashboard.ts')),
		reference: resolve(join('src', 'polls-reference.ts')),
	},
	{
		inlineCSS: { relativeCSSInjection: true },
		config: customConfig,
		minify: false, // disable rollup-plugin-esbuild-minify; terser is used instead (see above)
	},
)

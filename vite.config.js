/**
 * SPDX-FileCopyrightText: 2024 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import { createAppConfig } from '@nextcloud/vite-config'
import { join, resolve } from 'path'

const customConfig = {
	resolve: {
		alias: {
			'@': resolve('src/js'),
		},
	},
	css: {
		preprocessorOptions: {
			scss: {
				api: 'modern-compiler',
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
	},
)

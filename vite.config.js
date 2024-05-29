/**
 * SPDX-FileCopyrightText: 2024 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import { createAppConfig } from '@nextcloud/vite-config'
import { join, resolve } from 'path'

export default createAppConfig({
	main: resolve(join('src/js', 'main.js')),
	userSettings: resolve(join('src/js', 'userSettings.js')),
	adminSettings: resolve(join('src/js', 'adminSettings.js')),
	dashboard: resolve(join('src/js', 'dashboard.js')),
}, {
	inlineCSS: { relativeCSSInjection: true },
})

import { createAppConfig } from '@nextcloud/vite-config'
import { join, resolve } from 'path'

export default createAppConfig(
	{
		main: resolve(join('src/js', 'main.js')),
		userSettings: resolve(join('src/js', 'userSettings.js')),
		adminSettings: resolve(join('src/js', 'adminSettings.js')),
		dashboard: resolve(join('src/js', 'dashboard.js')),
	},
	{
		config: {
			build: {
				cssCodeSplit: false,
				rollupOptions: {
					output: {
						manualChunks: {
							vendor: ['vue', 'vue-router'],
						},
					},
				},
			},
		},
		inlineCSS: true,
	},
)

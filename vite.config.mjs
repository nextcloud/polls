import { createAppConfig } from '@nextcloud/vite-config'

export default createAppConfig({
	main: 'src/js/main.js',
	dashboard: 'src/js/dashboard.js',
	userSettings: 'src/js/userSettings.js',
	adminSettings: 'src/js/adminSettings.js',
})

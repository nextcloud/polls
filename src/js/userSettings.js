/* jshint esversion: 6 */
/**
 * @copyright Copyright (c) 2021 René Gieling <github@dartcafe.de>
 *
 * @author René Gieling <github@dartcafe.de>
 *
 * @license  AGPL-3.0-or-later
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

import { createApp } from 'vue'
import { createStore } from 'vuex'
import settings from './store/modules/settings.js'
import { translate, translatePlural } from '@nextcloud/l10n'

import UserSettingsPage from './views/UserSettingsPage.vue'

// Vue.config.debug = import.meta.env.MODE === 'development'
// Vue.config.devtools = import.meta.env.MODE === 'development'

const store = createStore({
	modules: {
		settings,
	},
	strict: import.meta.env.MODE === 'development',
})

const PollsUserSettings = createApp(UserSettingsPage)
	.use(store)

PollsUserSettings.config.globalProperties.t = translate
PollsUserSettings.config.globalProperties.n = translatePlural

PollsUserSettings.mount('#user_settings')

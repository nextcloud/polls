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
import appSettings from './store/modules/appSettings.js'
import { translate, translatePlural } from '@nextcloud/l10n'

import AdminSettingsPage from './views/AdminSettingsPage.vue'

/* eslint-disable-next-line camelcase, no-undef */
__webpack_nonce__ = btoa(getRequestToken())
/* eslint-disable-next-line camelcase, no-undef */
__webpack_public_path__ = generateFilePath('polls', '', 'js/')

const store = createStore({
	modules: {
		appSettings,
	},
	strict: process.env.NODE_ENV !== 'production',
})

const PollsAdminSettings = createApp(AdminSettingsPage)
	.use(store)

PollsAdminSettings.config.globalProperties.t = translate
PollsAdminSettings.config.globalProperties.n = translatePlural

AdminSettingsPage.mount('#admin_settings')

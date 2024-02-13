/* jshint esversion: 6 */
/**
 * @copyright Copyright (c) 2018 René Gieling <github@dartcafe.de>
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
import App from './App.vue'
// import { sync } from 'vuex-router-sync'
import store from './store/index.js'
import router from './router.js'
import ClickOutside from 'v-click-outside'
import { getCurrentUser } from '@nextcloud/auth'
import { translate, translatePlural } from '@nextcloud/l10n'
import { Tooltip } from '@nextcloud/vue'

import UserItem from './components/User/UserItem.vue'

// Vue.config.debug = import.meta.env.MODE === 'development'
// Vue.config.devtools = import.meta.env.MODE === 'development'

const Polls = createApp(App)
	.component('UserItem', UserItem)
	.component('tooltip', Tooltip)
	.use(router)
	.use(store)
	.use(ClickOutside)
	.config.debug(process.env.NODE_ENV !== 'production')
	.config.devTools(process.env.NODE_ENV !== 'production')

Polls.config.globalProperties.t = translate
Polls.config.globalProperties.n = translatePlural
Polls.config.globalProperties.getCurrentUser = getCurrentUser
Polls.mount('#content')

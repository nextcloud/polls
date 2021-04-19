/* jshint esversion: 6 */
/**
 * @copyright Copyright (c) 2018 René Gieling <github@dartcafe.de>
 *
 * @author René Gieling <github@dartcafe.de>
 *
 * @license GNU AGPL version 3 or any later version
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

import Vue from 'vue'
import App from './App'
import { sync } from 'vuex-router-sync'
import store from './store'
import router from './router'
import ClickOutside from 'v-click-outside'
import VueClipboard from 'vue-clipboard2'
import { getRequestToken, getCurrentUser } from '@nextcloud/auth'
import { translate, translatePlural } from '@nextcloud/l10n'
import { generateFilePath } from '@nextcloud/router'
import { Tooltip } from '@nextcloud/vue'

import UserItem from './components/User/UserItem'
import ButtonDiv from './components/Base/ButtonDiv'

/* eslint-disable-next-line camelcase, no-undef */
__webpack_nonce__ = btoa(getRequestToken())
/* eslint-disable-next-line camelcase, no-undef */
__webpack_public_path__ = generateFilePath('polls', '', 'js/')

sync(store, router)

Vue.config.debug = process.env.NODE_ENV !== 'production'
Vue.config.devTools = process.env.NODE_ENV !== 'production'

Vue.prototype.t = translate
Vue.prototype.n = translatePlural
Vue.prototype.getCurrentUser = getCurrentUser

// eslint-disable-next-line vue/match-component-file-name
Vue.component('UserItem', UserItem)
// eslint-disable-next-line vue/match-component-file-name
Vue.component('ButtonDiv', ButtonDiv)
Vue.directive('tooltip', Tooltip)

Vue.use(ClickOutside)
Vue.use(VueClipboard)

/* eslint-disable-next-line no-new */
new Vue({
	el: '#content',
	router,
	store,
	render: (h) => h(App),
})

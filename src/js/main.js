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
import router from './router'
import axios from 'nextcloud-axios'
import App from './App.vue'
import vClickOutside from 'v-click-outside'
import VueClipboard from 'vue-clipboard2'

import { DatetimePicker, PopoverMenu, Tooltip } from 'nextcloud-vue'

import Modal from './plugins/plugin.js'
import Controls from './components/base/controls'
import UserDiv from './components/base/userDiv'
import SideBar from './components/base/sideBar'
import SideBarClose from './components/base/sideBarClose'
import ShareDiv from './components/base/shareDiv'
import LoadingOverlay from './components/base/loadingOverlay'

Vue.config.debug = true
Vue.config.devTools = true
Vue.component('PopoverMenu', PopoverMenu)
Vue.component('DatePicker', DatetimePicker)
Vue.component('Controls', Controls)
Vue.component('UserDiv', UserDiv)
Vue.component('SideBar', SideBar)
Vue.component('SideBarClose', SideBarClose)
Vue.component('ShareDiv', ShareDiv)
Vue.component('LoadingOverlay', LoadingOverlay)

Vue.directive('tooltip', Tooltip)

Vue.use(vClickOutside)
Vue.use(VueClipboard)
Vue.use(Modal)

Vue.prototype.t = t
Vue.prototype.n = n
Vue.prototype.$http = axios
Vue.prototype.OC = OC
Vue.prototype.OCA = OCA

__webpack_nonce__ = btoa(OC.requestToken)
__webpack_public_path__ = OC.linkTo('polls', 'js/')

/* eslint-disable-next-line no-new */
new Vue({
	el: '#app-polls',
	router: router,
	render: h => h(App)
})

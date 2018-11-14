/*jshint esversion: 6 */
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

import Vue from 'vue';
import Create from './Create.vue';
import {DatetimePicker} from 'nextcloud-vue';
import Controls from './components/_base-controls.vue';
import SideBarClose from './components/sideBarClose.vue';
import UserDiv from './components/_base-UserDiv.vue';
import SideBar from './components/_base-SideBar.vue';
import ShareDiv from './components/shareDiv.vue';

Vue.config.debug = true
Vue.config.devTools = true
Vue.component('Controls', Controls);
Vue.component('DatePicker', DatetimePicker);
Vue.component('SideBarClose', SideBarClose);
Vue.component('UserDiv', UserDiv);
Vue.component('SideBar', SideBar);
Vue.component('ShareDiv', ShareDiv);

Vue.mixin({
	methods: {
		t: function(app, text, vars, count, options) {
			return OC.L10N.translate(app, text, vars, count, options)
		},
		n: function(app, textSingular, textPlural, count, vars, options) {
			return OC.L10N.translatePlural(app, textSingular, textPlural, count, vars, options)
		}
	}
});

new Vue({
	el: '#create-poll',
	render: h => h(Create)
});

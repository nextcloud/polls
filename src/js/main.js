/*jshint esversion: 6 */
import Vue from 'vue';
import Create from './Create.vue';
import { DatetimePicker } from 'nextcloud-vue';
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

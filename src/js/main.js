/*jshint esversion: 6 */
import Vue from 'vue';
import Create from './Create.vue';
import { DatetimePicker } from 'nextcloud-vue';
import Breadcrump from './components/breadcrump.vue';
import CloudDiv from './components/_base-CloudDiv.vue';
import ShareDiv from './components/shareDiv.vue';
import SideBarClose from './components/sideBarClose.vue';
import UserDiv from './components/_base-UserDiv.vue';

Vue.config.debug = true
Vue.config.devTools = true
Vue.component('Breadcrump', Breadcrump);
Vue.component('CloudDiv', CloudDiv);
Vue.component('DatePicker', DatetimePicker);
Vue.component('ShareDiv', ShareDiv);
Vue.component('SideBarClose', SideBarClose);
Vue.component('UserDiv', UserDiv);

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

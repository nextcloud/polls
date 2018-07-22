import Vue from 'vue'
import Create from './Create.vue'
import UserDiv from './components/_base-UserDiv.vue';
import CloudDiv from './components/_base-CloudDiv.vue';
import DatePickerInput from './components/_base-DatePickerInput.vue';
import TimePicker from './components/_base-TimePicker.vue';

Vue.config.devtools;

Vue.component('user-div', UserDiv);
Vue.component('cloud-div', CloudDiv);
Vue.component('date-picker-input', DatePickerInput);
Vue.component('time-picker', TimePicker);

Vue.mixin({
	methods: {
		t: function(app, text, vars, count, options) {
			return OC.L10N.translate(app, text, vars, count, options);
		},
		n: function(app, textSingular, textPlural, count, vars, options) {
			return OC.L10N.translatePlural(app, textSingular, textPlural, count, vars, options);
		}
	}
});

new Vue({
  el: '#create-poll',
  render: h => h(Create)
});

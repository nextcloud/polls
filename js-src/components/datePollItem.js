/* global: Vue */
Vue.component('date-poll-item', {
	props: ['option'],
	template: 
		'<li class="flex-row poll-box">' +
		'	<div class="poll-item">{{option.timestamp | localFullDate}}</div>' +
		'	<div class="flex-row options">' +
		'		<a @click="$emit(\'remove\')" class="icon-delete"></a>' +
		'	</div>' +
		'</li>',

});
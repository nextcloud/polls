Vue.component('date-poll-item', {
	props: ['option'],
	template: 
		'<li class="flex-row poll-box">' +
		'	<div class="poll-item">{{option.dateOnly}}</div>' +
		'	<div class="poll-item">{{option.time}}</div>' +
		'	<div class="flex-row options">' +
		'		<a @click="$emit(\'remove\')" class="icon-delete"></a>' +
		'	</div>' +
		'</li>'
});
// Mocks
var pollDates = [
			{ id:0, fromDate: '2018-02-02', fromTimestamp: '1517529600000'},
			{ id:1, fromDate: '2018-02-04', fromTimestamp: '1517738400000', fromTime: '11:00' },
			{ id:2, fromDate: '2018-02-03', fromTimestamp: '1517644800000', fromTime: '09:00' },
			{ id:3, fromDate: '2018-02-05', fromTimestamp: '1517824800000', fromTime: '11:00'},
			{ id:4, fromDate: '2018-02-08', fromTimestamp: '1518048000000'},
			{ id:5, fromDate: '2018-02-08', fromTimestamp: '1518044400000', fromTime: '00:00'}
];

var pollTexts = [
			{ id:0, text: 'Option Nr. 1' },
			{ id:1, text: 'Option Nr. 2' },
			{ id:2, text: 'Option Nr. 3' },
			{ id:3, text: 'Option Nr. 4' }
];

//Templates
var datePickerTemplate = '<input size="10" maxlength="10" :placeholder="placeholder" \
	v-bind:value="value"\
	v-on:input="$emit(\'input\', $event.target.value)" />';
	
var timePickerTemplate = '<input size="10" maxlength="10" :placeholder="placeholder" \
	v-bind:value="value"\
	v-on:input="$emit(\'input\', $event.target.value)" />';
	
var datePollItemTemplate = '<li class="flex-row poll-option">\
			<div class="flex-column from-date" :data-timestamp="option.fromTimestamp">{{ option.fromDate }}</div>\
			<button class="flex-column from-date button btn" v-if="option.fromTime == null">Add time option</button>\
			<div class="flex-column from-time" v-else>{{ option.fromTime }}</div>\
			<div class="flex-column options"><a @click="$emit(\'remove\')" class="icon icon-delete svg delete-poll"></a></div>\
			<div class="flex-column options"><a class="icon icon-rename svg edit-poll"></a></div>\
			<div class="flex-column options"><a class="icon icon-clippy svg copy-poll"></a></div>\
			</li>';
var textPollItemTemplate = '<li>{{ option.text }}\
			<a @click="$emit(\'remove\')" class="icon-delete svg delete-poll"></a>\
			</li>';

Vue.config.devtools = true;
// inject jQuery date picker

Vue.component('date-picker', {
	props: ['value', 'placeholder', 'dateFormat', 'minDate'],
	template: datePickerTemplate,
	mounted: function() {
		$(this.$el).datepicker({
			dateFormat: this.dateFormat,
			onClose: this.onClose,
			minDate: this.minDate
		})
	},
	methods: {
		onClose(date) {
			this.$emit('input', date)
		}
	},
	beforeDestroy: function() {
		$(this.$el).datepicker('hide').datepicker('destroy');
	},
	watch: {
		value(newVal) { $(this.el).datepicker('setDate', newVal); }
	}
});

Vue.component('date-poll-table', {
	template: '<div>Here is the table for date polls</div>'
});

Vue.component('date-poll-item', {
  props: ['option'],
  template: datePollItemTemplate
});

Vue.component('text-poll-item', {
  props: ['option'],
  template: textPollItemTemplate
});

Vue.component('text-poll-table', {
	template: '<div>Here is the table for text polls</div>'
});

var setOptions = new Vue({
	el: '#app',
	data: {
		title: '',
		description: '',
		pollType: 'datePoll',
		accessType: 'registered',
		maybeOptionAllowed: true,
		maybeOptionAllowedLabel: t('polls', 'Allow maybe option'),
		anonymousType: false,
		anonymousLabel: t('polls', 'Anonymous'),
		trueAnonymousType: false,
		trueAnonymousLabel: t('polls', 'Hide user names for admin'),
		expiration: false,
		expirationDate: null,
		expirationDateLabel: t('polls', 'Expires'),
		placeholder: '',
		newPollDate: '',
		newPollText: '',
		pollDates: _.sortBy(this.pollDates, 'fromTimestamp'),
		pollTexts: pollTexts,
		nextPollDateId: pollDates.length,
		nextPollTextId: pollTexts.length
	},

	created: function() {
		for (i = 0; i < this.pollDates.length; i++) {
			if (this.pollDates[i].fromTime == null) {
					this.pollDates[i].fromTimestamp = new Date(this.pollDates[i].fromDate).getTime();
				} else {
					this.pollDates[i].fromTimestamp = new Date(this.pollDates[i].fromDate + 'T' + this.pollDates[i].fromTime).getTime();
				}
		} 
	},

	events: {
		'datepicker-changed': function() {
			this.from = document.getElementById('from').value;
			this.to = document.getElementById('to').value
		}
	},
    
	methods: {
		updateDate: function(date) {
			this.date = date;
		},
		addNewPollDate: function () {
			this.pollDates.push({
				id: this.nextPollDateId++,
				fromDate: this.newPollDate,
				fromTimestamp: new Date(this.newPollDate).getTime(),
			})
			this.newPollDate = '';
			this.pollDates = _.sortBy(this.pollDates, 'fromTimestamp')
		},
		addNewPollText: function () {
			this.pollTexts.push({
				id: this.nextPollTextId++,
				text: this.newPollText
			})
			this.newPollText = ''
		}
	}	
  
});


function deletePoll($pollEl) {
	var str = t('polls', 'Do you really want to delete this new poll?') + '\n\n' + $($pollEl).attr('data-value');
	if (confirm(str)) {
		var form = document.form_delete_poll;
		var hiddenId = document.createElement("input");
		hiddenId.setAttribute("name", "pollId");
		hiddenId.setAttribute("type", "hidden");
		form.appendChild(hiddenId);
		form.elements.pollId.value = $pollEl.id.split('_')[2];
		form.submit();
	}
};



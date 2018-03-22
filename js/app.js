/** global: Vue */

var today = new Date();
var todayTS = new Date(today).getTime();

//Templates
var datePickerTemplate = '<input size="10" maxlength="10" :placeholder="placeholder" ' +
	'v-bind:value="value" ' +
	'v-on:input="$emit(\'input\', $event.target.value)" />';
	
var datePickerInlineTemplate = '<div v-bind:value="value" > </div>';
	
var timePickerTemplate = '<input size="10" maxlength="10" :placeholder="placeholder"  ' +
	'v-bind:value="value" ' +
	'v-on:input="$emit(\'input\', $event.target.value)" />';


var datePollItemTemplate =  '<li class="flex-row date-box">' +
							'	<div class="flex-column">' +
							'		<div class="month">{{option.fromMonth}} \'{{option.fromYear}}</div>' +
							'		<div class="dayow">{{option.fromDow}}, {{option.fromDay}}, {{option.fromTime}}</div>' +
							'	</div>' +
							'	<div class="options-box flex-column">' +
							'		<div class="options"><a @click="$emit(\'remove\')" class="icon icon-delete svg delete-poll"></a></div>' +
							'		<div class="options"><a class="icon icon-clippy svg copy-poll"></a></div>' +
							'	</div>' +
							'</li>';
							
var textPollItemTemplate = 	'<li class="flex-row text-box">' +
							'	<div class="flex-row">' +
							'		<div class="text">{{ option.text }}</div>' +
							'	</div>' +
							'	<div class="options-box flex-row">' +
							'		<div class="options"><a @click="$emit(\'remove\')" class="icon icon-delete svg delete-poll"></a></div>' +
							'	</div>' +
							'</li>';
							// '	<div class=action> <a @click="$emit(\'remove\')" class="icon-delete svg delete-poll"></a></div>'+

Vue.config.devtools = true;
// inject jQuery date picker

Vue.component('autor-div', {
	template: 	'<div>' +
				'	<div class="description leftLabel">{{ownerLabel}}</div>' + 
				'	<div class="avatar has-tooltip-bottom" style="height: 32px; width: 32px;" >' +
				'		<img :src="avatarURL" width="32" height="32">' +
				'	</div>' +
				'	<div class="author">{{displayName}}</div>' +
				'</div>',
	data: function () {
		return {
			ownerLabel: t('polls', 'Owner'),
			displayName: OC.getCurrentUser().displayName,
			avatarURL: OC.generateUrl(
				'/avatar/{user}/{size}?v={version}',
				{
					user: OC.getCurrentUser().uid,
					size: Math.ceil(32 * window.devicePixelRatio),
					version: oc_userconfig.avatar.version
			})
		}
	}
}) ;

Vue.component('side-bar-close', {
	template: 	'<div class="close flex-row">' +
				'	<a id="closeDetails" class="close icon-close has-tooltip-bottom" :title="closeDetailLabel" href="#" :alt="closeDetailLabelAlt"></a>' +
				'</div>',
	data: function () {
		return {
			closeDetailLabel: t('Close details'),
			closeDetailLabelAlt: t('Close')
		}
	}
});

Vue.component('date-picker', {
	props: ['value', 'placeholder', 'dateFormat', 'minDate'],
	template: datePickerTemplate,
	mounted: function() {
		$(this.$el).datepicker({
			dateFormat: this.dateFormat,
			onClose: this.onClose,
			minDate: this.minDate
		});
	},
	methods: {
		onClose(date) {
			this.$emit('input', date);
		}
	},
	beforeDestroy: function() {
		$(this.$el).datepicker('hide').datepicker('destroy');
	},
	watch: {
		value(newVal) { $(this.el).datepicker('setDate', newVal); }
	}
});

Vue.component('date-picker-inline', {
	props: ['value', 'placeholder', 'dateFormat', 'minDate'],
	template: '<div v-bind:value="value" > </div>',
	mounted: function() {
		$(this.$el).datepicker({
			dateFormat: this.dateFormat,
			onSelect: this.onSelect,
			minDate: this.minDate
		});
	},
	methods: {
		onSelect(date) {
			newPoll.newPollDate = date;
			newPoll.addNewPollDate();
		}
	},
	beforeDestroy: function() {
		$(this.$el).datepicker('hide').datepicker('destroy');
	},
	watch: {
		value(newVal) { $(this.el).datepicker('setDate', newVal); }
	}
});

Vue.component('time-picker', {
	props: ['value', 'placeholder', 'dateFormat', 'minDate'],
	template: timePickerTemplate,
	mounted: function() {
		$(this.$el).timepicker({
			dateFormat: this.dateFormat,
			onClose: this.onClose,
			minDate: this.minDate
		});
	},
	methods: {
		onClose(date) {
			this.$emit('input', date);
		}
	},
	beforeDestroy: function() {
		$(this.$el).timepicker('hide').timepicker('destroy');
	},
	watch: {
		value(newVal) { $(this.el).timepicker('setDate', newVal); }
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

var newPoll = new Vue({
	el: '#app',
	data: {
		today: today,
		lang: OC.getLocale(),
		todayTS: todayTS,
		title: '',
		description: '',
		pollType: 'datePoll',
		accessType: 'registered',
		maybeOptionAllowed: true,
		maybeOptionAllowedLabel: t('polls', 'Allow maybe option'),
		anonymousType: false,
		anonymousLabel: t('polls', 'Anonymous poll'),
		trueAnonymousType: false,
		trueAnonymousLabel: t('polls', 'Hide user names for admin'),
		expiration: false,
		expirationDate: null,
		expirationDateLabel: t('polls', 'Expires'),
		placeholder: '',
		newPollDate: '',
		newPollTime: '',
		newPollText: '',
		pollDates: [],
		pollTexts:[],
		nextPollDateId: 0,
		nextPollTextId: 0,
		OC: OC,
		OCP: OCP,
		OCA: OCA
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
	
	mounted: function() {
// Mocks
			this.pollDates.push({ id:0, fromDate: '2018-02-02', fromTime: '11:00', fromDow:'Fr', fromDay:'02', fromMonth:'Feb', fromYear:'18', fromTimestamp: '1517529600000'});
			// this.pollDates.push({ id:1, fromDate: '2018-02-04', fromTimestamp: '1517738400000', fromTime: '11:00' });
			// this.pollDates.push({ id:2, fromDate: '2018-02-03', fromTimestamp: '1517644800000', fromTime: '09:00' });
			// this.pollDates.push({ id:3, fromDate: '2018-02-05', fromTimestamp: '1517824800000', fromTime: '11:00'});
			// this.pollDates.push({ id:4, fromDate: '2018-02-08', fromTimestamp: '1518048000000'});
			// this.pollDates.push({ id:5, fromDate: '2018-02-08', fromTimestamp: '1518044400000', fromTime: '00:00'});
			this.nextPollDateId = 1;

			this.pollTexts.push({ id:0, text: 'Option Nr. 1' });
			this.pollTexts.push({ id:1, text: 'Option Nr. 2' });
			this.pollTexts.push({ id:2, text: 'Option Nr. 3' });
			this.pollTexts.push({ id:3, text: 'Option Nr. 4' });
			this.nextPollTextId = 6;
	},
	
	events: {
		'datepicker-changed': function() {
			this.from = document.getElementById('from').value;
			this.to = document.getElementById('to').value
		}
	},
    watch: {
		pollDates: {
			handler: function (val, oldVal) {
			},
			deep: true
		}
	},
	methods: {
		updateDate: function(date) {
			this.date = date;
		},
		addNewPollDate: function () {
			var timeStamp = new Date(this.newPollDate + 'T' + this .newPollTime +':00');
			this.pollDates.push({
				id: this.nextPollDateId++,
				fromTimestamp: timeStamp.getTime(),
				fromTimeLocal: timeStamp.toLocaleString(),
				fromTimeUTC: timeStamp,
				fromTimeLocal: timeStamp.toLocaleDateString(window.navigator.language, {hour: 'numeric', minute:'2-digit', timeZoneName:'short'}),
				fromTime: timeStamp.toLocaleTimeString(window.navigator.language, {hour: 'numeric', minute:'2-digit'}),
				fromDate: timeStamp.toLocaleDateString(window.navigator.language, {day: 'numeric', month:'2-digit', year:'numeric'}),
				fromDay: timeStamp.toLocaleString(window.navigator.language, {day: 'numeric'}),
				fromMonth: timeStamp.toLocaleString(window.navigator.language, {month: 'short'}),
				fromYear: timeStamp.toLocaleString(window.navigator.language, {year: '2-digit'}),
				fromDow: timeStamp.toLocaleString(window.navigator.language, {weekday: 'short'})
			})
/* 			this.newPollDate = '';
			this.newPollTime = '';
 */			this.pollDates = _.sortBy(this.pollDates, 'fromTimestamp')
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



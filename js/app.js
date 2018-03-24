/** global: Vue */
Vue.config.devtools = true;

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
});

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

// inject jQuery date picker
Vue.component('date-picker', {
	props: ['value', 'placeholder', 'dateFormat', 'minDate'],
	template: 	'<input size="10" maxlength="10" :placeholder="placeholder" ' +
				'v-bind:value="value" ' +
				'v-on:input="$emit(\'input\', $event.target.value)" />',
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

// inject jQuery date picker inline
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

// inject jQuery time picker
Vue.component('time-picker', {
	props: ['value', 'placeholder', 'dateFormat', 'minDate'],
	template: 
			'<input size="10" maxlength="10" :placeholder="placeholder"  ' +
			'v-bind:value="value" ' +
			'v-on:input="$emit(\'input\', $event.target.value)" />',
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

Vue.component('date-poll-item', {
	props: ['option'],
	template: 
			'<li class="flex-row poll-box">' +
		'		<div class="poll-item">{{option.fromTimeLocal}}</div>' +
		'		<div class="options"><a @click="$emit(\'remove\')" class="icon icon-delete svg delete-poll"></a></div>' +
			'</li>'
});

Vue.component('text-poll-item', {
  props: ['option'],
  template: 
			'<li class="flex-row poll-box">' +
		'		<div class="poll-item">{{ option.text }}</div>' +
		'		<div class="options"><a @click="$emit(\'remove\')" class="icon icon-delete svg delete-poll"></a></div>' +
			'</li>'
});

var newPoll = new Vue({
	el: '#app',
	data: {
		label: {
			is_anonymous: t('polls', 'Anonymous poll'),
			full_anonymous: t('polls', 'Hide user names for admin'),
			expirationDate: t('polls', 'Expires'),
			maybeOptionAllowed: t('polls', 'Allow maybe option')
		},
		polls_event: {
			poll_id: 0,
			hash: '',
			pollType: 'datePoll',
			title: '',
			description: '',
			owner:'',
			created: '',
			accessType: 'registered',
			expiration: false,
			expirationDate: null,
			is_anonymous: false,
			full_anonymous: false,
			maybeOptionAllowed: true,
		},
		votes: {
			pollDates: [],
			pollTexts:[]
		},
		lang: OC.getLocale(),
		placeholder: '',
		newPollDate: '',
		newPollTime: '',
		newPollText: '',
		nextPollDateId: 0,
		nextPollTextId: 0
	},
	created: function() {
		for (i = 0; i < this.votes.pollDates.length; i++) {
			if (this.votes.pollDates[i].fromTime == null) {
					this.votes.pollDates[i].fromTimestamp = new Date(this.votes.pollDates[i].fromDate).getTime();
				} else {
					this.votes.pollDates[i].fromTimestamp = new Date(this.votes.pollDates[i].fromDate + 'T' + this.votes.pollDates[i].fromTime).getTime();
				}
		} 
	},
	mounted: function() {
// Mocks
			this.addNewPollDate('2018-02-04', '11:00');
			this.addNewPollDate('2018-02-08', '11:00');

			this.addNewPollText('Option Nr. 1');
			this.addNewPollText('Option Nr. 2');
			this.addNewPollText('Option Nr. 3');
			this.addNewPollText('Option Nr. 4');

	},
	methods: {
		addNewPollDate: function (newPollDate, newPollTime) {
			if (newPollDate != null) {
				this.newPollDate = newPollDate
			}
			if (newPollTime != null) {
				this.newPollTime = newPollTime
			}
			var timeStamp = new Date(this.newPollDate + 'T' + this .newPollTime +':00');
			this.votes.pollDates.push({
				id: this.nextPollDateId++,
				fromTimestamp: timeStamp.getTime(),
				fromTimeLocal: timeStamp.toLocaleString(),
				fromTimeUTC: timeStamp,
				fromTimeLocal: timeStamp.toLocaleDateString(this.lang, {hour: 'numeric', minute:'2-digit', timeZoneName:'short'}),
				fromTime: timeStamp.toLocaleTimeString(this.lang, {hour: 'numeric', minute:'2-digit'}),
				fromDate: timeStamp.toLocaleDateString(this.lang, {day: 'numeric', month:'2-digit', year:'numeric'}),
				fromDay: timeStamp.toLocaleString(this.lang, {day: 'numeric'}),
				fromMonth: timeStamp.toLocaleString(this.lang, {month: 'short'}),
				fromYear: timeStamp.toLocaleString(this.lang, {year: '2-digit'}),
				fromDow: timeStamp.toLocaleString(this.lang, {weekday: 'short'})
			})
			this.votes.pollDates = _.sortBy(this.votes.pollDates, 'fromTimestamp')
		},
		addNewPollText: function (newPollText) {
			if (newPollText != null) {
				this.newPollText = newPollText
			}
			this.votes.pollTexts.push({
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



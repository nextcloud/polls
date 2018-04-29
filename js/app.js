/** global: Vue */
Vue.config.devtools = true;

Vue.component('breadcrump', {
	props: ['intitle'],
	template: 	
		'<div id="breadcrump">' +
		'	<div class="crumb svg" data-dir="/">' +
		'		<a :href="home">' +
		'			<img class="svg" :src="imagePath" alt="Home">' +
		'		</a>' +
		'	</div>' +
		'	<div class="crumb svg last">' +
		'		<span v-text="intitle" />' +
		'	</div>' +
		'</div>',
	data: function () {
		return {
			home: OC.generateUrl('apps/polls'),
			imagePath: OC.imagePath('core', 'places/home.svg'),
		}
	}
});


Vue.component('author-div', {
	template: 	
		'<div>' +
		'	<div class="description leftLabel">{{ownerLabel}}</div>' + 
		'	<div class="avatar has-tooltip-bottom" style="height: 32px; width: 32px;" >' +
		'		<img :src="avatarURL" width="32" height="32">' +
		'	</div>' +
		'	<div class="author">{{displayName}}</div>' +
		'</div>',
	data: function () {
		return {
			ownerLabel: t('polls', 'Owner'),
			displayName: OC.getCurrentUser().uid,
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
	template:
		'<div class="close flex-row">' +
		'	<a id="closeDetails" @:click="hideSidebar" class="close icon-close has-tooltip-bottom" :title="closeDetailLabel" href="#" :alt="closeDetailLabelAlt"></a>' +
		'</div>',
	data: function () {
		return {
			closeDetailLabel: t('Close details'),
			closeDetailLabelAlt: t('Close')
		}
	},
	methods: {
		hideSidebar: function () {
			OC.Apps.hideAppSidebar();
		}
	}
});

// inject jQuery date picker
Vue.component('date-picker', {
	props: ['value', 'placeholder', 'dateFormat', 'minDate'],
	template:
		'<input size="10" maxlength="10" :placeholder="placeholder" ' +
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
		'	<div class="poll-item">{{option.fromTimeLocal}}</div>' +
		'	<div class="flex-row options">' +
		'		<a @click="$emit(\'remove\')" class="icon-delete"></a>' +
		'	</div>' +
		'</li>'
});

Vue.component('text-poll-item', {
	props: ['option'],
	template: 
		'<li class="flex-row poll-box">' +
		'	<div class="poll-item">{{ option.text }}</div>' +
		'	<div class="flex-row options">' +
		'		<a @click="$emit(\'remove\')" class="icon icon-delete svg delete-poll"></a>' +
		'</div>' +
		'</li>'
});

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


var newPoll = new Vue({
	el: '#app',
	data: {
		poll: {
			mode: 'create',
			event: {
				id: 0,
				hash: '',
				type: 'datePoll',
				title: '',
				description: '',
				owner:'',
				created: '',
				access: 'public',
				expiration: false,
				expire: null,
				is_anonymous: false,
				full_anonymous: false,
				disallowMaybe: false,
			},
			options: {
				pollDates: [],
				pollTexts:[]
			}
		},
		lang: OC.getLocale(),
		placeholder: '',
		newPollDate: '',
		newPollTime: '',
		newPollText: '',
		nextPollDateId: 0,
		nextPollTextId: 0,
		protect: false,
		sidebar: false
	},

	mounted: function() {
		this.poll.event.hash = document.getElementById("app").getAttribute("data-hash"); 

		if (!this.poll.event.hash == '') {
			this.loadPoll(this.poll.event.hash);
			this.protect = true;
			this.poll.mode = 'edit';

		}
	},
	computed: {
		title: function () {
			if (this.poll.event.title == '') {
				return t('poll','Create new poll')
			} else {
				return this.poll.event.title
			}
		}
	},
	methods: {
		switchSidebar: function() {
			this.sidebar = !this.sidebar;
		},

		addNewPollDate: function (newPollDate, newPollTime) {
			if (newPollDate != null) {
				this.newPollDate = newPollDate
			}
			if (newPollTime != null) {
				this.newPollTime = newPollTime
			}
			if (this.newPollTime == '') {
				var timeStamp = new Date(this.newPollDate);
			} else {
				var timeStamp = new Date(this.newPollDate + 'T' + this .newPollTime +':00');
			}
			this.poll.options.pollDates.push({
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
			this.poll.options.pollDates = _.sortBy(this.poll.options.pollDates, 'fromTimestamp')
		},
		
		addNewPollText: function (newPollText) {
			if (newPollText != null) {
				this.newPollText = newPollText
			}
			this.poll.options.pollTexts.push({
				id: this.nextPollTextId++,
				text: this.newPollText
			})
			this.newPollText = ''
		},
		writePoll: function (mode) {
			// console.log(JSON.stringify(this.poll));
			if (mode !='') {
				this.poll.mode = mode
			}
			axios.post(OC.generateUrl('apps/polls/add'), this.poll)
				.then(function (response) {
					console.log(response);
				})
				.catch(function (error) {
				console.log('error');
			});
		},
		loadPoll: function (hash) {
			axios.get(OC.generateUrl('apps/polls/get/poll/' + hash))
			.then((response) => {
				// console.log(response.data);
				this.poll = response.data.poll;
				if (response.data.poll.event.type == 'datePoll') {
					for (i = 0; i < response.data.poll.options.pollTexts.length; i++) {
						// date = new Date(response.data.poll.options.pollTexts[i].text);
						this.addNewPollDate(new Date(response.data.poll.options.pollTexts[i].text) +' UTC');
					}
				this.poll.options.pollTexts = [];
				}
				// }
			}, (error) => {
				console.log(error);
			});

		
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



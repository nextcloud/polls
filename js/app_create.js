/** global: Vue */

Vue.component('breadcrump', {
	props: ['intitle'],
	template: 	
		'<div id="breadcrump">' +
		'	<div class="crumb svg">' +
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

Vue.component('date-picker-input', {
	props: ['value', 'placeholder', 'dateFormat'],
	template:
		'<input size="10" maxlength="10" ' +
		':placeholder="placeholder" ' +
		':value="value" ' +
		':dateFormat="dateFormat" ' +
		'@input="$emit(\'input\', $event.target.value)" />',
	mounted: function() {
		$(this.$el).datepicker({
			dateFormat: this.dateFormat,
			onSelect: this.onClose
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
	
	props: {		
		minDate: {
			default: null
		},
		// get moment.js locale information, because datepicker initializes in default localization
		localeData: {
		},
		useTime: {
			default: '00:00'
		}
	},
	
	template: 
		'<div class="datepicker-inline"></div>',
	
	mounted: function() {
		$(this.$el).datepicker({
			dateFormat: 'yy-mm-dd',
			minDate: this.minDate,
			monthNames: this.localeData.months(),
			dayNamesMin: this.localeData.weekdaysMin(),
			dayNamesShort: this.localeData.weekdaysShort(),
			firstDay: +this.localeData.firstDayOfWeek(),
			showOtherMonths: true,
			selectOtherMonths: true,
			onSelect: this.onSelect
		});
	},
	methods: {
		onSelect(date) {
			if (this.useTime === '') {
				useTime = '00:00'
			} else {
				useTime= this.useTime
			};
			this.$emit('selected', date + ' ' + useTime)
		}
	},
	beforeDestroy: function() {
		$(this.$el).datepicker('hide').datepicker('destroy');
	}
		
});

Vue.component('time-picker', {
	props: ['value', 'placeholder'],
	template: 
		'<input size="10" maxlength="10" :placeholder="placeholder"  ' +
		':value="value" ' +
		'@input="$emit(\'input\', $event.target.value)" />',
	mounted: function() {
		$(this.$el).timepicker({
			onClose: this.onClose
		});
	},
	methods: {
		onClose(time) {
			this.$emit('input', time);
		}
	},
	beforeDestroy: function() {
		$(this.$el).timepicker('hide').timepicker('destroy');
	},
	watch: {
		value(newVal) { $(this.el).timepicker('setTime', newVal); }
	}
});

Vue.component('date-poll-item', {
	props: ['option'],
	template: 
		'<li class="flex-row poll-box">' +
		'	<div class="poll-item">{{option.date}}</div>' +
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
		localeData: moment.localeData(moment.locale(OC.getLocale())),
		placeholder: '',
		newPollDate: '',
		newPollTime: '',
		newPollText: '',
		nextPollDateId: 0,
		nextPollTextId: 0,
		protect: false,
		sidebar: false,
		titleEmpty: false
	},

	mounted: function() {
		this.poll.event.hash = document.getElementById("app").getAttribute("data-hash"); 
		if (!this.poll.event.hash == '') {
			this.loadPoll(this.poll.event.hash);
			this.protect = true;
			this.poll.mode = 'edit';
		};
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
 			if (newPollTime !== undefined) {
				this.newPollDate = moment(newPollDate +' ' + newPollTime)
			} else {
				this.newPollDate = moment(newPollDate)
			}
			this.poll.options.pollDates.push({
				id: this.nextPollDateId++,
				timestamp: moment(newPollDate).unix(),
				date: moment(newPollDate).format('llll')
			})
			this.poll.options.pollDates = _.sortBy(this.poll.options.pollDates, 'timestamp')
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
			if (mode !='') {
				this.poll.mode = mode
			}
			if (this.poll.event.title.length == 0) {
				this.titleEmpty = true;
			} else {
				this.titleEmpty = false;
				axios.post(OC.generateUrl('apps/polls/write'), this.poll)
					.then((response) => {
						console.log(response);
						this.poll.mode = 'edit';
 						this.poll.event.hash = response.data.hash;
						this.poll.event.id = response.data.id;
						window.location.href = OC.generateUrl('apps/polls/edit/' + this.poll.event.hash);
					}, (error) => {
						console.log(error.response);
				});
			};
		},
		
		loadPoll: function (hash) {
			axios.get(OC.generateUrl('apps/polls/get/poll/' + hash))
			.then((response) => {
				console.log(response);
				this.poll = response.data.poll;
				if (response.data.poll.event.type == 'datePoll') {
					for (i = 0; i < response.data.poll.options.pollTexts.length; i++) {
						this.addNewPollDate(new Date(response.data.poll.options.pollTexts[i].text) +' UTC');
					}
				this.poll.options.pollTexts = [];
				}
			}, (error) => {
				console.log(error.response);
			});
		}
	}	
});
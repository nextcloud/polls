/* global: Vue */
/* global: axios */
Vue.config.devtools = true;

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
				isAnonymous: false,
				fullAnonymous: false,
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
		if (this.poll.event.hash !== '') {
			this.loadPoll(this.poll.event.hash);
			this.protect = true;
			this.poll.mode = 'edit';
		}
	},
	
	computed: {
		title: function () {
			if (this.poll.event.title === '') {
				return t('poll','Create new poll');
			} else {
				return this.poll.event.title;
			}
		}
	},
	
	methods: {
		switchSidebar: function() {
			this.sidebar = !this.sidebar;
		},

		addNewPollDate: function (newPollDate, newPollTime) {
 			if (newPollTime !== undefined) {
				this.newPollDate = moment(newPollDate +' ' + newPollTime);
			} else {
				this.newPollDate = moment(newPollDate);
			}
			this.poll.options.pollDates.push({
				id: this.nextPollDateId++,
				timestamp: moment(newPollDate).unix(),
				date: moment(newPollDate).format('llll'),
				time: moment(newPollDate).format('LT'),
				dateOnly: moment(newPollDate).format('dddd[,] ll')
			});
			this.poll.options.pollDates = _.sortBy(this.poll.options.pollDates, 'timestamp');
		},
		
		addNewPollText: function (newPollText) {
			if (newPollText !== null) {
				this.newPollText = newPollText;
			}
			this.poll.options.pollTexts.push({
				id: this.nextPollTextId++,
				text: this.newPollText
			});
			this.newPollText = '';
		},

		writePoll: function (mode) {
			if (mode !== '') {
				this.poll.mode = mode;
			}
			if (this.poll.event.title.length === 0) {
				this.titleEmpty = true;
			} else {
				this.titleEmpty = false;
				axios.post(OC.generateUrl('apps/polls/write'), this.poll)
					.then((response) => {
						this.poll.mode = 'edit';
 						this.poll.event.hash = response.data.hash;
						this.poll.event.id = response.data.id;
						window.location.href = OC.generateUrl('apps/polls/edit/' + this.poll.event.hash);
					}, (error) => {
						console.log(error.response);
				});
			}
		},
		
		loadPoll: function (hash) {
			axios.get(OC.generateUrl('apps/polls/get/poll/' + hash))
			.then((response) => {
				var i;
				this.poll = response.data.poll;
				if (response.data.poll.event.type === 'datePoll') {
					for (i = 0; i < response.data.poll.options.pollTexts.length; i++) {
						this.addNewPollDate(new Date(moment.utc(response.data.poll.options.pollTexts[i].text)));
						// this.addNewPollDate(new Date(response.data.poll.options.pollTexts[i].text)  +' UTC');
					}
				this.poll.options.pollTexts = [];
				}
			}, (error) => {
				console.log(error.response);
			});
		}
	}	
});
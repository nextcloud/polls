var pollDates = [
			{ id:0, fromDate: '2018-02-02', fromTime: '' },
			{ id:1, fromDate: '2018-02-04', fromTime: '11:00' },
			{ id:2, fromDate: '2018-02-03', fromTime: '09:00' },
			{ id:3, fromDate: '2018-02-05', fromTime: '11:00', toDate: '2018-02-06', toTime: '17:00' },
			{ id:4, fromDate: '2018-02-08', fromTime: '', toDate: '2018-02-09', toTime: '' }
];

var pollTexts = [
			{ id:0, text: 'Option Nr. 1' },
			{ id:1, text: 'Option Nr. 2' },
			{ id:2, text: 'Option Nr. 3' },
			{ id:3, text: 'Option Nr. 4' }
];
// inject jQuery date picker
Vue.component('date-picker', {
  template: '<input placeholder="' + 
			t('polls', 'Expiration date') + '"/>',
  props: [ 'dateFormat', 'date' ],
  
  mounted: function() {
  var self = this;
  
  $(this.$el).datepicker({
    dateFormat: this.dateFormat,
    onSelect: function(date) {
      self.$emit('input', date);
    }
  });
  
  $(this.$el).datepicker('setDate', this.date)
  },
  beforeDestroy: function() {
    $(this.$el).datepicker('hide').datepicker('destroy');
  },
  watch: {
    'date': function (value) {
      $(this.$el).datepicker('setDate', value)
    }
  }
});

Vue.component('date-poll-table', {
	template: '<div>Here is the table for date polls</div>'
});

Vue.component('date-poll-item', {
  props: ['option'],
  template: '<li>{{ option.fromDate }} {{ option.fromTime }} {{ option.fromTimestamp }}\
			{{ option.toDate }} {{ option.toTime }} {{ option.toTimestamp }}\
			<a v-on:click="$emit(\'remove\')" class="icon-delete svg delete-poll"></a>\
			</li>'
});

Vue.component('text-poll-item', {
  props: ['option'],
  template: '<li>{{ option.text }}\
			<a v-on:click="$emit(\'remove\')" class="icon-delete svg delete-poll"></a>\
			</li>'
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
		anonymousType: false,
		anonymousLabel: t('polls', 'Anonymous'),
		trueAnonymousType: false,
		trueAnonymousLabel: t('polls', 'Hide user names for admin'),
		expiration: false,
		expirationDate: null,
		expirationDateLabel: t('polls', 'Expires'),
		expirationDatePlaceholder: t('polls', 'Expiration date'),
		newPollDate: '',
		newPollText: '',
		pollDates: _.sortBy(this.pollDates, 'fromDate'),
		pollTexts: pollTexts,
		nextPollDateId: pollDates.length,
		nextPollTextId: pollTexts.length
	},
	
	events: {
		'datepicker-changed': function() {
			this.from = document.getElementById('from').value
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
				fromDate: this.newPollDate
			})
			this.newPollDate = ''
			this.pollDates = _.sortBy(this.pollDates, 'fromDate')
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

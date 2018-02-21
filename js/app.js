
// inject jQuery date picker
Vue.component('date-picker', {
  template: '<input/>',
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
	template: '<div>Here comes the Table for date polls</div>'
});

Vue.component('text-poll-table', {
	template: '<div>Here comes the Table for text polls</div>'
});

/* Vue.component('expiration-date-container', {
	template: '<div class="expirationDateContainer">' +
				'<label for="expirationDate" class="hidden-visually" value="">'	+ 
					t('polls', 'Expires') + 
				'</label>' +    
				'<date-picker @update-date="updateDate" date-format="dd.mm.yy" place-holder="' + t('polls', 'Expires') + '" v-once></date-picker>' +
			'</div>'
				// '<input id="expirationDate" class="datepicker hasDatepicker" place-holder="' + t('polls', 'Expires') + '" value="" type="text">' +
}); */

var setOptions = new Vue({
	el: '#app',
	data: {
		title: '',
		description: '',
		pollType: 'datePoll',
		accessType: 'registered',
		anonymousType: false,
		trueAnonymousType: false,
		expiration: false,
		expirationDate: null,
		anonymousLabel: t('polls', 'Anonymous'),
		trueAnonymousLabel: t('polls', 'Hide user names for admin'),
		expirationDateLabel: t('polls', 'Expires')
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

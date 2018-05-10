/* Vue.filter('localFullDate', function (timestamp) {
	if (!timestamp) return '';
	if (!moment(timestamp).isValid()) return 'Invalid Date';
	if (timestamp <  999999999999) timestamp = timestamp *1000;
	return moment(timestamp).format('llll');
});

 */Vue.filter('localFullDate', function (timestamp) {
	return convertTimestamp(timestamp, 'llll');
});

Vue.filter('localDateOnly', function (timestamp) {
	if (!timestamp) return '';
	if (!moment(timestamp).isValid()) return 'Invalid Date';
	if (timestamp <  999999999999) timestamp = timestamp *1000;
	return moment(timestamp).format('dddd[,] ll');
});

Vue.filter('localYear', function (timestamp) {
	if (!timestamp) return '';
	if (!moment(timestamp).isValid()) return 'Invalid Date';
	if (timestamp <  999999999999) timestamp = timestamp *1000;
	return moment(timestamp).format('YYYY');
});

Vue.filter('localMonthNameShort', function (timestamp) {
	if (!timestamp) return '';
	if (!moment(timestamp).isValid()) return 'Invalid Date';
	if (timestamp <  999999999999) timestamp = timestamp *1000;
	return moment(timestamp).format('MMM');
});

Vue.filter('localDowShort', function (timestamp) {
	if (!timestamp) return '';
	if (!moment(timestamp).isValid()) return 'Invalid Date';
	if (timestamp <  999999999999) timestamp = timestamp *1000;
	return moment(timestamp).format('dd');
});

Vue.filter('localDay', function (timestamp) {
	if (!timestamp) return '';
	if (!moment(timestamp).isValid()) return 'Invalid Date';
	if (timestamp <  999999999999) timestamp = timestamp *1000;
	return moment(timestamp).format('D');
});

Vue.filter('localDayOrdinal', function (timestamp) {
	if (!timestamp) return '';
	if (!moment(timestamp).isValid()) return 'Invalid Date';
	if (timestamp <  999999999999) timestamp = timestamp *1000;
	return moment(timestamp).format('Do');
});

Vue.filter('local', function (timestamp) {
	if (!timestamp) return '';
	if (!moment(timestamp).isValid()) return 'Invalid Date';
	if (timestamp <  999999999999) timestamp = timestamp *1000;
	return moment(timestamp).format('dddd[,] ll');
});

Vue.filter('localTime', function (timestamp) {
	if (!timestamp) return '';
	if (!moment(timestamp).isValid()) return 'Invalid Date';
	if (timestamp <  999999999999) timestamp = timestamp *1000;
	return moment(timestamp).format('LT');
});

// UTC variants
Vue.filter('utcDateOnly', function (timestamp) {
	if (!timestamp) return '';
	if (!moment(timestamp).isValid()) return 'Invalid Date';
	if (timestamp <  999999999999) timestamp = timestamp *1000;
	return moment.utc(timestamp).format('dddd[,] ll');
});

Vue.filter('utcTime', function (timestamp) {
	if (!timestamp) return '';
	if (!moment(timestamp).isValid()) return 'Invalid Date';
	if (timestamp <  999999999999) timestamp = timestamp *1000;
	return moment.utc(timestamp).format('LT');
});

Vue.filter('utcFullDate', function (timestamp) {
	if (!timestamp) return '';
	if (!moment(timestamp).isValid()) return 'Invalid Date';
	if (timestamp <  999999999999) timestamp = timestamp *1000;
	return moment.utc(timestamp).format('llll');
});

		convertTimestamp: function convertTimestamp(timestamp, format, utc) {
			if (!timestamp) timestamp = moment().unix();
			if (!moment(timestamp).isValid()) return 'Invalid Date';
			if (timestamp <  999999999999) timestamp = timestamp *1000;
			if (!format) format = 'MM-DD-YYYY';
			if (utc) {
				return moment.utc(timestamp).format(format);
			} else {
				return moment(timestamp).format(format);
			}
		}


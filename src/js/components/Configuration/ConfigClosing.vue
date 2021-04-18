<!--
  - @copyright Copyright (c) 2021 René Gieling <github@dartcafe.de>
  -
  - @author René Gieling <github@dartcafe.de>
  -
  - @license GNU AGPL version 3 or any later version
  -
  - This program is free software: you can redistribute it and/or modify
  - it under the terms of the GNU Affero General Public License as
  - published by the Free Software Foundation, either version 3 of the
  - License, or (at your option) any later version.
  -
  - This program is distributed in the hope that it will be useful,
  - but WITHOUT ANY WARRANTY; without even the implied warranty of
  - MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  - GNU Affero General Public License for more details.
  -
  - You should have received a copy of the GNU Affero General Public License
  - along with this program.  If not, see <http://www.gnu.org/licenses/>.
  -
  -->

<template>
	<div>
		<ButtonDiv
			:icon="closed ? 'icon-polls-open' : 'icon-polls-closed'"
			:title="closed ? t('polls', 'Reopen poll'): t('polls', 'Close poll')"
			@click="switchClosed()" />
		<CheckBoxDiv v-show="!closed" v-model="useExpire" :label="t('polls', 'Closing date')" />
		<DatetimePicker v-show="useExpire && !closed" v-model="expire" v-bind="expirationDatePicker" />
	</div>
</template>

<script>
import { mapState, mapGetters } from 'vuex'
import moment from '@nextcloud/moment'
import { DatetimePicker } from '@nextcloud/vue'
import CheckBoxDiv from '../Base/CheckBoxDiv'

export default {
	name: 'ConfigClosing',

	components: {
		CheckBoxDiv,
		DatetimePicker,
	},

	data() {
		return {
			expirationDatePicker: {
				editable: true,
				minuteStep: 5,
				type: 'datetime',
				format: moment.localeData().longDateFormat('L LT'),
				placeholder: t('polls', 'Closing date'),
				confirm: true,
				lang: {
					formatLocale: {
						firstDayOfWeek: moment.localeData()._week.dow === 0 ? 7 : moment.localeData()._week.dow,
						months: moment.months(),
						monthsShort: moment.monthsShort(),
						weekdays: moment.weekdays(),
						weekdaysMin: moment.weekdaysMin(),
					},
				},
			},
		}
	},

	computed: {
		...mapState({
			poll: (state) => state.poll,
		}),

		...mapGetters({
			closed: 'poll/closed',
		}),

		expire: {
			get() {
				return moment.unix(this.poll.expire)._d
			},
			set(value) {
				this.$store.commit('poll/setProperty', { expire: moment(value).unix() })
				this.$emit('change')
			},
		},

		useExpire: {
			get() {
				return this.poll.expire
			},
			set(value) {
				if (value) {
					this.$store.commit('poll/setProperty', { expire: moment().add(1, 'week').unix() })
				} else {
					this.$store.commit('poll/setProperty', { expire: 0 })
				}
				this.$emit('change')
			},
		},
	},

	methods: {
		switchClosed() {
			if (this.closed) {
				this.$store.commit('poll/setProperty', { expire: 0 })
			} else {
				this.$store.commit('poll/setProperty', { expire: -1 })
			}
			this.$emit('change')

		},
	},
}
</script>

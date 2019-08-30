<!--
  - @copyright Copyright (c) 2018 René Gieling <github@dartcafe.de>
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
	<div v-tooltip.auto="localFullDate" class="date-box">
		<div class="month">
			{{ month }}
		</div>
		<div class="day">
			{{ day }}
		</div>
		<div class="dow">
			{{ dow }}
		</div>
		<div class="year">
			{{ year }}
		</div>
		<div class="time">
			{{ time }}
		</div>
		<a v-if="mode === 'edit'" class="icon-delete" @click="$emit('remove')" />
	</div>
</template>

<script>
import moment from 'moment'

export default {
	name: 'DatePollVoteHeader',

	props: {
		option: {
			type: Object,
			default: undefined
		},
		pollType: {
			type: String,
			default: undefined
		},
		mode: {
			type: String,
			default: 'vote'
		}
	},

	data() {
		return {
			openedMenu: false,
			hostName: this.$route.query.page
		}

	},

	computed: {
		localFullDate() {
			return moment(this.option.timestamp * 1000).format('llll')
		},
		day() {
			return moment(this.option.timestamp * 1000).format('Do')
		},
		dow() {
			return moment(this.option.timestamp * 1000).format('ddd')
		},
		month() {
			return moment(this.option.timestamp * 1000).format('MMM')
		},
		year() {
			return moment(this.option.timestamp * 1000).format('YYYY')
		},
		time() {
			return moment(this.option.timestamp * 1000).format('LT')
		},

		localFullDateT() {
			return moment(this.option.text).format('llll')
		},
		dayT() {
			return moment(this.option.text).format('Do')
		},
		dowT() {
			return moment(this.option.text).format('ddd')
		},
		monthT() {
			return moment(this.option.text).format('MMM')
		},
		yearT() {
			return moment(this.option.text).format('YYYY')
		},
		timeT() {
			return moment(this.option.text).format('LT')
		}
	}
}
</script>

<style lang="scss">
.date-box {
	display: flex;
	flex-direction: column;
	flex: 0 0;
	padding: 0 2px;
	align-items: center;
	.month, .dow {
		font-size: 1.2em;
		color: var(--color-text-lighter);
	}
	.day {
		font-size: 1.8em;
		margin: 5px 0 5px 0;
	}
}
</style>

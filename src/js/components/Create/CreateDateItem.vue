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
	<li>
		<div>{{ option.timestamp | localFullDate }}</div>
		<div>
			<a class="icon-delete" @click="$emit('remove')" />
		</div>
	</li>
</template>

<script>

export default {
	name: 'DatePollItem',

	filters: {
		localFullDate(timestamp) {
			if (!timestamp) return ''
			if (timestamp < 999999999999) timestamp = timestamp * 1000
			if (!moment(timestamp).isValid()) return 'Invalid Date'
			return moment(timestamp).format('llll')
		}
	},

	props: {
		option: {
			type: Object,
			default: undefined
		}
	}

}
</script>

<style lang="scss" scoped>
	li > div {
		display: flex;
		flex-grow: 1;
		font-size: 1.2em;
		opacity: 0.7;
		white-space: normal;
		padding-right: 4px;
	}

	li > div:nth-last-child(1) {
		justify-content: center;
		flex-grow: 0;
		flex-shrink: 0;
	}
</style>

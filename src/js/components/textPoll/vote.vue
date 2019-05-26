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
	<div v-if="header">
		{{ option.text }}
	</div>
</template>

<script>

export default {
	name: 'VoteTextPoll',

	props: {
		header: {
			type: Boolean,
			default: false
		},
		option: {
			type: Object,
			default: undefined
		},
		pollType: {
			type: String,
			default: undefined
		}
	},

	data() {
		return {
			openedMenu: false,
			hostName: this.$route.query.page
		}

	}
}
</script>

<style lang="scss">


</style>

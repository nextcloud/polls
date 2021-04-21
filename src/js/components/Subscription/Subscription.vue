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

<template lang="html">
	<div class="subscription">
		<CheckBoxDiv v-model="subscribe" :label="label" />
	</div>
</template>

<script>
import { mapState } from 'vuex'
import CheckBoxDiv from '../Base/CheckBoxDiv'
export default {
	name: 'Subscription',

	components: {
		CheckBoxDiv,
	},

	computed: {
		...mapState({
			subscribed: (state) => state.subscription.subscribed,
			emailAddress: (state) => state.share.emailAddress,
		}),

		label() {
			if (this.emailAddress) {
				return t('polls', 'Receive notification email on activity to {emailAddress}', { emailAddress: this.emailAddress })
			}
			return t('polls', 'Receive notification email on activity')

		},

		subscribe: {
			get() {
				return this.subscribed
			},
			set(value) {
				this.$store.dispatch('subscription/update', value)
			},
		},
	},
}
</script>

<style lang="css" scoped>
	.subscription {
		padding: 8px;
	}
</style>

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
	<div class="action change-view">
		<ButtonDiv v-if="buttonMode"
			:title="caption"
			simple
			:icon="icon"
			@click="clickAction()" />
		<Actions v-else>
			<ActionButton :icon="icon" @click="clickAction()">
				{{ caption }}
			</ActionButton>
		</Actions>
	</div>
</template>

<script>
import { Actions, ActionButton } from '@nextcloud/vue'
import { mapState } from 'vuex'
import ButtonDiv from '../Base/ButtonDiv'

export default {
	name: 'ActionChangeView',

	components: {
		Actions,
		ActionButton,
		ButtonDiv,
	},

	props: {
		buttonMode: {
			type: Boolean,
			default: false,
		},
	},

	computed: {
		...mapState({
			subscribed: state => state.subscription.subscribed,
		}),

		caption() {
			if (this.subscribed) {
				return t('polls', 'Unsubscribe')
			}
			return t('polls', 'Subscribe')

		},

		icon() {
			if (this.subscribed) {
				return 'icon-polls-confirmed'
			}
			return 'icon-polls-unconfirmed'

		},
	},

	methods: {
		async clickAction() {
			await this.$store.dispatch('subscription/update', !this.subscribed)
		},
	},
}
</script>

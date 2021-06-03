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
	<div class="action copy-mail-adresses">
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
import ButtonDiv from '../Base/ButtonDiv'
import { mapGetters } from 'vuex'
import { showSuccess, showError } from '@nextcloud/dialogs'

export default {
	name: 'ActionCopyMailAdresses',

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

	data() {
		return {
			icon: 'icon-clippy',
		}
	},

	computed: {
		...mapGetters({
			countParticipantsVoted: 'poll/countParticipantsVoted',
		}),

		caption() {
			return n('polls', '%n Participant', '%n Participants', this.countParticipantsVoted)
		},
	},
	methods: {

		async clickAction() {
			try {
				const response = await this.$store.dispatch('poll/getParticipantsEmailAddresses')
				await this.$copyText(response.data)
				showSuccess(t('polls', 'Link copied to clipboard'))
			} catch {
				showError(t('polls', 'Error while copying link to clipboard'))
			}
		},
	},
}
</script>

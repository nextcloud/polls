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
	<CardDiv :type="cardType">
		{{ confirmationSendMessage }}
		<template #button>
			<ActionSendConfirmed @error="confirmationSendError()"
				@success="confirmationSendSuccess()" />
		</template>
	</CardDiv>
</template>

<script>
import { CardDiv } from '../../Base/index.js'
import ActionSendConfirmed from '../../Actions/modules/ActionSendConfirmed.vue'

export default {
	name: 'CardSendConfirmations',
	components: {
		CardDiv,
		ActionSendConfirmed,
	},

	data() {
		return {
			cardType: 'info',
			confirmationSendMessage: t('polls', 'You have confirmed options. Inform your participants about the result via email.'),
		}
	},

	methods: {
		confirmationSendError() {
			this.cardType = 'error'
			this.confirmationSendMessage = t('polls', 'Some confirmation messages could not been sent.')
			this.$emit('send-confirmation-success')
		},

		confirmationSendSuccess() {
			this.cardType = 'success'
			this.confirmationSendMessage = t('polls', 'Messages sent.')
			this.$emit('send-confirmation-error')
		},

	},
}
</script>

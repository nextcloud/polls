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
	<div class="action send-confirmations">
		<div class="confirmation-button">
			<h2>{{ headerCaption }}</h2>
			<NcButton v-tooltip="caption" @click="clickAction()">
				<template #icon>
					<EmailCheckIcon />
				</template>
				<template #default>
					{{ t('polls', 'Send confirmation emails') }}
				</template>
			</NcButton>
		</div>
		<div v-if="confirmations" class="confirmation-info">
			<div v-if="confirmations.sent" class="sent-confirmations">
				<h2>{{ t('polls', 'Sent emails to:') }}</h2>
				<ul>
					<li v-for="(item) in confirmations.sent" :key="item">
						{{ item }}
					</li>
				</ul>
			</div>
			<div v-if="confirmations.error" class="error-confirmations">
				<h2>{{ t('polls', 'Emails could not be sent:') }}</h2>
				<ul>
					<li v-for="(item) in confirmations.error" :key="item">
						{{ item }}
					</li>
				</ul>
			</div>
		</div>
	</div>
</template>

<script>
import { NcButton } from '@nextcloud/vue'
import EmailCheckIcon from 'vue-material-design-icons/EmailCheck.vue' // view-comfy-outline
import { showError, showSuccess } from '@nextcloud/dialogs'
import { PollsAPI } from '../../Api/polls.js'

export default {
	name: 'ActionSendConfirmedOptions',

	components: {
		EmailCheckIcon,
		NcButton,
	},

	data() {
		return {
			caption: t('polls', 'Send information about confirmed options by email'),
			confirmations: null,
			headerCaption: t('polls', 'Inform your participants about the confirmed options'),
		}
	},

	methods: {
		async clickAction() {
			try {
				this.confirmations = await PollsAPI.sendConfirmation(this.$route.params.id)
			} catch (e) {
				if (e?.code === 'ERR_CANCELED') return
			}

			this.headerCaption = t('polls', 'Confirmations processed')
			this.confirmations.sent.forEach((confirmation) => {
				showSuccess(t('polls', `Confirmation sent to ${confirmation}`))
			})
			this.confirmations.error.forEach((confirmation) => {
				showError(t('polls', `Confirmation could not be sent to ${confirmation}`))
			})
		},
	},
}
</script>

<style lang="scss">
.action.send-confirmations {
	display: flex;
	flex-wrap: wrap;
	gap: 44px;
}

.confirmation-result {
	display: flex;
}

.confirmation-info {
	display: flex;
	flex-wrap: wrap;
	flex: auto;
	gap: 12px;
}

.sent-confirmations {
	flex: auto;
}

.error-confirmations {
	flex: auto;
}
</style>

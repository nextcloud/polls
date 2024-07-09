<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<NcActionInput v-if="$route.name === 'publicVote'"
		v-bind="inputProps"
		v-model="shareStore.emailAddress"
		@update:value="validate"
		@submit="submit">
		<template #icon>
			<EditEmailIcon />
		</template>
		{{ inputProps.label }}
	</NcActionInput>
</template>

<script>
import { debounce } from 'lodash'
import { showSuccess, showError } from '@nextcloud/dialogs'
import { NcActionInput } from '@nextcloud/vue'
import { mapStores } from 'pinia'
import EditEmailIcon from 'vue-material-design-icons/EmailEditOutline.vue'
import { ValidatorAPI } from '../../Api/index.js'
import { t } from '@nextcloud/l10n'
import { useSessionStore } from '../../stores/session.ts'
import { useShareStore } from '../../stores/share.ts'

const setError = (inputProps) => {
	inputProps.success = false
	inputProps.error = true
	inputProps.showTrailingButton = false
}

const setSuccess = (inputProps) => {
	inputProps.success = true
	inputProps.error = false
	inputProps.showTrailingButton = true
}
const setUnchanged = (inputProps) => {
	inputProps.success = false
	inputProps.error = false
	inputProps.showTrailingButton = false
}

export default {
	name: 'ActionInputEmailAddress',

	components: {
		NcActionInput,
		EditEmailIcon,
	},

	data() {
		return {
			inputProps: {
				success: false,
				error: false,
				showTrailingButton: true,
				labelOutside: false,
				label: t('polls', 'Edit Email Address'),
			},
		}
	},

	computed: {
		...mapStores(useSessionStore, useShareStore),
	},

	methods: {
		validate: debounce(async function() {
			const inputProps = this.inputProps

			if (this.shareStore.emailAddress === this.sessionStore.currentUser.emailAddress) {
				setUnchanged(inputProps)
				return
			}

			try {
				await ValidatorAPI.validateEmailAddress(this.shareStore.emailAddress)
				setSuccess(inputProps)
			} catch {
				setError(inputProps)
			}
		}, 500),

		async submit() {
			try {
				await this.shareStore.updateEmailAddress({ emailAddress: this.shareStore.emailAddress })
				showSuccess(t('polls', 'Email address {emailAddress} saved.', { emailAddress: this.shareStore.emailAddress }))
				setUnchanged(this.inputProps)
			} catch {
				showError(t('polls', 'Error saving email address {emailAddress}', { emailAddress: this.shareStore.emailAddress }))
				setError(this.inputProps)
			}
		},
	},
}
</script>

<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import { debounce } from 'lodash'
import { showSuccess, showError } from '@nextcloud/dialogs'
import { NcActionInput } from '@nextcloud/vue'
import EditEmailIcon from 'vue-material-design-icons/EmailEditOutline.vue'
import { ValidatorAPI } from '../../Api/index.js'
import { t } from '@nextcloud/l10n'
import { useSessionStore } from '../../stores/session.ts'
import { useShareStore } from '../../stores/share.ts'
import { ref } from 'vue'

const sessionStore = useSessionStore()
const shareStore = useShareStore()

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

const inputProps = ref({
	success: false,
	error: false,
	showTrailingButton: true,
	labelOutside: false,
	label: t('polls', 'Edit Email Address'),
})

function validate() {
	debounce(async function () {
		if (shareStore.emailAddress === sessionStore.currentUser.emailAddress) {
			setUnchanged(inputProps.value)
			return
		}

		try {
			await ValidatorAPI.validateEmailAddress(shareStore.emailAddress)
			setSuccess(inputProps.value)
		} catch {
			setError(inputProps.value)
		}
	}, 500)()
}

async function submit() {
	try {
		await shareStore.updateEmailAddress({ emailAddress: shareStore.emailAddress })
		showSuccess(t('polls', 'Email address {emailAddress} saved.', { emailAddress: shareStore.emailAddress }))
		setUnchanged(inputProps.value)
	} catch {
		showError(t('polls', 'Error saving email address {emailAddress}', { emailAddress: shareStore.emailAddress }))
		setError(inputProps.value)
	}
}
</script>

<template>
	<NcActionInput v-if="$route.name === 'publicVote'"
		v-bind="inputProps"
		v-model="shareStore.emailAddress"
		@update:model-value="validate"
		@submit="submit">
		<template #icon>
			<EditEmailIcon />
		</template>
		{{ inputProps.label }}
	</NcActionInput>
</template>

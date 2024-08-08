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
	import { StatusResults } from '../../Types/index.ts'

	type InputProps = {
		success: boolean
		error: boolean
		showTrailingButton: boolean
		labelOutside: boolean
		label: string
	}

	const sessionStore = useSessionStore()
	const shareStore = useShareStore()

	const inputProps = ref<InputProps>({
		success: false,
		error: false,
		showTrailingButton: true,
		labelOutside: false,
		label: t('polls', 'Edit Email Address'),
	})

	function setStatus(status: StatusResults) {
		inputProps.value.success = status === StatusResults.Success
		inputProps.value.error = status === StatusResults.Error
		inputProps.value.showTrailingButton = status === StatusResults.Success
	}


	const validate = debounce(async function () {
		if (shareStore.emailAddress === sessionStore.currentUser.emailAddress) {
			setStatus(StatusResults.Unchanged)
			return
		}

		try {
			await ValidatorAPI.validateEmailAddress(shareStore.emailAddress)
			setStatus(StatusResults.Success)
		} catch {
			setStatus(StatusResults.Error)
		}
	}, 500)

	async function submit() {
		try {
			await shareStore.updateEmailAddress({ emailAddress: shareStore.emailAddress })
			showSuccess(t('polls', 'Email address {emailAddress} saved.', { emailAddress: shareStore.emailAddress }))
			setStatus(StatusResults.Unchanged)
		} catch {
			showError(t('polls', 'Error saving email address {emailAddress}', { emailAddress: shareStore.emailAddress }))
			setStatus(StatusResults.Error)
		}
	}
</script>

<template>
	<NcActionInput v-if="sessionStore.route.name === 'publicVote'"
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

<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import { ref } from 'vue'
import { debounce } from 'lodash'
import { showSuccess, showError } from '@nextcloud/dialogs'
import { t } from '@nextcloud/l10n'

import NcActionInput from '@nextcloud/vue/components/NcActionInput'
import EditEmailIcon from 'vue-material-design-icons/EmailEditOutline.vue'

import { ValidatorAPI } from '../../Api/index.ts'
import { StatusResults } from '../../Types/index.ts'
import { useSessionStore } from '../../stores/session.ts'

type InputProps = {
	success: boolean
	error: boolean
	showTrailingButton: boolean
	labelOutside: boolean
	label: string
}

const sessionStore = useSessionStore()

const inputProps = ref<InputProps>({
	success: false,
	error: false,
	showTrailingButton: true,
	labelOutside: false,
	label: t('polls', 'Edit Email Address'),
})

/**
 *
 * @param status
 */
function setStatus(status: StatusResults) {
	inputProps.value.success = status === StatusResults.Success
	inputProps.value.error = status === StatusResults.Error
	inputProps.value.showTrailingButton = status === StatusResults.Success
}

const validate = debounce(async function () {
	if (
		sessionStore.share.user.emailAddress ===
		sessionStore.currentUser.emailAddress
	) {
		setStatus(StatusResults.Unchanged)
		return
	}

	try {
		await ValidatorAPI.validateEmailAddress(sessionStore.share.user.emailAddress)
		setStatus(StatusResults.Success)
	} catch {
		setStatus(StatusResults.Error)
	}
}, 500)

/**
 *
 */
async function submit() {
	try {
		await sessionStore.updateEmailAddress({
			emailAddress: sessionStore.share.user.emailAddress,
		})
		showSuccess(
			t('polls', 'Email address {emailAddress} saved.', {
				emailAddress: sessionStore.share.user.emailAddress,
			}),
		)
		setStatus(StatusResults.Unchanged)
	} catch {
		showError(
			t('polls', 'Error saving email address {emailAddress}', {
				emailAddress: sessionStore.share.user.emailAddress,
			}),
		)
		setStatus(StatusResults.Error)
	}
}
</script>

<template>
	<NcActionInput
		v-if="sessionStore.route.name === 'publicVote'"
		v-bind="inputProps"
		v-model="sessionStore.share.user.emailAddress"
		@update:model-value="validate"
		@submit="submit">
		<template #icon>
			<EditEmailIcon />
		</template>
		{{ inputProps.label }}
	</NcActionInput>
</template>

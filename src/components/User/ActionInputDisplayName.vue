<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import { ref } from 'vue'
import debounce from 'lodash/debounce'
import { showSuccess, showError } from '@nextcloud/dialogs'
import { t } from '@nextcloud/l10n'

import NcActionInput from '@nextcloud/vue/components/NcActionInput'
import EditAccountIcon from 'vue-material-design-icons/AccountEditOutline.vue'

import { ValidatorAPI } from '../../Api'
import { StatusResults } from '../../Types'
import { useSessionStore } from '../../stores/session'

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
	label: t('polls', 'Change name'),
})

/**
 *
 * @param status
 */
function setStatus(status: StatusResults) {
	inputProps.value.success = status === 'success'
	inputProps.value.error = status === 'error'
	inputProps.value.showTrailingButton = status === 'success'
}

const validatePublicUsername = debounce(async function () {
	if (sessionStore.share.user.displayName.length < 1) {
		setStatus('unchanged')
		return
	}

	if (
		sessionStore.share.user.displayName === sessionStore.currentUser.displayName
	) {
		setStatus('error')
		return
	}

	try {
		await ValidatorAPI.validateName(
			sessionStore.route.params.token,
			sessionStore.share.user.displayName,
		)
		setStatus('success')
	} catch {
		setStatus('error')
	}
}, 500)

/**
 *
 */
async function submit() {
	try {
		await sessionStore.updateDisplayName({
			displayName: sessionStore.share.user.displayName,
		})
		showSuccess(t('polls', 'Name changed.'))
		setStatus('unchanged')
	} catch {
		showError(t('polls', 'Error changing name.'))
		setStatus('error')
	}
}
</script>

<template>
	<NcActionInput
		v-if="sessionStore.route.name === 'publicVote'"
		v-bind="inputProps"
		v-model="sessionStore.share.user.displayName"
		@update:value="validatePublicUsername"
		@submit="submit">
		<template #icon>
			<EditAccountIcon />
		</template>
		{{ inputProps.label }}
	</NcActionInput>
</template>

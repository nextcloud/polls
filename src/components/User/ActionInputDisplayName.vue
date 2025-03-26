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
import EditAccountIcon from 'vue-material-design-icons/AccountEdit.vue'

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
	label: t('polls', 'Change name'),
})

function setStatus(status: StatusResults) {
	inputProps.value.success = status === StatusResults.Success
	inputProps.value.error = status === StatusResults.Error
	inputProps.value.showTrailingButton = status === StatusResults.Success
}

const validate = debounce(async function () {
	if (sessionStore.share.user.displayName.length < 1) {
		setStatus(StatusResults.Unchanged)
		return
	}

	if (
		sessionStore.share.user.displayName === sessionStore.currentUser.displayName
	) {
		setStatus(StatusResults.Error)
		return
	}

	try {
		await ValidatorAPI.validateName(
			sessionStore.route.params.token,
			sessionStore.share.user.displayName,
		)
		setStatus(StatusResults.Success)
	} catch {
		setStatus(StatusResults.Error)
	}
}, 500)

async function submit() {
	try {
		await sessionStore.updateDisplayName({
			displayName: sessionStore.share.user.displayName,
		})
		showSuccess(t('polls', 'Name changed.'))
		setStatus(StatusResults.Unchanged)
	} catch {
		showError(t('polls', 'Error changing name.'))
		setStatus(StatusResults.Error)
	}
}
</script>

<template>
	<NcActionInput
		v-if="sessionStore.route.name === 'publicVote'"
		v-bind="inputProps"
		v-model="sessionStore.share.user.displayName"
		@update:value-value="validate"
		@submit="submit">
		<template #icon>
			<EditAccountIcon />
		</template>
		{{ inputProps.label }}
	</NcActionInput>
</template>

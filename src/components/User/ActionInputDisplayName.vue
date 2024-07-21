<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
	import { debounce } from 'lodash'
	import { showSuccess, showError } from '@nextcloud/dialogs'
	import { NcActionInput } from '@nextcloud/vue'
	import EditAccountIcon from 'vue-material-design-icons/AccountEdit.vue'
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
		label: t('polls', 'Change name'),
	})


	function validate() {
		debounce(async function () {
			if (shareStore.displayName.length < 1) {
				setError(inputProps.value)
				return
			}

			if (shareStore.displayName === sessionStore.currentUser.displayName) {
				setUnchanged(inputProps.value)
				return
			}

			try {
				await ValidatorAPI.validateName(sessionStore.route.params.token, shareStore.displayName)
				setSuccess(inputProps.value)
			} catch {
				setError(inputProps.value)
			}
		})
	}

	async function submit() {
		try {
			await this.shareStore.updateDisplayName({ displayName: this.shareStore.displayName })
			showSuccess(t('polls', 'Name changed.'))
			setUnchanged(this.inputProps)
		} catch {
			showError(t('polls', 'Error changing name.'))
			setError(this.inputProps)
		}
	}

</script>

<template>
	<NcActionInput v-if="$route.name === 'publicVote'"
		v-bind="inputProps"
		v-model="shareStore.displayName"
		@update:value-value="validate"
		@submit="submit">
		<template #icon>
			<EditAccountIcon />
		</template>
		{{ inputProps.label }}
	</NcActionInput>
</template>

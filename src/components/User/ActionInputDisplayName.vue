<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<NcActionInput v-if="$route.name === 'publicVote'"
		v-bind="inputProps"
		:value.sync="shareStore.displayName"
		@update:value="validate"
		@submit="submit">
		<template #icon>
			<EditAccountIcon />
		</template>
		{{ inputProps.label }}
	</NcActionInput>
</template>

<script>
import { debounce } from 'lodash'
import { showSuccess, showError } from '@nextcloud/dialogs'
import { NcActionInput } from '@nextcloud/vue'
import { mapStores } from 'pinia'
import EditAccountIcon from 'vue-material-design-icons/AccountEdit.vue'
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
	name: 'ActionInputDisplayName',

	components: {
		NcActionInput,
		EditAccountIcon,
	},

	data() {
		return {
			inputProps: {
				success: false,
				error: false,
				showTrailingButton: true,
				labelOutside: false,
				label: t('polls', 'Change name'),
			},
		}
	},

	computed: {
		...mapStores(useSessionStore, useShareStore),
	
	},

	methods: {
		validate: debounce(async function() {
			const inputProps = this.userName.inputProps
			if (this.shareStore.displayName.length < 1) {
				setError(inputProps)
				return
			}

			if (this.shareStore.displayName === this.sessionStore.currentUser.displayName) {
				setUnchanged(inputProps)
				return
			}

			try {
				await ValidatorAPI.validateName(this.$route.params.token, this.shareStore.displayName)
				setSuccess(inputProps)
			} catch {
				setError(inputProps)
			}
		}, 500),

		async submit() {
			try {
				await this.shareStore.updateDisplayName({ displayName: this.shareStore.displayName })
				showSuccess(t('polls', 'Name changed.'))
				setUnchanged(this.inputProps)
			} catch {
				showError(t('polls', 'Error changing name.'))
				setError(this.inputProps)
			}
		},
	},
}
</script>

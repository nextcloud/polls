<!--
  - SPDX-FileCopyrightText: 2021 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
	import { computed, ref } from 'vue'
	import { t } from '@nextcloud/l10n'
	import { usePollStore } from '../../stores/poll.ts'

	import NcCheckboxRadioSwitch from '@nextcloud/vue/components/NcCheckboxRadioSwitch'
	import { NcDialog } from '@nextcloud/vue'
	import NcButton, { ButtonType } from '@nextcloud/vue/components/NcButton'

	const dialog = {
		message: t('polls', 'Once enabled the anonymous setting can not be reverted anymore.'),
		name: t('polls', 'Anonymize poll irrevocably'),

		buttons: [
			{
				label: 'Cancel',
				// icon: IconCancel,
				callback: () => { cancelAnonymous() },
			},
			{
				label: 'Ok',
				type: ButtonType.Primary,
				// icon: IconCheck,
				callback: () => { confirmAnonymous() },
			}
		]
	}
	const showDialog = ref(false)

	const showLockAnonymous = computed(() => pollStore.permissions.deanonymize
		&& pollStore.status.isAnonymous
		&& !pollStore.status.isRealAnonymous)

	function spawnConfirmationDialog(lockAnonymous = false) {
		if ((!pollStore.permissions.deanonymize && pollStore.configuration.anonymous) || lockAnonymous) {
			showDialog.value = true
			return
		}
		pollStore.write()
	}

	function confirmAnonymous() {
		if (pollStore.permissions.deanonymize) {
			pollStore.LockAnonymous()
		}

		pollStore.write()
		return true
	}

	function cancelAnonymous() {
		if (!pollStore.permissions.deanonymize) {
			pollStore.configuration.anonymous = false
		}
		return true
	}

	const pollStore = usePollStore()
	const disabledState = computed(() => (pollStore.status.isAnonymous && !pollStore.permissions.deanonymize) || pollStore.status.isRealAnonymous)
</script>

<template>
	<NcCheckboxRadioSwitch v-model="pollStore.configuration.anonymous"
		type="switch"
		:disabled="disabledState"
		@update:model-value="spawnConfirmationDialog()">
		{{ t('polls', 'Anonymous poll') }}
	</NcCheckboxRadioSwitch>

	<NcButton v-if="showLockAnonymous" class="indented" @click="spawnConfirmationDialog(true)">
		{{ t('polls', 'Anonymize poll irrevocably') }}
	</NcButton>

	<NcDialog v-model:open="showDialog" v-bind="dialog" />

</template>

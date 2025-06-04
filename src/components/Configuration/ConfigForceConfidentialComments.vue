<!--
  - SPDX-FileCopyrightText: 2021 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import { t } from '@nextcloud/l10n'
import { usePollStore } from '../../stores/poll.ts'

import NcCheckboxRadioSwitch from '@nextcloud/vue/components/NcCheckboxRadioSwitch'
import { useSessionStore } from '../../stores/session.ts'

const emit = defineEmits(['change'])

const pollStore = usePollStore()
const sessionStore = useSessionStore()
const description =
	pollStore.owner.id === sessionStore.currentUser.id
		? t(
				'polls',
				'Force confidential comments (only visible to you and the author)',
			)
		: t(
				'polls',
				'Force confidential comments (only visible to {displayName} and the author)',
				{
					displayName: pollStore.owner.displayName,
				},
			)
</script>

<template>
	<NcCheckboxRadioSwitch
		v-model="pollStore.configuration.forceConfidentialComments"
		type="switch"
		@update:model-value="emit('change')">
		{{ description }}
	</NcCheckboxRadioSwitch>
</template>

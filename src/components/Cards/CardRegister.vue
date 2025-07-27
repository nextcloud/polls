<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import { computed } from 'vue'
import CardDiv from '../Base/modules/CardDiv.vue'
import ActionRegister from '../Actions/modules/ActionRegister.vue'
import { t } from '@nextcloud/l10n'
import { useSessionStore } from '../../stores/session'

const sessionStore = useSessionStore()
const cardType = 'info'

const registrationInvitationText = computed(() => {
	if (sessionStore.share?.publicPollEmail === 'mandatory') {
		return t(
			'polls',
			'To participate, register with your email address and a name.',
		)
	}
	if (sessionStore.share?.publicPollEmail === 'optional') {
		return t(
			'polls',
			'To participate, register a name and optionally with your email address.',
		)
	}
	return t('polls', 'To participate, register with a name.')
})
</script>

<template>
	<CardDiv :type="cardType">
		{{ registrationInvitationText }}
		<template #button>
			<ActionRegister />
		</template>
	</CardDiv>
</template>

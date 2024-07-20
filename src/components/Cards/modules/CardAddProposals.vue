<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import { CardDiv } from '../../Base/index.js'
import OptionProposals from '../../Options/OptionProposals.vue'
import { t } from '@nextcloud/l10n'
import { usePollStore } from '../../../stores/poll.ts'

const pollStore = usePollStore()
const cardType = 'info'

</script>

<template>
	<CardDiv :type="cardType">
		{{ t('polls', 'You are asked to propose more options. ') }}
		<p v-if="pollStore.isProposalExpirySet && !pollStore.isProposalExpired">
			{{ t('polls', 'The proposal period ends {timeRelative}.',
				{ timeRelative: pollStore.proposalsExpireRelative }) }}
		</p>
		<OptionProposals v-if="pollStore.type === 'textPoll'" />
		<template #button>
			<OptionProposals v-if="pollStore.type === 'datePoll'" />
		</template>
	</CardDiv>
</template>

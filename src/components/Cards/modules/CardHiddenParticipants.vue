<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import { CardDiv } from '../../Base/index.ts'
import ActionSwitchSafeTable from '../../Actions/modules/ActionSwitchSafeTable.vue'
import { t } from '@nextcloud/l10n'
import { usePollStore } from '../../../stores/poll.ts'

const pollStore = usePollStore()
const cardType = 'warning'
</script>

<template>
	<CardDiv :type="cardType">
		{{
			t(
				'polls',
				'Due to possible performance issues {countHiddenParticipants} voters are hidden.',
				{ countHiddenParticipants: pollStore.countHiddenParticipants },
			)
		}}
		{{
			t(
				'polls',
				'You can reveal them, but you may expect an unwanted long loading time.',
			)
		}}
		<template #button>
			<ActionSwitchSafeTable />
		</template>
	</CardDiv>
</template>

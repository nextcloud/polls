<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<CardDiv :type="cardType">
		{{ t('polls', 'Due to possible performance issues {countHiddenParticipants} voters are hidden.', { countHiddenParticipants: pollStore.countHiddenParticipants }) }}
		{{ t('polls', 'You can reveal them, but you may expect an unwanted long loading time.') }}
		<template #button>
			<ActionSwitchSafeTable />
		</template>
	</CardDiv>
</template>

<script>
import { mapStores } from 'pinia'
import { CardDiv } from '../../Base/index.js'
import ActionSwitchSafeTable from '../../Actions/modules/ActionSwitchSafeTable.vue'
import { t } from '@nextcloud/l10n'
import { usePollStore } from '../../../stores/poll.ts'

export default {
	name: 'CardHiddenParticipants',
	components: {
		CardDiv,
		ActionSwitchSafeTable,
	},

	data() {
		return {
			cardType: 'warning',
		}
	},

	computed: {
		...mapStores(usePollStore),
	},

	methods: {
		t,
	},
}
</script>

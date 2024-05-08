<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<CardDiv :type="cardType">
		{{ t('polls', 'Due to possible performance issues {countHiddenParticipants} voters are hidden.', { countHiddenParticipants }) }}
		{{ t('polls', 'You can reveal them, but you may expect an unwanted long loading time.') }}
		<template #button>
			<ActionSwitchSafeTable />
		</template>
	</CardDiv>
</template>

<script>
import { mapGetters } from 'vuex'
import { CardDiv } from '../../Base/index.js'
import ActionSwitchSafeTable from '../../Actions/modules/ActionSwitchSafeTable.vue'

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
		...mapGetters({
			countHiddenParticipants: 'poll/countHiddenParticipants',
		}),
	},
}
</script>

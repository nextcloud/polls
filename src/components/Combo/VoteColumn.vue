<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<div class="vote-column">
		<OptionItem :option="props.option" />
		<div
			v-for="poll in comboStore.polls"
			:key="poll.id"
			:title="poll.configuration.title"
			class="poll-group">
			<VoteItem
				v-for="participant in comboStore.participantsInPoll(poll.id)"
				:key="`${participant.user.id}_${participant.pollId}`"
				:poll="poll"
				:user="participant.user"
				:option="option" />
		</div>
	</div>
</template>

<script setup lang="ts">
import { PropType } from 'vue'
import VoteItem from './VoteItem.vue'
import OptionItem from '../Options/OptionItem.vue'
import { useComboStore } from '../../stores/combo.ts'
import { Option } from '../../Types/index.ts'

const comboStore = useComboStore()

const props = defineProps({
	option: {
		type: Object as PropType<Option>,
		required: true,
	},
})
</script>

<style lang="scss">
.vote-column {
	display: flex;
	flex: 1 0 11rem;
	flex-direction: column;
	align-items: stretch;
	max-width: 280px;
	border-left: 1px solid var(--color-border-dark);
	margin-bottom: 4px;
}
</style>

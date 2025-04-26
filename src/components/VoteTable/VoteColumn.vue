<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import { computed, PropType } from 'vue'
import { getCurrentUser } from '@nextcloud/auth'

import Counter from '../Options/Counter.vue'
import OptionItem from '../Options/OptionItem.vue'
import VoteItem from './VoteItem.vue'
import CalendarPeek from '../Calendar/CalendarPeek.vue'
import { usePollStore, PollType } from '../../stores/poll.ts'
import { usePreferencesStore } from '../../stores/preferences.ts'
import { Option } from '../../stores/options.ts'

import OptionMenu from '../Options/OptionMenu.vue'

const pollStore = usePollStore()
const preferencesStore = usePreferencesStore()

const props = defineProps({
	option: {
		type: Object as PropType<Option>,
		required: true,
	},
})

const componentClass = computed(() => {
	const classList = ['vote-column']
	if (props.option.locked) {
		classList.push('locked')
	}

	if (props.option.confirmed && pollStore.isClosed) {
		classList.push('confirmed')
	}
	if (props.option.votes.currentUser) {
		classList.push(props.option.votes.currentUser)
	}

	return classList
})

const showCalendarPeek = computed(
	() =>
		pollStore.type === PollType.Date
		&& getCurrentUser()
		&& preferencesStore.user.calendarPeek,
)
</script>

<template>
	<div :class="componentClass">
		<div class="option-menu">
			<OptionMenu :option="option" use-sort />
		</div>
		<div class="column-header">
			<OptionItem :option="option" />

			<Counter
				v-if="pollStore.permissions.seeResults"
				:show-maybe="pollStore.configuration.allowMaybe"
				:option="option" />

			<CalendarPeek
				v-if="showCalendarPeek"
				:focus-trap="false"
				:option="option" />
		</div>

		<VoteItem
			v-for="participant in pollStore.safeParticipants"
			:key="participant.id"
			:user="participant"
			:option="option" />
	</div>
</template>

<style lang="scss">
.option-menu {
	// display: grid;
	// grid-template-columns: 1fr auto;
	// grid-template-areas: 'menu sort';
	// align-content: center;
	align-self: center;
	flex: 0 0 34px;
}

.vote-style-beta-510 .vote-column {
	border-radius: var(--border-radius-large);

	&:hover {
		background-color: var(--color-background-dark);
	}

	&.locked:hover {
		background-color: var(--color-polls-background-no);
	}
}
</style>

<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import { computed, ref } from 'vue'
import { NcButton } from '@nextcloud/vue'
import { t } from '@nextcloud/l10n'
import { getCurrentUser } from '@nextcloud/auth'

import { usePollStore } from '../../stores/poll.ts'
import { Option, useOptionsStore } from '../../stores/options.ts'
import { useVotesStore } from '../../stores/votes.ts'

import StickyDiv from '../Base/modules/StickyDiv.vue'
import CalendarPeek from '../Calendar/CalendarPeek.vue'
import Counter from '../Options/Counter.vue'
import OptionItem from '../Options/OptionItem.vue'
import OptionMenu from '../Options/OptionMenu.vue'
import VoteButton from './VoteButton.vue'
import VoteItem from './VoteItem.vue'
import VoteParticipant from './VoteParticipant.vue'

import { usePreferencesStore } from '../../stores/preferences.ts'
import { useSessionStore } from '../../stores/session.ts'

import { User } from '../../Types/index.ts'

import SortOptionIcon from 'vue-material-design-icons/SortBoolAscendingVariant.vue'
import SortNameIcon from 'vue-material-design-icons/SortAlphabeticalDescending.vue'

const pollStore = usePollStore()
const optionsStore = useOptionsStore()
const votesStore = useVotesStore()
const preferencesStore = usePreferencesStore()
const sessionStore = useSessionStore()

const { downPage = false } = defineProps<{ downPage: boolean }>()

const chunksLoading = ref(false)

const tableStyle = computed(() => ({
	'--participants-count': `${votesStore.getChunkedParticipants.length}`,
	'--options-count': `${optionsStore.options.length}`,
}))

const showCalendarPeek = computed(
	() =>
		pollStore.type === 'datePoll'
		&& getCurrentUser()
		&& preferencesStore.user.calendarPeek,
)

function isCurrentUser(participant: User) {
	return participant.id === sessionStore.currentUser.id
}

function isVotable(participant: User, option: Option) {
	return (
		participant.id === sessionStore.currentUser.id
		&& pollStore.permissions.vote
		&& !pollStore.status.isExpired
		&& !option.locked
	)
}
</script>

<template>
	<TransitionGroup
		id="vote-table"
		tag="div"
		name="list"
		:class="pollStore.viewMode"
		class="vote-table"
		:style="tableStyle">
		<StickyDiv
			v-show="pollStore.viewMode === 'table-view'"
			key="grid-info"
			sticky-left
			class="grid-info">
			<NcButton
				v-show="votesStore.sortByOption > 0"
				class="sort-indicator"
				:title="t('polls', 'Click to sort by name')"
				:button-variant="'tertiary-no-background'"
				@click="() => (votesStore.sortByOption = 0)">
				<template #icon>
					<SortNameIcon />
				</template>
			</NcButton>
		</StickyDiv>

		<StickyDiv
			v-show="pollStore.viewMode === 'table-view'"
			:id="`option-item-spacer`"
			class="option-item-spacer"
			sticky-top
			sticky-left
			:activate-bottom-shadow="!downPage">
		</StickyDiv>

		<StickyDiv
			v-if="pollStore.permissions.seeResults"
			v-show="pollStore.viewMode === 'table-view'"
			sticky-left
			class="counter-spacer" />

		<StickyDiv
			v-for="participant in votesStore.getChunkedParticipants"
			:key="participant.id"
			class="participant"
			sticky-left>
			<VoteParticipant :user="participant" />
		</StickyDiv>

		<template v-for="option in optionsStore.orderedOptions" :key="option.id">
			<div
				v-show="pollStore.viewMode === 'table-view'"
				class="option-menu-grid">
				<CalendarPeek
					v-if="showCalendarPeek"
					:id="`peek-${option.id}`"
					:focus-trap="false"
					:option="option" />

				<OptionMenu :option="option" use-sort />

				<SortOptionIcon
					v-show="votesStore.sortByOption === option.id && !chunksLoading"
					:id="`option-sort-${option.id}`"
					class="sort-indicator"
					:title="t('polls', 'Click to remove sorting')"
					@click="() => (votesStore.sortByOption = 0)" />
			</div>
			<StickyDiv
				:id="`option-item-${option.id}`"
				class="option-item"
				:class="{
					confirmed: option.confirmed && pollStore.status.isExpired,
				}"
				:sticky-top="pollStore.viewMode === 'table-view'"
				:activate-bottom-shadow="!downPage">
				<OptionItem :option="option" />
			</StickyDiv>

			<Counter
				v-if="pollStore.permissions.seeResults"
				:id="`counter-${option.id}`"
				:key="`counter-${option.id}`"
				:class="{
					confirmed: option.confirmed && pollStore.status.isExpired,
				}"
				:show-maybe="pollStore.configuration.allowMaybe"
				:option="option" />

			<div
				v-for="participant in votesStore.getChunkedParticipants"
				:key="participant.id"
				class="vote-cell"
				:class="{ 'current-user': isCurrentUser(participant) }">
				<VoteButton
					v-if="isVotable(participant, option)"
					:key="`vote-${participant.id}-${option.id}-vote`"
					:user="participant"
					:option="option" />
				<VoteItem
					v-else
					:key="`vote-${participant.id}-${option.id}`"
					:user="participant"
					:current-user="isCurrentUser(participant)"
					:option="option" />
			</div>
		</template>
	</TransitionGroup>
</template>

<style lang="scss">
.vote-table {
	display: grid;
	grid-template-columns: max-content repeat(
			var(--options-count),
			minmax(9.5rem, 18rem)
		);
	grid-template-rows: repeat(3, auto);
	grid-auto-columns: minmax(9.5rem, 18rem);
	grid-auto-rows: auto;
	grid-auto-flow: column;
	overflow: scroll;
	margin: auto;

	.vote-cell {
		padding: 0.4rem;
		display: flex;
		justify-content: center;
	}

	.participant {
		grid-column: 1;
		padding: 0.8rem 0.1rem 0.1rem 0.1rem;
		inset-inline-start: 0;
		background-color: var(--color-main-background);

		.user-actions {
			visibility: hidden;
		}

		&.sticky-left {
			left: -8px;
			padding-left: 8px;
		}

		&:hover {
			background: var(--color-background-hover);

			.user-actions {
				visibility: visible;
			}
		}
	}

	.option-menu-grid {
		display: grid;
		grid-template-columns: repeat(3, 1fr);
		grid-template-areas: 'left middle right';
		align-content: center;
		align-self: stretch;
		flex: 0 0 34px;

		.option-menu {
			grid-area: middle;
			justify-self: center;
		}

		.sort-indicator {
			grid-area: right;
		}
	}

	.table-view & {
		overflow: visible;
		min-width: min-content;
		max-width: max-content;
		.grid-info {
			grid-row: 1;
			grid-column: 1;
			inset-inline-start: 0;
			background-color: var(--color-main-background);
		}

		.option-item-spacer {
			grid-row: 2;
			grid-column: 1;
			inset-inline-start: 0;
		}

		.counter-spacer {
			grid-row: 3;
			grid-column: 1;
			inset-inline-start: 0;
			background-color: var(--color-main-background);
		}

		.option-menu-grid {
			grid-row: 1;
			border-inline-start: 1px solid var(--color-border);
			background-color: var(--color-main-background);

			.calendar-peek {
				grid-area: left;
			}
		}

		.option-item {
			grid-row: 2;
			border-inline-start: 1px solid var(--color-border);

			// .option-item-container {
			// 	background-color: var(--color-main-background);
			// }
		}

		.counter {
			grid-row: 3;
			border-inline-start: 1px solid var(--color-border);
		}

		.vote-cell {
			border-inline-start: 1px solid var(--color-border);
		}

		> .current-user {
			margin-top: 1.5rem;
			margin-bottom: 1.5rem;
		}

		.vote-column {
			display: flex;
			flex-direction: column;
			flex: 1 0 11rem;
			border-inline-start: 1px solid var(--color-border-dark);
		}

		.option-item .option-item__option--text {
			text-align: center;
			/* Notice: https://caniuse.com/css-text-wrap-balance */
			text-wrap: balance;
			hyphens: auto;
			padding: 0 0.6em;
			margin: auto;
		}
	}

	&.list-view {
		row-gap: 8px;
		grid-template-columns:
			minmax(11rem, max-content)
			minmax(4rem, max-content)
			minmax(4rem, max-content);
		max-width: var(--cap-width);
		width: fit-content;
		align-items: center;

		.participant {
			display: none;
		}

		.option-item {
			grid-column: 1;
		}

		.counter {
			grid-column: 2;
			flex-direction: column;
		}

		.vote-cell {
			grid-column: 3;
			width: 60px;
			height: 60px;
		}

		@media only screen and (max-width: 340px) {
			.calendar-peek {
				display: none;
			}
		}
	}
}
</style>

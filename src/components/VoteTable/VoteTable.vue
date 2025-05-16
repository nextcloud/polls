<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import { t } from '@nextcloud/l10n'

import { PollType, usePollStore } from '../../stores/poll.ts'
import { useOptionsStore } from '../../stores/options.ts'
import { useVotesStore } from '../../stores/votes.ts'

import { NcButton } from '@nextcloud/vue'
import SortNameIcon from 'vue-material-design-icons/SortAlphabeticalDescending.vue'
import { computed } from 'vue'
import { getCurrentUser } from '@nextcloud/auth'
import Counter from '../Options/Counter.vue'
import CalendarPeek from '../Calendar/CalendarPeek.vue'
import OptionItem from '../Options/OptionItem.vue'
import OptionMenu from '../Options/OptionMenu.vue'
import { usePreferencesStore, ViewMode } from '../../stores/preferences.ts'

import SortOptionIcon from 'vue-material-design-icons/SortBoolAscendingVariant.vue'
import VoteItem from './VoteItem.vue'
import VoteParticipant from './VoteParticipant.vue'

const pollStore = usePollStore()
const optionsStore = useOptionsStore()
const votesStore = useVotesStore()
const preferencesStore = usePreferencesStore()

const tableStyle = computed(() => ({
	'--participants-count': `${pollStore.safeParticipants.length}`,
	'--options-count': `${optionsStore.list.length}`,
}))

const showCalendarPeek = computed(
	() =>
		pollStore.type === PollType.Date
		&& getCurrentUser()
		&& preferencesStore.user.calendarPeek,
)
</script>

<template>
	<TransitionGroup
		tag="div"
		name="list"
		class="vote-table"
		:class="[pollStore.viewMode, { closed: pollStore.isClosed }]"
		:style="tableStyle">
		<div v-if="pollStore.viewMode === ViewMode.TableView" class="grid-info">
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
		</div>

		<div
			v-if="pollStore.viewMode === ViewMode.TableView"
			class="option-spacer" />
		<div v-if="pollStore.permissions.seeResults" class="counter-spacer" />

		<template
			v-for="participant in pollStore.safeParticipants"
			:key="participant.id">
			<VoteParticipant :user="participant" />
		</template>

		<template v-for="option in optionsStore.orderedOptions" :key="option.id">
			<div
				v-if="pollStore.viewMode === ViewMode.TableView"
				class="option-menu-grid">
				<CalendarPeek
					v-if="showCalendarPeek"
					:id="`peek-${option.id}`"
					:focus-trap="false"
					:option="option" />

				<OptionMenu :id="`menu-${option.id}`" :option="option" use-sort />

				<SortOptionIcon
					v-show="votesStore.sortByOption === option.id"
					:id="`option-sort-${option.id}`"
					class="sort-indicator"
					:title="t('polls', 'Click to remove sorting')"
					@click="() => (votesStore.sortByOption = 0)" />
			</div>

			<OptionItem :id="`option-${option.id}`" :option="option" />
			<Counter
				v-if="pollStore.permissions.seeResults"
				:id="`counter-${option.id}`"
				:key="`counter-${option.id}`"
				:show-maybe="pollStore.configuration.allowMaybe"
				:option="option" />

			<div
				v-for="participant in pollStore.safeParticipants"
				:key="participant.id"
				class="vote-cell">
				<VoteItem
					:id="`vote-${participant.id}-${option.id}`"
					:key="`vote-${participant.id}-${option.id}`"
					:user="participant"
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

	.vote-cell {
		padding: 6px;
		display: flex;
	}

	.participant {
		grid-column: 1;
		padding: 6px;
		position: sticky;
		left: 0;
		z-index: 3;
		background-color: var(--color-main-background);

		.user-actions {
			visibility: hidden;
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

	&.table-view {
		.grid-info {
			grid-row: 1;
			grid-column: 1;
			position: sticky;
			left: 0;
			z-index: 5;
			background-color: var(--color-main-background);
		}

		.option-spacer {
			grid-row: 2;
			grid-column: 1;
			position: sticky;
			left: 0;
			top: 0;
			z-index: 5;
			background-color: var(--color-main-background);
		}

		.counter-spacer {
			grid-row: 3;
			grid-column: 1;
			position: sticky;
			left: 0;
			z-index: 5;
			background-color: var(--color-main-background);
		}

		.option-menu-grid {
			grid-row: 1;
			border-left: 1px solid var(--color-border);

			.calendar-peek {
				grid-area: left;
			}
		}

		.option-item {
			grid-row: 2;
			position: sticky;
			top: 0;
			z-index: 2;
			background-color: var(--color-main-background);
			border-left: 1px solid var(--color-border);
		}

		.counter {
			grid-row: 3;
			border-left: 1px solid var(--color-border);
		}

		.vote-cell {
			border-left: 1px solid var(--color-border);
		}

		.current-user {
			margin: 1rem 0;
		}

		.option-element {
			grid-row: 1;
		}

		.option-element,
		.vote-column {
			display: flex;
			flex-direction: column;
			flex: 1 0 11rem;
			border-left: 1px solid var(--color-border-dark);
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
		grid-template-columns: auto 5rem 5rem;

		.grid-info,
		.option-spacer,
		.counter-spacer,
		.participant,
		.option-menu-grid {
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
		}

		.vote-item:not(.current-user) {
			display: none;
		}

		@media only screen and (max-width: 340px) {
			.calendar-peek {
				display: none;
			}
		}
	}
}
</style>

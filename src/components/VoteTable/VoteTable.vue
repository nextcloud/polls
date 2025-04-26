<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import { showSuccess } from '@nextcloud/dialogs'
import { t } from '@nextcloud/l10n'

import UserItem from '../User/UserItem.vue'
import { PollType, usePollStore } from '../../stores/poll.ts'
import { useSessionStore } from '../../stores/session.ts'
import { useOptionsStore } from '../../stores/options.ts'
import { useVotesStore } from '../../stores/votes.ts'
import { ButtonVariant } from '@nextcloud/vue/components/NcButton'
import { NcActionButton, NcActions, NcActionText, NcButton } from '@nextcloud/vue'
import SortNameIcon from 'vue-material-design-icons/SortAlphabeticalDescending.vue'
import { computed } from 'vue'
import { getCurrentUser } from '@nextcloud/auth'
import Counter from '../Options/Counter.vue'
import CalendarPeek from '../Calendar/CalendarPeek.vue'
import OptionItem from '../Options/OptionItem.vue'
import OptionMenu from '../Options/OptionMenu.vue'
import { usePreferencesStore, ViewMode } from '../../stores/preferences.ts'

import SortOptionIcon from 'vue-material-design-icons/SortBoolAscendingVariant.vue'
import DeleteIcon from 'vue-material-design-icons/Delete.vue'
import VoteItem from './VoteItem.vue'
import VoteMenu from './VoteMenu.vue'

const pollStore = usePollStore()
const sessionStore = useSessionStore()
const optionsStore = useOptionsStore()
const votesStore = useVotesStore()
const preferencesStore = usePreferencesStore()

/**
 *
 * @param userId
 */
async function removeUser(userId: string) {
	await votesStore.resetUserVotes({ userId })
	showSuccess(t('polls', 'Participant {userId} has been removed', { userId }))
}

const showCalendarPeek = computed(
	() =>
		pollStore.type === PollType.Date
		&& getCurrentUser()
		&& preferencesStore.user.calendarPeek,
)
</script>

<template>
	<div
		class="vote-table"
		:class="[pollStore.viewMode, { closed: pollStore.isClosed }]">
		<div v-if="pollStore.viewMode === ViewMode.TableView" class="grid-info">
			<NcButton
				v-if="votesStore.sortByOption > 0"
				class="sort-indicator"
				:title="t('polls', 'Click to sort by name')"
				:button-variant="ButtonVariant.TertiaryNoBackground"
				@click="() => (votesStore.sortByOption = 0)">
				<template #icon>
					<SortNameIcon />
				</template>
			</NcButton>
		</div>

		<TransitionGroup
			v-if="pollStore.viewMode === 'table-view'"
			tag="div"
			name="list"
			class="vote-table__users grid-users">
			<div
				v-for="participant in pollStore.safeParticipants"
				:key="participant.id"
				:class="[
					'participant',
					{
						'current-user':
							participant.id === sessionStore.currentUser.id,
					},
				]">
				<UserItem :user="participant" condensed>
					<template
						v-if="
							pollStore.permissions.edit
							|| participant.id === sessionStore.currentUser.id
						"
						#menu>
						<NcActions
							v-if="
								participant.id !== sessionStore.currentUser.id
								&& pollStore.permissions.changeForeignVotes
							"
							class="user-menu"
							placement="right"
							:variant="ButtonVariant.TertiaryNoBackground"
							force-menu>
							<NcActionText :name="participant.displayName" />
							<NcActionButton
								:name="
									t('polls', 'Remove votes of {displayName}', {
										displayName: participant.displayName,
									})
								"
								@click="removeUser(participant.id)">
								<template #icon>
									<DeleteIcon />
								</template>
							</NcActionButton>
						</NcActions>
						<VoteMenu
							v-if="participant.id === sessionStore.currentUser.id"
							class="user-menu"
							placement="right"
							:variant="ButtonVariant.TertiaryNoBackground"
							force-menu
							no-menu-icon>
						</VoteMenu>
					</template>
				</UserItem>
			</div>
		</TransitionGroup>

		<TransitionGroup
			tag="div"
			name="list"
			class="vote-table__options grid-options">
			<div v-for="option in optionsStore.orderedOptions" :key="option.id">
				<div class="option-menu-grid">
					<OptionMenu
						v-if="pollStore.viewMode === ViewMode.TableView"
						:option="option"
						use-sort />
					<SortOptionIcon
						v-if="
							votesStore.sortByOption === option.id
							&& pollStore.viewMode === ViewMode.TableView
						"
						class="sort-indicator"
						:title="t('polls', 'Click to remove sorting')"
						@click="() => (votesStore.sortByOption = 0)" />
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
			</div>
		</TransitionGroup>

		<TransitionGroup tag="div" name="list" class="vote-table__votes grid-votes">
			<TransitionGroup
				v-for="option in optionsStore.orderedOptions"
				:key="option.id"
				tag="div"
				name="list">
				<VoteItem
					v-for="participant in pollStore.safeParticipants"
					:key="participant.id"
					:user="participant"
					:option="option" />
			</TransitionGroup>
		</TransitionGroup>
	</div>
</template>

<style lang="scss">
.vote-table {
	overflow-x: scroll;
	display: grid;
	grid-template-columns: 7.5rem auto;
	grid-template-areas: 'info options' 'users votes';

	.participant,
	.vote-item {
		height: 4.5em;
		order: 10;
		padding: 6px;
		border-radius: 12px;
	}

	.participant {
		display: flex;
		align-self: stretch;
		justify-content: center;
		max-width: 7rem;

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

	.vote-table__users {
		display: flex;
		flex-direction: column;
		padding-bottom: 4px;
		align-items: flex-start;
	}

	.vote-table__votes {
		display: flex;
	}

	.vote-column {
		order: 2;
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
			justify-self: end;
		}
	}

	.grid-info {
		grid-area: info;
		position: sticky;
		left: 0;
		top: 0;
		z-index: 5;
		background-color: var(--color-main-background);
	}

	.grid-users {
		grid-area: users;
		position: sticky;
		left: 0;
		z-index: 4;
		background-color: var(--color-main-background);
	}

	.grid-votes,
	.grid-options {
		display: flex;
	}

	.grid-votes {
		grid-area: votes;
	}

	.grid-options {
		grid-area: options;
		position: sticky;
		top: 0;
		z-index: 2;
		background-color: var(--color-main-background);
		// TODO Quickfix: the grid area seems to be less wide, that it should be
		// give the sticky divs inside grid-options
		& > div {
			background-color: var(--color-main-background);
		}
	}

	&.table-view {
		.current-user {
			margin-bottom: 30px;
		}

		.grid-votes,
		.grid-options {
			> div {
				flex: 1 0 11rem;
				border-left: 1px solid var(--color-border-dark);
			}
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
		flex: 1;
		grid-template-columns: auto 5rem;
		grid-template-areas: 'options votes';

		.grid-votes,
		.grid-options {
			align-items: stretch;
			flex-direction: column;
			> div {
				flex: 1;
				display: grid;
				align-items: center;
				justify-content: stretch;
				min-height: 6.4rem;
			}
		}

		.column-header {
			display: grid;
			grid-template-columns: auto 4rem;
		}

		.counter {
			flex-direction: column;
			justify-content: space-evenly;
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

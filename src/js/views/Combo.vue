<!--
  - @copyright Copyright (c) 2018 René Gieling <github@dartcafe.de>
  -
  - @author René Gieling <github@dartcafe.de>
  -
  - @license GNU AGPL version 3 or any later version
  -
  - This program is free software: you can redistribute it and/or modify
  - it under the terms of the GNU Affero General Public License as
  - published by the Free Software Foundation, either version 3 of the
  - License, or (at your option) any later version.
  -
  - This program is distributed in the hope that it will be useful,
  - but WITHOUT ANY WARRANTY; without even the implied warranty of
  - MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  - GNU Affero General Public License for more details.
  -
  - You should have received a copy of the GNU Affero General Public License
  - along with this program.  If not, see <http://www.gnu.org/licenses/>.
  -
  -->

<template>
	<AppContent class="combo">
		<ComboTitle />
		<div v-if="combo.description" class="area__header">
			{{ combo.description }}
		</div>

		<div class="area__main" :class="viewMode">
			<div v-show="combo.polls.length" class="combo-table" :class="viewMode">
				<div class="vote-table__users">
					<div class="spacer" />
					<div v-for="(poll) in polls"
						:key="poll.id"
						v-tooltip.auto="poll.title"
						class="poll-group">
						<div v-for="(participant) in participantsByPoll(poll.id)"
							:key="`${participant.userId}_${participant.pollId}`"
							class="participant">
							<UserItem v-bind="participant" condensed />
						</div>
					</div>
				</div>

				<transition-group name="list" tag="div" class="vote-table__votes">
					<VoteColumn v-for="(option) in options"
						:key="option.id"
						:option="option"
						:view-mode="viewMode" />
				</transition-group>
			</div>
			<EmptyContent v-if="!combo.polls.length" icon="icon-polls">
				{{ t('polls', 'No polls selected') }}
				<template #desc>
					{{ t('polls', 'Select polls by clicking on them in the right sidebar!') }}
				</template>
			</EmptyContent>
		</div>

		<LoadingOverlay v-if="isLoading" />
	</AppContent>
</template>

<script>
import { mapGetters, mapState } from 'vuex'
import { AppContent, EmptyContent } from '@nextcloud/vue'
import { emit } from '@nextcloud/event-bus'
import ComboTitle from '../components/Combo/ComboTitle'
import VoteColumn from '../components/Combo/VoteColumn'

export default {
	name: 'Combo',
	components: {
		AppContent,
		EmptyContent,
		LoadingOverlay: () => import('../components/Base/LoadingOverlay'),
		ComboTitle,
		VoteColumn,
	},

	data() {
		return {
			isLoading: false,
			viewMode: 'table-view',
		}
	},

	computed: {
		...mapState({
			combo: (state) => state.combo,
			polls: (state) => state.combo.polls,
			participants: (state) => state.combo.participants,
		}),

		...mapGetters({
			options: 'combo/uniqueOptions',
			participantsByPoll: 'combo/participantsInPoll',
		}),
		/* eslint-disable-next-line vue/no-unused-properties */
		windowTitle() {
			return `${t('polls', 'Polls')} - ${this.combo.title}`
		},
	},

	created() {
		// simulate @media:prefers-color-scheme until it is supported for logged in users
		// This simulates the theme--dark
		// TODO: remove, when completely supported by core
		if (!window.matchMedia) {
			return true
		}
		emit('polls:sidebar:toggle', { open: (window.innerWidth > 920) })
	},

	beforeDestroy() {
		// this.$store.dispatch({ type: 'combo/reset' })
	},

}

</script>

<style lang="scss" >
.combo-title {
	margin-bottom: 16px;
}

.combo-table {
	display: flex;
	flex: 1;
	.vote-table {
		display: flex;
	}
	.poll-group {
		display: flex;
		flex-direction: column;
	}

	.participant {
		display: flex;
	}

	.participant, .vote-item {
		flex: 0 0 auto;
		height: 4.5em;
		order: 10;
		line-height: 1.5em;
		padding: 4px;
		border-top: solid 1px var(--color-border-dark);
		&.currentuser {
			order:5;
		}
	}

	.vote-table__users {
		display: flex;
		flex-direction: column;
		overflow-x: scroll;
		margin-bottom: 4px;
	}

	.vote-table__votes {
		display: flex;
		flex: 1;
		overflow-x: scroll;
	}

	.vote-column {
		order: 2;
		display: flex;
		flex: 1 0 85px;
		flex-direction: column;
		align-items: stretch;
		max-width: 280px;
		border-left: 1px solid var(--color-border-dark);
		margin-bottom: 4px;

		& .vote-table-header-item, & .vote-item {
			display: flex;
			justify-content: center;
			align-items: center;
		}
		.vote-table-header-item {
			align-items: stretch;
			flex: 1;
			// padding: 0 8px;
			order: 0;
		}
	}

	&.closed .vote-column {
		&.confirmed {
			order: 1;
			border-radius: 10px;
			border: 1px solid var(--color-polls-foreground-yes);
			background-color: var(--color-polls-background-yes);
			margin: 4px 4px;
		}
	}

	.vote-item {
		background-clip: content-box;
	}

	.confirmation {
		order:3;
		padding: 4px;
	}

	.counter {
		order:3;
	}

	.calendar-peek {
		order:2;
	}

	.confirm {
		height: 45px;
		order: 20;
	}

	.owner {
		display: flex;
		flex: 0 auto;
		height: 1.6em;
		line-height: 1.6em;
		min-width: 24px;
		order: 19;
	}

	.spacer {
		flex: 1;
		order: 0;
	}

	&.table-view {
		.vote-table__users::after, .vote-column::after {
			content: '';
			height: 8px;
			order: 99;
		}

		.participant {
			max-width: 245px;
		}

		.option-item .option-item__option--text {
			text-align: center;
		}
	}

	&.list-view {
		flex-direction: column;

		&.closed {
			.vote-item:not(.confirmed) {
				background-color: var(--color-main-background);
				&.no > .icon {
					background-image: var(--icon-polls-no)
				}
			}

			.vote-column {
				padding: 2px 8px;
				&.confirmed {
					margin: 4px 0;
				}
			}
		}

		.vote-table__users .confirm {
			display: none;
		}

		.vote-column {
			flex-direction: row-reverse;
			align-items: center;
			max-width: initial;
			position: relative;
			border-top: solid 1px var(--color-border);
			border-left: none;
			padding: 0;
		}

		.vote-table__users {
			margin: 0;

			.owner {
				display: none;
			}
		}

		.participant:not(.currentuser), .vote-item:not(.currentuser) {
			display: none;
		}

		.participant.currentuser {
			border-top: none;
		}

		.vote-table__votes {
			align-items: stretch;
			flex-direction: column;
		}

		.vote-table-header-item {
			flex-direction: row;
			.option-item {
				padding: 8px 4px;
			}
		}

		.counter {
			order: 0;
			padding-left: 12px;
		}

		.vote-item.currentuser {
			border: none;
		}

		.owner {
			order: 0;
		}

		@media only screen and (max-width: 370px) {
			.owner {
				display: none;
			}
		}

		@media only screen and (max-width: 340px) {
			.calendar-peek {
				display: none;
			}
		}

		.calendar-peek {
			order: 0;
			padding-left:4px;
		}

		.calendar-peek__conflict.icon {
			width: 24px;
			height: 24px;
		}

		.calendar-peek__caption {
			display: none;
		}

		.confirm {
			display: none;
		}

		.option-item.date-box {
			align-items: baseline;
		}

		.option-item__option--datebox {
			min-width: 120px;
		}
	}
}

</style>

<!--
  - @copyright Copyright (c) 2022 Michael Longo <contact@tiller.fr>
  - 
  - @author Michael Longo <contact@tiller.fr>
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
  - MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
  - GNU Affero General Public License for more details.
  -
  - You should have received a copy of the GNU Affero General Public License
  - along with this program. If not, see <http://www.gnu.org/licenses/>.
  -
  -->

  <template>
	<div>
		<NcDashboardWidget :items="relevantPolls"
			empty-content-icon="icon-polls"
			:empty-content-message="t('polls', 'No polls')"
			:show-more-text="t('polls', 'relevant polls')"
			:loading="loading"
			@hide="() => {}"
			@markDone="() => {}">
			<template #default="{ item }">
				<a :href="pollLink(item)">
					<div class="poll-item__item">
						<div class="item__icon-spacer">
							<TextPollIcon v-if="item.type === 'textPoll'" />
							<DatePollIcon v-else />
						</div>

						<div class="item__title">
							<div class="item__title__title">
								{{ item.title }}
							</div>

							<div class="item__title__description">
								{{ item.description ? item.description : t('polls', 'No description provided') }}
							</div>
						</div>
					</div>
				</a>
			</template>
		</NcDashboardWidget>
	</div>
</template>

<script>
import PlusIcon from 'vue-material-design-icons/Plus.vue'
import { NcButton, NcDashboardWidget } from '@nextcloud/vue'
import TextPollIcon from 'vue-material-design-icons/FormatListBulletedSquare.vue'
import DatePollIcon from 'vue-material-design-icons/CalendarBlank.vue'
import { mapGetters } from 'vuex'
import { generateUrl } from '@nextcloud/router'

export default {
	name: 'Dashboard',
	components: {
		NcDashboardWidget,
		TextPollIcon,
		DatePollIcon,
		NcButton,
		PlusIcon,
		PollItem: () => import('../components/PollList/PollItem.vue'),
	},
	data() {
		return {
			loading: false,
		}
	},
	computed: {
		...mapGetters({
            filteredPolls: 'polls/filtered',
        }),
		relevantPolls() {
			const list = [
				...this.filteredPolls('relevant'),
			]
			return list.slice(0, 6)
		},
		pollLink() {
			return (card) => {
				return generateUrl(`/apps/polls/vote/${card.id}`)
			}
		},
	},
	beforeMount() {
		this.loading = true
		this.$store.dispatch('polls/list').then(() => {
			this.loading = false
		})
	}
}
</script>
<style lang="scss">
	[class^='poll-item__'] {
		display: flex;
		flex: 1;
		padding: 4px 8px;
		border-bottom: 1px solid var(--color-border-dark);
	}

	[class^='item__'],
	.poll-item__item .action-item {
		display: flex;
		align-items: center;
		flex: 0 0 auto;
		overflow: hidden;
		white-space: nowrap;
		text-overflow: ellipsis;
	}

	.item__title {
		display: flex;
		flex-direction: column;
		flex: 1 0 155px;
		align-items: stretch;
		justify-content: center;

		.item__title__title {
			display: block;
		}

		.item__title__description {
			opacity: 0.5;
			display: block;
		}
	}

	.poll-item__item {
		border-radius: var(--border-radius-large);

		&> .action-item {
			display: flex;
		}
		&.active {
			background-color: var(--color-primary-light);
		}
		&:hover {
			background-color: var(--color-background-hover);
		}
	}

	.item__icon-spacer {
		width: 44px;
		min-width: 44px;
	}

	[class^='item__type'] {
		width: 44px;
		min-width: 16px;
		min-height: 16px;
	}
</style>

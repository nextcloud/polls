<!--
  - SPDX-FileCopyrightText: 2022 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<div>
		<NcDashboardWidget :items="relevantPolls"
			:empty-content-message="t('polls', 'No polls found for this category')"
			:show-more-text="t('polls', 'Relevant polls')"
			:loading="loading">

			<template #emptyContentIcon>
				<PollsAppIcon />
			</template>

			<template #default="{ item }">
				<a :href="pollLink(item)">
					<div class="poll-item__item">
						<div class="item__icon-spacer">
							<TextPollIcon v-if="item.type === 'textPoll'" />
							<TextPollIcon v-else-if="item.type === 'textRankPoll'" />
							<DatePollIcon v-else />
						</div>

						<div class="item__title">
							<div class="item__title__title">
								{{ item.configuration.title }}
							</div>

							<div class="item__title__description">
								{{ item.configuration.description ? item.configuration.description : t('polls', 'No description provided') }}
							</div>
						</div>
					</div>
				</a>
			</template>
		</NcDashboardWidget>
	</div>
</template>

<script>
import { NcDashboardWidget } from '@nextcloud/vue'
import { showError } from '@nextcloud/dialogs'
import TextPollIcon from 'vue-material-design-icons/FormatListBulletedSquare.vue'
import DatePollIcon from 'vue-material-design-icons/CalendarBlank.vue'
import { PollsAppIcon } from '../components/AppIcons/index.js'
import { mapGetters, mapState, mapActions } from 'vuex'
import { generateUrl } from '@nextcloud/router'

export default {
	name: 'Dashboard',
	components: {
		NcDashboardWidget,
		DatePollIcon,
		PollsAppIcon,
		TextPollIcon,
	},

	computed: {
		...mapState({
			loading: (state) => state.polls.status.loading,
		}),

		...mapGetters({
			filteredPolls: 'polls/filteredByCategory',
		}),

		relevantPolls() {
			const list = [
				...this.filteredPolls('relevant'),
			]
			return list.slice(0, 6)
		},

		pollLink() {
			return (poll) => generateUrl(`/apps/polls/vote/${poll.id}`)
		},
	},

	beforeMount() {
		this.loadPolls().then(() => {
		}).catch(() => {
			showError(t('polls', 'Error loading poll list'))
		})
	},

	methods: {
		...mapActions({
			loadPolls: 'polls/list',
		}),
	},
}

</script>

<style lang="scss" scoped>
	.poll-item__item {
		display: flex;
		padding: 4px 0;

		&.active {
			background-color: var(--color-primary-element-light);
		}

		&:hover {
			background-color: var(--color-background-hover);
		}
	}

	.item__title {
		display: flex;
		flex-direction: column;
		overflow: hidden;

		*  {
			display: block;
			overflow: hidden;
			white-space: nowrap;
			text-overflow: ellipsis;
		}

		.item__title__description {
			opacity: 0.5;
		}
	}

	.item__icon-spacer {
		width: 44px;
		min-width: 44px;
	}
</style>

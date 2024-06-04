<!--
  - SPDX-FileCopyrightText: 2022 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<div>
		<NcDashboardWidget :items="pollsStore.dashboardList"
			:empty-content-message="dashboardWidgetProperties.emptyContentMessage"
			:show-more-text="dashboardWidgetProperties.showMoreText"
			:loading="pollsStore.pollsLoading">

			<template #emptyContentIcon>
				<PollsAppIcon />
			</template>

			<template #default="{ item }">
				<a :href="pollLink(item)">
					<div class="poll-item__item">
						<div class="item__icon-spacer">
							<TextPollIcon v-if="item.type === 'textPoll'" />
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
import { generateUrl } from '@nextcloud/router'
import { t } from '@nextcloud/l10n'
import { mapStores } from 'pinia';
import { usePollsStore } from '../stores/polls.ts'
import { Logger } from '../helpers/index.ts'

export default {
	name: 'Dashboard',
	components: {
		NcDashboardWidget,
		DatePollIcon,
		PollsAppIcon,
		TextPollIcon,
	},

	data() {
		return {
			dashboardWidgetProperties: {
				emptyContentMessage: t('polls', 'No polls found for this category'),
				showMoreText: t('polls', 'Relevant polls'),
			},
		}
	},

	computed: {
		...mapStores(usePollsStore),

		pollLink() {
			return (poll) => generateUrl(`/apps/polls/vote/${poll.id}`)
		},

	},

	beforeMount() {
		this.loadPolls()
	},

	methods: {
		async loadPolls() {
			Logger.debug('Loading polls in dashboard widget')
			this.pollsStore.load().then(() => null).catch(() => {
				showError(t('polls', 'Error loading poll list'))
			})
		},
		t,
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
../stores/polls.ts
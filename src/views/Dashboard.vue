<!--
  - SPDX-FileCopyrightText: 2022 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import { onMounted } from 'vue'
import { generateUrl } from '@nextcloud/router'
import { showError } from '@nextcloud/dialogs'
import { t } from '@nextcloud/l10n'

import NcDashboardWidget from '@nextcloud/vue/components/NcDashboardWidget'

import TextPollIcon from 'vue-material-design-icons/FormatListBulletedSquare.vue'
import DatePollIcon from 'vue-material-design-icons/CalendarBlank.vue'

import { PollsAppIcon } from '../components/AppIcons/index.ts'
import { Logger } from '../helpers/index.ts'
import { usePollsStore } from '../stores/polls.ts'

const dashboardWidgetProperties = {
	emptyContentMessage: t('polls', 'No polls found for this category'),
	showMoreText: t('polls', 'Relevant polls'),
}

const pollsStore = usePollsStore()

/**
 * Load the polls
 */
function loadPolls(): void {
	Logger.debug('Loading polls in dashboard widget')
	try {
		pollsStore.load()
	} catch (error) {
		showError(t('polls', 'Error setting dashboard list'))
	}
}

onMounted(() => {
	loadPolls()
})
</script>

<template>
	<div>
		<NcDashboardWidget
			:items="pollsStore.dashboardList"
			:empty-content-message="dashboardWidgetProperties.emptyContentMessage"
			:show-more-text="dashboardWidgetProperties.showMoreText"
			:loading="pollsStore.pollsLoading">
			<template #emptyContentIcon>
				<PollsAppIcon />
			</template>

			<template #default="{ item }">
				<a :href="generateUrl(`/apps/polls/vote/${item.id}`)">
					<div class="poll-item__item">
						<div class="item__icon-spacer">
							<TextPollIcon v-if="item.type === 'textPoll'" />
							<TextPollIcon v-else-if="item.type === 'genericPoll'" />
							<DatePollIcon v-else />
						</div>

						<div class="item__title">
							<div class="item__title__title">
								{{ item.configuration.title }}
							</div>

							<div class="item__title__description">
								{{
									item.configuration.description
										? item.configuration.description
										: t('polls', 'No description provided')
								}}
							</div>
						</div>
					</div>
				</a>
			</template>
		</NcDashboardWidget>
	</div>
</template>

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

	* {
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

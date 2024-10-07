<!--
  - SPDX-FileCopyrightText: 2022 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup>
	import { onMounted } from 'vue'
	import { showError } from '@nextcloud/dialogs'
	import { t } from '@nextcloud/l10n'
	import { generateUrl } from '@nextcloud/router'
	import NcDashboardWidget from '@nextcloud/vue/dist/Components/NcDashboardWidget.js'
	import { usePollsStore } from '../stores/polls.ts'
	import { PollType } from '../Types/index.ts'
	import { Logger } from '../helpers/index.ts'

	import TextPollIcon from 'vue-material-design-icons/FormatListBulletedSquare.vue'
	import DatePollIcon from 'vue-material-design-icons/CalendarBlank.vue'
	import { PollsAppIcon } from '../components/AppIcons/index.js'

	const dashboardWidgetProperties = {
		emptyContentMessage: t('polls', 'No polls found for this category'),
		showMoreText: t('polls', 'Relevant polls'),
	}

	const pollsStore = usePollsStore()

	/**
	 *
	 * @param {object} poll - The poll object
	 */
	function pollLink(poll) {
		generateUrl(`/apps/polls/vote/${poll.id}`)
	}

	/**
	 * Load the polls
	 */
	function loadPolls() {
		Logger.debug('Loading polls in dashboard widget')
		pollsStore.load().then(() => null).catch(() => {
			showError(t('polls', 'Error loading poll list'))
		})
	}

	onMounted(() => {
		loadPolls()
	})

</script>

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
							<TextPollIcon v-if="item.type === PollType.Text" />
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

<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import { RouterLink } from 'vue-router'
import { computed } from 'vue'
import moment from '@nextcloud/moment'
import { t } from '@nextcloud/l10n'
import { usePollStore, AccessType, Poll, PollType } from '../../stores/poll'
import { StatusResults } from '../../Types/index.ts'

// Icons
import TextPollIcon from 'vue-material-design-icons/FormatListBulletedSquare.vue'
import DatePollIcon from 'vue-material-design-icons/CalendarBlank.vue'
import ExpirationIcon from 'vue-material-design-icons/CalendarEnd.vue'
import PrivatePollIcon from 'vue-material-design-icons/Key.vue'
import OpenPollIcon from 'vue-material-design-icons/Earth.vue'
import ArchivedPollIcon from 'vue-material-design-icons/Archive.vue'
import BadgeSmallDiv from '../Base/modules/BadgeSmallDiv.vue'

export interface Props {
	poll?: Poll
	noLink?: boolean
}

const pollStore = usePollStore()

const props = withDefaults(defineProps<Props>(), {
	header: false,
	poll: undefined,
	noLink: false,
})

const closeToClosing = computed(
	() =>
		!props.poll.status.isExpired &&
		props.poll.configuration.expire &&
		moment.unix(props.poll.configuration.expire).diff() < 86400000,
)

const pollTypeName = computed(() => {
	if (props.poll.type === PollType.Text) {
		return t('polls', 'Text poll')
	}
	return t('polls', 'Date poll')
})

const timeExpirationRelative = computed(() => {
	if (props.poll.configuration.expire) {
		return moment.unix(props.poll.configuration.expire).fromNow()
	}
	return t('polls', 'never')
})

const expiryClass = computed(() => {
	if (props.poll.status.isExpired) {
		return StatusResults.Error
	}

	if (props.poll.configuration.expire && closeToClosing.value) {
		return StatusResults.Warning
	}

	if (props.poll.configuration.expire && !props.poll.status.isExpired) {
		return StatusResults.Success
	}

	return StatusResults.Success
})

const timeCreatedRelative = computed(() =>
	moment.unix(props.poll.status.created).fromNow(),
)
</script>

<template>
	<div class="poll-item">
		<TextPollIcon
			v-if="props.poll.type === PollType.Text"
			class="item__type"
			:title="pollTypeName" />
		<DatePollIcon v-else class="item__type" :title="pollTypeName" />

		<div
			v-if="props.noLink"
			class="item__title"
			:class="{ closed: props.poll.status.isExpired }">
			<div class="title">
				{{ props.poll.configuration.title }}
			</div>

			<div class="description">
				{{
					props.poll.configuration.description
						? props.poll.configuration.description
						: t('polls', 'No description provided')
				}}
			</div>
		</div>

		<RouterLink
			v-else
			class="item__title"
			:title="props.poll.configuration.description"
			:to="{ name: 'vote', params: { id: props.poll.id } }"
			:class="{
				closed: props.poll.status.isExpired,
				active: props.poll.id === pollStore.id,
			}">
			<div class="title_line">
				<span class="title">
					{{ props.poll.configuration.title }}
				</span>
				<BadgeSmallDiv v-if="props.poll.configuration.expire" :class="expiryClass">
					<template #icon>
						<ExpirationIcon :size="16"/>
					</template>
					{{ timeExpirationRelative }}
				</BadgeSmallDiv>
			</div>

			<div class="description_line">
				<!-- <BadgeSmallDiv class="item_access">
					<template #icon>
						<ArchivedPollIcon v-if="props.poll.status.isDeleted" :size="16"/>
						<OpenPollIcon v-else-if="props.poll.configuration.access === AccessType.Open" :size="16"/>
						<PrivatePollIcon v-else :size="16"/>
					</template>
					<span v-if="props.poll.status.isDeleted">
						{{ t('polls', 'Archived  poll') }}
					</span>
					<span v-else>
						{{ props.poll.configuration.access === AccessType.Open ? t('polls', 'Openly accessible poll') : t('polls', 'Private poll')}}
					</span>
				</BadgeSmallDiv> -->

				<ArchivedPollIcon v-if="props.poll.status.isDeleted" :title="t('polls', 'Archived  poll')" :size="16"/>
				<OpenPollIcon v-else-if="props.poll.configuration.access === AccessType.Open" :title="t('polls', 'Openly accessible poll')" :size="16"/>
				<PrivatePollIcon v-else :title="t('polls', 'Private poll')" :size="16"/>

				<span class="description">
					{{ t('polls', 'from {ownerName}, started {relativeTime}', {
						ownerName: props.poll.owner.displayName,
						'relativeTime': timeCreatedRelative,
					}) }}
				</span>

			</div>
		</RouterLink>

		<slot name="actions" />
	</div>
</template>

<style lang="scss">
.poll-item {
	display: flex;
	column-gap: 4px;
	align-items: center;
	padding: 4px 0;
	border-bottom: 1px solid var(--color-border-dark);

	&.active {
		background-color: var(--color-primary-element-light);
	}

	&:hover {
		background-color: var(--color-background-hover);
	}

	.item__type {
		flex: 0 0 44px;
	}

	.item__title {
		flex: 1 0 170px;
		overflow: hidden;
	}

	.title_line,
	.description_line {
		display: flex;
		gap: 8px;
		.title, .description {
			overflow: hidden;
			text-overflow: ellipsis;
			white-space: nowrap;
		}
		.title {
			flex: 1;
			font-weight: 600;
		}
	}

	.title-line {
		justify-content: space-between;
	}
	.description_line {
		opacity: 0.5;
	}

	.action-item {
		display: flex;
		flex: 0 0 40px;
		justify-content: center;
	}
}
</style>

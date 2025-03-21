<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import { RouterLink } from 'vue-router'
import { computed } from 'vue'
import moment from '@nextcloud/moment'
import { BadgeDiv } from '../Base/index.js'
import { t } from '@nextcloud/l10n'
import UserItem from '../User/UserItem.vue'
import { usePollsStore, SortType } from '../../stores/polls.ts'
import { usePollStore, AccessType, Poll, PollType } from '../../stores/poll'
import { StatusResults } from '../../Types/index.ts'

// Icons
import TextPollIcon from 'vue-material-design-icons/FormatListBulletedSquare.vue'
import DatePollIcon from 'vue-material-design-icons/CalendarBlank.vue'
import CreationIcon from 'vue-material-design-icons/ClockOutline.vue'
import ExpirationIcon from 'vue-material-design-icons/CalendarEnd.vue'
import PrivatePollIcon from 'vue-material-design-icons/Key.vue'
import OpenPollIcon from 'vue-material-design-icons/Earth.vue'
import ArchivedPollIcon from 'vue-material-design-icons/Archive.vue'

export interface Props {
	header?: boolean
	poll?: Poll
	noLink?: boolean
}

const pollsStore = usePollsStore()
const pollStore = usePollStore()

const props = withDefaults(defineProps<Props>(), {
	header: false,
	poll: undefined,
	noLink: false,
})

const emit = defineEmits(['sortList'])

const closeToClosing = computed(
	() =>
		!props.poll.status.isExpired &&
		props.poll.configuration.expire &&
		moment.unix(props.poll.configuration.expire).diff() < 86400000,
)

const accessType = computed(() => {
	if (props.poll.status.isDeleted) {
		return t('polls', 'Archived')
	}

	if (props.poll.configuration.access === AccessType.Open) {
		return t('polls', 'Openly accessible poll')
	}

	return t('polls', 'Private poll')
})

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
	<div v-if="props.header" class="poll-item header">
		<div class="item__type" />
		<div
			:class="[
				'item__title',
				'sortable',
				{ sort: pollsStore.sort.by === SortType.Title },
				{ reverse: pollsStore.sort.reverse },
			]"
			@click="emit('sortList', { sortBy: SortType.Title })">
			{{ t('polls', 'Title') }}
			<!-- <span :class="['sort-indicator', { 'hidden': sortBy !== 'title'}, reverse ? 'icon-triangle-s' : 'icon-triangle-n']" /> -->
		</div>

		<div class="item__action" />

		<div
			:class="[
				'item__access',
				'sortable',
				{ sort: pollsStore.sort.by === SortType.Access },
				{ reverse: pollsStore.sort.reverse },
			]"
			@click="emit('sortList', { sortBy: SortType.Access })">
			{{ t('polls', 'Access') }}
			<!-- <span :class="['sort-indicator', { 'hidden': sortBy !== 'access'}, reverse ? 'icon-triangle-s' : 'icon-triangle-n']" /> -->
		</div>

		<div
			:class="[
				'item__owner',
				'sortable',
				{ sort: pollsStore.sort.by === SortType.Owner },
				{ reverse: pollsStore.sort.reverse },
			]"
			@click="emit('sortList', { sortBy: SortType.Owner })">
			{{ t('polls', 'Owner') }}
			<!-- <span :class="['sort-indicator', { 'hidden': sortBy !== 'owner.displayName'}, reverse ? 'icon-triangle-s' : 'icon-triangle-n']" /> -->
		</div>

		<div class="poll-item__wrapper">
			<div
				:class="[
					'item__created',
					'sortable',
					{ sort: pollsStore.sort.by === SortType.Created },
					{ reverse: pollsStore.sort.reverse },
				]"
				@click="emit('sortList', { sortBy: SortType.Created })">
				{{ t('polls', 'Created') }}
				<!-- <span :class="['sort-indicator', { 'hidden': sortBy !== 'created'}, reverse ? 'icon-triangle-s' : 'icon-triangle-n']" /> -->
			</div>

			<div
				:class="[
					'item__expiry',
					'sortable',
					{ sort: pollsStore.sort.by === SortType.Expire },
					{ reverse: pollsStore.sort.reverse },
				]"
				@click="emit('sortList', { sortBy: SortType.Expire })">
				{{ t('polls', 'Closing date') }}
				<!-- <span :class="['sort-indicator', { 'hidden': sortBy !== 'expire'}, reverse ? 'icon-triangle-s' : 'icon-triangle-n']" /> -->
			</div>
		</div>
	</div>

	<div v-else class="poll-item content">
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
			:to="{ name: 'vote', params: { id: props.poll.id } }"
			:class="{
				closed: props.poll.status.isExpired,
				active: props.poll.id === pollStore.id,
			}">
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
		</RouterLink>

		<slot name="actions" />

		<ArchivedPollIcon
			v-if="props.poll.status.isDeleted"
			:title="accessType"
			class="item__access" />
		<OpenPollIcon
			v-else-if="props.poll.configuration.access === AccessType.Open"
			:title="accessType"
			class="item__access" />
		<PrivatePollIcon v-else :title="accessType" class="item__access" />

		<div class="item__owner">
			<UserItem :user="props.poll.owner" condensed />
		</div>

		<div class="poll-item__wrapper">
			<BadgeDiv class="item__created">
				<template #icon>
					<CreationIcon />
				</template>
				{{ timeCreatedRelative }}
			</BadgeDiv>
			<BadgeDiv :class="['item__expiry', expiryClass]">
				<template #icon>
					<ExpirationIcon />
				</template>
				{{ timeExpirationRelative }}
			</BadgeDiv>
		</div>
	</div>
</template>

<style lang="scss">
.poll-item {
	display: flex;
	column-gap: 4px;
	align-items: center;
	padding: 4px 0;
	border-bottom: 1px solid var(--color-border-dark);

	.item__type {
		flex: 0 0 44px;
	}

	.item__title {
		flex: 1 0 170px;
	}

	.item__action,
	.action-item,
	.item__access,
	.item__owner {
		display: flex;
		flex: 0 0 80px;
		justify-content: center;
	}

	.item__created,
	.item__expiry {
		flex: 0 1 148px;
	}

	.poll-item__wrapper {
		display: flex;
		flex: 0 1 300px;
		column-gap: 4px;
		flex-wrap: wrap;
		align-items: center;
		overflow: hidden;

		.badge {
			height: 2.5em;
		}
	}

	&.header {
		opacity: 0.7;
		height: 4em;

		[class^='item__'] {
			display: flex;
			align-items: baseline;
			overflow: hidden;
			text-overflow: ellipsis;
			white-space: nowrap;
		}

		.sortable {
			cursor: pointer;
		}

		.sort::after {
			display: inline-block;
			content: '';
			width: 8px;
			height: 8px;
			margin-left: 4px;
			background-image: var(--icon-triangle-n-dark);
			background-repeat: no-repeat;
			background-position: center;
		}

		.sort.reverse::after {
			background-image: var(--icon-triangle-s-dark);
		}
	}

	&.content {
		.item__title {
			overflow: hidden;

			> * {
				overflow: hidden;
				text-overflow: ellipsis;
				white-space: nowrap;
			}

			.title {
				font-weight: 600;
			}

			.description {
				opacity: 0.5;
			}
		}

		.item__created,
		.item__expiry {
			overflow: hidden;
			text-overflow: ellipsis;
			white-space: nowrap;
		}

		&.active {
			background-color: var(--color-primary-element-light);
		}

		&:hover {
			background-color: var(--color-background-hover);
		}
	}
}
</style>

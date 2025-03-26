<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<div v-if="header" class="poll-item header">
		<div class="item__type" />
		<div :class="['item__title', 'sortable', { 'sort': sortBy === 'title'}, { reverse }]"
			@click="$emit('sort-list', { sortBy: 'title'})">
			{{ t('polls', 'Title') }}
			<!-- <span :class="['sort-indicator', { 'hidden': sortBy !== 'title'}, reverse ? 'icon-triangle-s' : 'icon-triangle-n']" /> -->
		</div>

		<div class="item__action" />

		<div :class="['item__access', 'sortable', { 'sort': sortBy === 'access'}, { reverse }]"
			@click="$emit('sort-list', { sortBy: 'access'})">
			{{ t('polls', 'Access') }}
			<!-- <span :class="['sort-indicator', { 'hidden': sortBy !== 'access'}, reverse ? 'icon-triangle-s' : 'icon-triangle-n']" /> -->
		</div>

		<div :class="['item__owner', 'sortable', { 'sort': sortBy === 'owner.displayName'}, { reverse }]"
			@click="$emit('sort-list', { sortBy: 'owner.displayName'})">
			{{ t('polls', 'Owner') }}
			<!-- <span :class="['sort-indicator', { 'hidden': sortBy !== 'owner.displayName'}, reverse ? 'icon-triangle-s' : 'icon-triangle-n']" /> -->
		</div>

		<div class="poll-item__wrapper">
			<div :class="['item__created', 'sortable', { 'sort': sortBy === 'created'}, { reverse }]"
				@click="$emit('sort-list', { sortBy: 'created'})">
				{{ t('polls', 'Created') }}
				<!-- <span :class="['sort-indicator', { 'hidden': sortBy !== 'created'}, reverse ? 'icon-triangle-s' : 'icon-triangle-n']" /> -->
			</div>

			<div :class="['item__expiry', 'sortable', { 'sort': sortBy === 'expire'}, { reverse }]"
				@click="$emit('sort-list', { sortBy: 'expire'})">
				{{ t('polls', 'Closing date') }}
				<!-- <span :class="['sort-indicator', { 'hidden': sortBy !== 'expire'}, reverse ? 'icon-triangle-s' : 'icon-triangle-n']" /> -->
			</div>
		</div>
	</div>

	<div v-else class="poll-item content">
		<TextIndPollIcon v-if="pollType === 'textIndPoll'" class="item__type" :title="pollTypeName" />
		<TextRankPollIcon v-else-if="pollType === 'textRankPoll'" class="item__type" :title="pollTypeName" />
		<DatePollIcon v-else class="item__type" :title="pollTypeName" />

		<div v-if="noLink" class="item__title" :class="{ closed: closed }">
			<div class="title">
				{{ pollConfiguration.title }}
			</div>

			<div class="description">
				{{ pollConfiguration.description ? pollConfiguration.description : t('polls', 'No description provided') }}
			</div>
		</div>

		<router-link v-else
			class="item__title"
			:to="{ name: 'vote', params: { id: poll.id }}"
			:class="{ closed: closed, active: (poll.id === $store.state.poll.id) }">
			<div class="title">
				{{ pollConfiguration.title }}
			</div>

			<div class="description">
				{{ pollConfiguration.description ? pollConfiguration.description : t('polls', 'No description provided') }}
			</div>
		</router-link>

		<slot name="actions" />

		<ArchivedPollIcon v-if="pollStatus.deleted" :title="accessType" class="item__access" />
		<OpenPollIcon v-else-if="pollConfiguration.access === 'open'" :title="accessType" class="item__access" />
		<PrivatePollIcon v-else :title="accessType" class="item__access" />

		<div class="item__owner">
			<UserItem :user="poll.owner" condensed />
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

<script>
import { mapState } from 'vuex'
import moment from '@nextcloud/moment'
import { BadgeDiv } from '../Base/index.js'
import TextIndPollIcon from 'vue-material-design-icons/FormatListBulletedSquare.vue'
import TextRankPollIcon from 'vue-material-design-icons/FormatListBulletedSquare.vue'
import DatePollIcon from 'vue-material-design-icons/CalendarBlank.vue'
import CreationIcon from 'vue-material-design-icons/ClockOutline.vue'
import ExpirationIcon from 'vue-material-design-icons/CalendarEnd.vue'
import PrivatePollIcon from 'vue-material-design-icons/Key.vue'
import OpenPollIcon from 'vue-material-design-icons/Earth.vue'
import ArchivedPollIcon from 'vue-material-design-icons/Archive.vue'

export default {
	name: 'PollItem',
	components: {
		BadgeDiv,
		TextRankPollIcon,
		TextIndPollIcon,
		DatePollIcon,
		CreationIcon,
		ExpirationIcon,
		PrivatePollIcon,
		OpenPollIcon,
		ArchivedPollIcon,
	},

	props: {
		header: {
			type: Boolean,
			default: false,
		},
		poll: {
			type: Object,
			default: undefined,
		},
		noLink: {
			type: Boolean,
			default: false,
		},
	},

	computed: {
		...mapState({
			sortBy: (state) => state.polls.sort.by,
			reverse: (state) => state.polls.sort.reverse,
		}),

		closeToClosing() {
			return (!this.closed && this.pollConfiguration.expire && moment.unix(this.pollConfiguration.expire).diff() < 86400000)
		},

		closed() {
			return this.pollStatus.expired
		},

		pollConfiguration() {
			return this.poll.configuration
		},

		pollStatus() {
			return this.poll.status
		},

		accessType() {
			if (this.pollConfiguration.deleted) {
				return t('polls', 'Archived')
			}

			if (this.pollConfiguration.access === 'open') {
				return t('polls', 'Openly accessible poll')
			}

			return t('polls', 'Private poll')
		},

		pollType() {
			return this.poll.type
		},

		pollTypeName() {
			if (this.pollType === 'textIndPoll') {
				return t('polls', 'Text poll')
			}
			else if (this.pollType === 'textRankPoll') {
				return t('polls', 'Text poll ranking')
			}
			return t('polls', 'Date poll')
		},

		timeExpirationRelative() {
			if (this.pollConfiguration.expire) {
				return moment.unix(this.pollConfiguration.expire).fromNow()
			}
			return t('polls', 'never')

		},

		expiryClass() {
			if (this.closed) {
				return 'error'
			}

			if (this.pollConfiguration.expire && this.closeToClosing) {
				return 'warning'
			}

			if (this.pollConfiguration.expire && !this.closed) {
				return 'success'
			}

			return 'success'
		},

		timeCreatedRelative() {
			return moment.unix(this.pollStatus.created).fromNow()
		},
	},
}
</script>

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

			[class^="item__"] {
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

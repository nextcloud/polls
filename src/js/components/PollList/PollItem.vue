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
	<div v-if="header" class="poll-item__header">
		<div class="item__title sortable" @click="$emit('sort-list', { sortBy: 'title'})">
			{{ t('polls', 'Title') }}
			<span :class="['sort-indicator', { 'hidden': sortBy !== 'title'}, reverse ? 'icon-triangle-s' : 'icon-triangle-n']" />
		</div>

		<div class="item__icon-spacer" />

		<div class="item__access sortable" @click="$emit('sort-list', { sortBy: 'access'})">
			{{ t('polls', 'Access') }}
			<span :class="['sort-indicator', { 'hidden': sortBy !== 'access'}, reverse ? 'icon-triangle-s' : 'icon-triangle-n']" />
		</div>

		<div class="item__owner sortable" @click="$emit('sort-list', { sortBy: 'owner.displayName'})">
			{{ t('polls', 'Owner') }}
			<span :class="['sort-indicator', { 'hidden': sortBy !== 'owner.displayName'}, reverse ? 'icon-triangle-s' : 'icon-triangle-n']" />
		</div>

		<div class="wrapper">
			<div class="item__created sortable" @click="$emit('sort-list', { sortBy: 'created'})">
				{{ t('polls', 'Created') }}
				<span :class="['sort-indicator', { 'hidden': sortBy !== 'created'}, reverse ? 'icon-triangle-s' : 'icon-triangle-n']" />
			</div>

			<div class="item__expiry sortable" @click="$emit('sort-list', { sortBy: 'expire'})">
				{{ t('polls', 'Closing date') }}
				<span :class="['sort-indicator', { 'hidden': sortBy !== 'expire'}, reverse ? 'icon-triangle-s' : 'icon-triangle-n']" />
			</div>
		</div>
	</div>

	<div v-else class="poll-item__item">
		<div :title="pollTypeName" class="item__icon-spacer">
			<TextPollIcon v-if="pollType === 'textPoll'" />
			<DatePollIcon v-else />
		</div>

		<div v-if="noLink" class="item__title" :class="{ closed: closed }">
			<div class="item__title__title">
				{{ poll.title }}
			</div>

			<div class="item__title__description">
				{{ poll.description ? poll.description : t('polls', 'No description provided') }}
			</div>
		</div>

		<router-link v-else
			class="item__title"
			:to="{ name: 'vote', params: { id: poll.id }}"
			:class="{ closed: closed, active: (poll.id === $store.state.poll.id) }">
			<div class="item__title__title">
				{{ poll.title }}
			</div>

			<div class="item__title__description">
				{{ poll.description ? poll.description : t('polls', 'No description provided') }}
			</div>
		</router-link>

		<slot name="actions" />
		<div :title="accessType" class="item__access">
			<ArchivedPollIcon v-if="poll.deleted" />
			<OpenPollIcon v-else-if="poll.access === 'open'" />
			<PrivatePollIcon v-else />
		</div>

		<div class="item__owner">
			<UserItem :user="poll.owner" condensed />
		</div>

		<div class="wrapper">
			<div class="item__created">
				<BadgeDiv>
					<template #icon>
						<CreationIcon />
					</template>
					{{ timeCreatedRelative }}
				</BadgeDiv>
			</div>
			<div class="item__expiry">
				<BadgeDiv :class="expiryClass">
					<template #icon>
						<ExpirationIcon />
					</template>
					{{ timeExpirationRelative }}
				</BadgeDiv>
			</div>
		</div>
	</div>
</template>

<script>
import { mapState } from 'vuex'
import moment from '@nextcloud/moment'
import { BadgeDiv } from '../Base/index.js'
import TextPollIcon from 'vue-material-design-icons/FormatListBulletedSquare.vue'
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
		TextPollIcon,
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

	emits: ['sort-list'],

	computed: {
		...mapState({
			sortBy: (state) => state.polls.sort.sortby,
			reverse: (state) => state.polls.sort.reverse,
		}),

		closeToClosing() {
			return (!this.closed && this.poll.expire && moment.unix(this.poll.expire).diff() < 86400000)
		},

		closed() {
			return this.poll.pollExpired
		},

		accessType() {
			if (this.poll.deleted) {
				return t('polls', 'Archived')
			}

			if (this.poll.access === 'open') {
				return t('polls', 'Openly accessible poll')
			}

			return t('polls', 'Private poll')
		},

		pollType() {
			return this.poll.type
		},

		pollTypeName() {
			if (this.pollType === 'textPoll') {
				return t('polls', 'Text poll')
			}
			return t('polls', 'Date poll')
		},

		timeExpirationRelative() {
			if (this.poll.expire) {
				return moment.unix(this.poll.expire).fromNow()
			}
			return t('polls', 'never')

		},

		expiryClass() {
			if (this.closed) {
				return 'error'
			}

			if (this.poll.expire && this.closeToClosing) {
				return 'warning'
			}

			if (this.poll.expire && !this.closed) {
				return 'success'
			}

			return 'success'
		},

		timeCreatedRelative() {
			return moment.unix(this.poll.created).fromNow()
		},
	},
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

	.poll-item__header {
		opacity: 0.7;
		flex: auto;
		height: 4em;
		align-items: center;
		padding-left: 52px;

		.sortable {
			cursor: pointer;
			&:hover {
				.sort-indicator.hidden {
					visibility: visible;
					display: block;
				}
			}
		}

		.item__title {
			flex-direction: row;
			justify-content: flex-start;
		}
	}

	.poll-item__item {
		&> .action-item {
			display: flex;
		}
		&.active {
			background-color: var(--color-primary-element-light);
		}
		&:hover {
			background-color: var(--color-background-hover);
		}
	}

	.item__icon-spacer {
		width: 44px;
		min-width: 44px;
	}

	.wrapper {
		display: flex;
		flex: 0 1 auto;
		flex-wrap: wrap;
	}

	.item__access,
	.item__owner {
		width: 78px;
		justify-content: center;
	}

	.item__created,
	.item__expiry {
		width: 145px;
		.badge {
			width: 100%;
		}
	}

	[class^='item__type'] {
		width: 44px;
		min-width: 16px;
		min-height: 16px;
	}

	[class^='item__access'] {
		width: 70px;
		min-width: 16px;
		min-height: 16px;
		justify-content: center;
	}
</style>

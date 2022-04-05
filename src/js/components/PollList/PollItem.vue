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
		<div v-tooltip.auto="pollTypeName" :class="['item__icon-spacer', pollTypeIcon]" />

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

		<div v-tooltip.auto="accessType" :class="['item__access', accessIcon]" />

		<div class="item__owner">
			<UserItem v-bind="poll.owner" condensed />
		</div>

		<div class="wrapper">
			<div class="item__created">
				<Badge :title="timeCreatedRelative"
					icon="icon-mask-md-creation" />
			</div>
			<div class="item__expiry">
				<Badge :title="timeExpirationRelative"
					icon="icon-mask-md-expiration"
					:class="expiryClass" />
			</div>
		</div>
	</div>
</template>

<script>
import { mapState } from 'vuex'
import moment from '@nextcloud/moment'
import Badge from '../Base/Badge'

export default {
	name: 'PollItem',
	components: {
		Badge,
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
				// TRANSLATORS Open poll means a poll which is open in the meaning of accessible for everyone
				return t('polls', 'Open poll')
			}

			return t('polls', 'Private poll')
		},

		pollTypeName() {
			if (this.poll.type === 'textPoll') {
				return t('polls', 'Text poll')
			}

			return t('polls', 'Date poll')
		},

		pollTypeIcon() {
			if (this.poll.type === 'textPoll') {
				return 'icon-mask-md-text-poll'
			}

			return 'icon-mask-md-date-poll'
		},

		accessIcon() {
			if (this.poll.deleted) {
				return 'icon-mask-md-archived-poll'
			}

			if (this.poll.access === 'open') {
				return 'icon-mask-md-open-poll'
			}

			return 'icon-mask-md-private-poll'
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
		background-repeat: no-repeat;
		background-position: center;
		min-width: 16px;
		min-height: 16px;
	}

	[class^='item__access'] {
		width: 70px;
		background-repeat: no-repeat;
		background-position: center;
		min-width: 16px;
		min-height: 16px;
		justify-content: center;
	}
</style>

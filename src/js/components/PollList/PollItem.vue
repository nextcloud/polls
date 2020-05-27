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
		<div class="item__title sortable" @click="$emit('sortList', {sort: 'title'})">
			{{ t('polls', 'Title') }}
			<span :class="['sort-indicator', { 'hidden': sort !== 'title'}, reverse ? 'icon-triangle-s' : 'icon-triangle-n']" />
		</div>

		<div class="item__icon-spacer" />

		<div class="item__access sortable" @click="$emit('sortList', {sort: 'access'})">
			{{ t('polls', 'Access') }}
			<span :class="['sort-indicator', { 'hidden': sort !== 'access'}, reverse ? 'icon-triangle-s' : 'icon-triangle-n']" />
		</div>

		<div class="item__owner sortable" @click="$emit('sortList', {sort: 'owner'})">
			{{ t('polls', 'Owner') }}
			<span :class="['sort-indicator', { 'hidden': sort !== 'owner'}, reverse ? 'icon-triangle-s' : 'icon-triangle-n']" />
		</div>

		<div class="wrapper">
			<div class="item__created sortable" @click="$emit('sortList', {sort: 'created'})">
				{{ t('polls', 'Created') }}
				<span :class="['sort-indicator', { 'hidden': sort !== 'created'}, reverse ? 'icon-triangle-s' : 'icon-triangle-n']" />
			</div>

			<div class="item__expiry sortable" @click="$emit('sortList', {sort: 'expire'})">
				{{ t('polls', 'Expires') }}
				<span :class="['sort-indicator', { 'hidden': sort !== 'expire'}, reverse ? 'icon-triangle-s' : 'icon-triangle-n']" />
			</div>
		</div>
	</div>

	<div v-else class="poll-item__item" :class="{ expired: isExpired, active: (poll.id === $store.state.poll.id) }">
		<div v-tooltip.auto="pollType" :class="'item__type--' + poll.type" />
		<router-link :to="{name: 'vote', params: {id: poll.id}}" class="item__title">
			<div class="item__title__title">
				{{ poll.title }}
			</div>
			<div class="item__title__description">
				{{ poll.description ? poll.description : t('polls', 'No description provided') }}
			</div>
		</router-link>

		<Actions :force-menu="true">
			<ActionButton icon="icon-add"
				:close-after-click="true"
				@click="clonePoll()">
				{{ t('polls', 'Clone poll') }}
			</ActionButton>

			<ActionButton v-if="poll.allowEdit && !poll.deleted"
				icon="icon-delete"
				:close-after-click="true"
				@click="switchDeleted()">
				{{ t('polls', 'Delete poll') }}
			</ActionButton>

			<ActionButton v-if="poll.allowEdit && poll.deleted"
				icon="icon-history"
				:close-after-click="true"
				@click="switchDeleted()">
				{{ t('polls', 'Restore poll') }}
			</ActionButton>

			<ActionButton v-if="poll.allowEdit && poll.deleted"
				icon="icon-delete"
				class="danger"
				:close-after-click="true"
				@click="deletePermanently()">
				{{ t('polls', 'Delete poll permanently') }}
			</ActionButton>
		</Actions>

		<div v-tooltip.auto="accessType" :class="'item__access--' + poll.access" @click="loadPoll()" />

		<div class="item__owner" @click="loadPoll()">
			<UserItem :user-id="poll.owner" :display-name="poll.ownerDisplayName" />
		</div>
		<div class="wrapper" @click="loadPoll()">
			<div class="item__created">
				{{ timeCreatedRelative }}
			</div>
			<div class="item__expiry">
				{{ timeExpirationRelative }}
			</div>
		</div>
	</div>
</template>

<script>
import { Actions, ActionButton } from '@nextcloud/vue'
import { emit } from '@nextcloud/event-bus'
import moment from '@nextcloud/moment'

export default {
	name: 'PollItem',

	components: {
		Actions,
		ActionButton,
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
		sort: {
			type: String,
			default: 'created',
		},
		reverse: {
			type: Boolean,
			default: true,
		},
	},

	data() {
		return {
			openedMenu: false,
		}
	},

	computed: {

		isExpired() {
			return (this.poll.expire > 0 && moment.unix(this.poll.expire).diff() < 0)
		},

		accessType() {
			if (this.poll.access === 'public') {
				return t('polls', 'Visible to other users')
			} else {
				return t('polls', 'Hidden to other users')
			}
		},

		pollType() {
			if (this.poll.type === 'textPoll') {
				return t('polls', 'Text poll')
			} else {
				return t('polls', 'Date poll')
			}
		},

		timeExpirationRelative() {
			if (this.poll.expire) {
				return moment.unix(this.poll.expire).fromNow()
			} else {
				return t('polls', 'never')
			}
		},
		timeCreatedRelative() {
			return moment.unix(this.poll.created).fromNow()
		},
	},

	methods: {
		loadPoll() {
			this.$store.dispatch({ type: 'loadPollMain', pollId: this.poll.id })
				.then((response) => {
					emit('toggle-sidebar', { open: true })
				})
				.catch((error) => {
					console.error(error)
					OC.Notification.showTemporary(t('polls', 'Error loading poll'), { type: 'error' })
				})

		},

		toggleMenu() {
			this.openedMenu = !this.openedMenu
		},

		hideMenu() {
			this.openedMenu = false
		},

		switchDeleted() {
			this.$store.dispatch('polls/switchDeleted', { pollId: this.poll.id })
				.then((response) => {
					emit('update-polls')
				})
			this.hideMenu()
		},

		deletePermanently() {
			this.$store.dispatch('polls/delete', { pollId: this.poll.id })
				.then((response) => {
					emit('update-polls')
				})
			this.hideMenu()
		},

		clonePoll() {
			this.$store.dispatch('polls/clone', { pollId: this.poll.id })
				.then((response) => {
					emit('update-polls')
				})
			this.hideMenu()
		},
	},
}
</script>

<style lang="scss" scoped>

[class^='item__'] {
	padding-right: 8px;
	display: flex;
	align-items: center;
	flex: 0 0 auto;
	overflow: hidden;
	white-space: nowrap;
	text-overflow: ellipsis;

}

.item__icon-spacer {
	width: 44px;
	min-width: 44px;
}
.item__title {
	display: flex;
	flex-direction: column;
	flex: 1 0 auto;
	align-items: stretch;
	width: 210px;
}

.item__title__description {
	opacity: 0.5;
}

.item__access {
	width: 80px;
}
.item__owner {
	width: 230px;
}

.wrapper {
	width: 240px;
	display: flex;
	flex: 0 1 auto;
	flex-wrap: wrap;
}

.item__created, .item__expiry {
	width: 110px;
}

.expired {
	.item__expiry {
		color: var(--color-error);
	}
}

[class^='poll-item__'] {
	display: flex;
	flex: 1;
	padding: 4px 8px;
	border-bottom: 1px solid var(--color-border-dark);
	background-color: var(--color-main-background)
}

.poll-item__header {
	opacity: 0.7;
	flex: auto;
	height: 4em;
	align-items: center;
	padding-left: 52px;
}

.sortable {
	cursor: pointer;
	&:hover {
		.sort-indicator.hidden {
			visibility: visible;
			display: block;
		}
	}
}

[class^='item__type'] {
	width: 44px;
	background-repeat: no-repeat;
	background-position: center;
	min-width: 16px;
	min-height: 16px;
}

.item__type--textPoll {
	background-image: var(--icon-toggle-filelist-000);
}
.item__type--datePoll {
	background-image: var(--icon-calendar-000);
}

[class^='item__access'] {
	width: 44px;
	background-repeat: no-repeat;
	background-position: center;
	min-width: 16px;
	min-height: 16px;
}

.item__access--public {
	background-image: var(--icon-timezone-000);
}

.item__access--hidden {
	background-image: var(--icon-password-000);
}

.poll-item__item {
	&.active {
		background-color: var(--color-primary-light);
	}
	&:hover {
		background-color: var(--color-background-hover);
	}
}

</style>

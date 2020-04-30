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
	<div v-if="header" class="poll-list__header">
		<div class="item__title" @click="$emit('sortList', {sort: 'title'})">
			{{ t('polls', 'Title') }}
			<span :class="['sort-indicator', { 'hidden': sort !== 'title'}, reverse ? 'icon-triangle-s' : 'icon-triangle-n']" />
		</div>

		<div class="icon-spacer" />

		<div class="item__access" @click="$emit('sortList', {sort: 'access'})">
			{{ t('polls', 'Access') }}
			<span :class="['sort-indicator', { 'hidden': sort !== 'access'}, reverse ? 'icon-triangle-s' : 'icon-triangle-n']" />
		</div>

		<div class="item__owner" @click="$emit('sortList', {sort: 'owner'})">
			{{ t('polls', 'Owner') }}
			<span :class="['sort-indicator', { 'hidden': sort !== 'owner'}, reverse ? 'icon-triangle-s' : 'icon-triangle-n']" />
		</div>

		<div class="item__created" @click="$emit('sortList', {sort: 'created'})">
			{{ t('polls', 'Created') }}
			<span :class="['sort-indicator', { 'hidden': sort !== 'created'}, reverse ? 'icon-triangle-s' : 'icon-triangle-n']" />
		</div>

		<div class="item__expiry" @click="$emit('sortList', {sort: 'expire'})">
			{{ t('polls', 'Expires') }}
			<span :class="['sort-indicator', { 'hidden': sort !== 'expire'}, reverse ? 'icon-triangle-s' : 'icon-triangle-n']" />
		</div>
	</div>

	<div v-else class="poll-list__item" :class="{ expired: isExpired, active: (poll.id === $store.state.poll.id) }">
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
			<user-div :user-id="poll.owner" :display-name="poll.ownerDisplayName" />
		</div>

		<div class="item__created" @click="loadPoll()">
			{{ moment.unix(poll.created).fromNow() }}
		</div>
		<div class="item__expiry" @click="loadPoll()">
			{{ timeSpanExpiration }}
		</div>
	</div>
</template>

<script>
import { Actions, ActionButton } from '@nextcloud/vue'

export default {
	name: 'PollListItem',

	components: {
		Actions,
		ActionButton
	},

	props: {
		header: {
			type: Boolean,
			default: false
		},
		poll: {
			type: Object,
			default: undefined
		},
		sort: {
			type: String,
			default: 'created'
		},
		reverse: {
			type: Boolean,
			default: true
		}
	},

	data() {
		return {
			openedMenu: false
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
				// TRANSLATORS This means that this is the type of the poll. Another type is a 'Date poll'.
				return t('polls', 'Text poll')
			} else {
				return t('polls', 'Date poll')
			}
		},

		timeSpanExpiration() {
			if (this.poll.expire) {
				return moment.unix(this.poll.expire).fromNow()
			} else {
				return t('polls', 'never')
			}
		}
	},

	methods: {
		loadPoll() {
			this.$store.dispatch({ type: 'loadPollMain', pollId: this.poll.id })
				.then((response) => {
					this.$store.dispatch({ type: 'loadPoll', pollId: this.poll.id })
					this.$root.$emit('toggle-sidebar', { open: true })
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
			this.$store.dispatch('switchDeleted', { pollId: this.poll.id })
				.then((response) => {
					this.$root.$emit('updatePolls')
				})
			this.hideMenu()
		},

		deletePermanently() {
			this.$store.dispatch('deletePermanently', { pollId: this.poll.id })
				.then((response) => {
					this.$root.$emit('updatePolls')
				})
			this.hideMenu()
		},

		clonePoll() {
			this.$store.dispatch('clonePoll', { pollId: this.poll.id })
				.then((response) => {
					this.$root.$emit('updatePolls')
				})
			this.hideMenu()
		}
	}
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

.icon-spacer {
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

.item__created, .item__expiry {
	width: 110px;
}

.expired {
	.item__expiry {
		color: var(--color-error);
	}
}

[class^='poll-list__'] {
	display: flex;
	flex: 1;
	border-bottom: 1px solid var(--color-border-dark);
	padding: 4px 8px;
}

.poll-list__header {
	opacity: 0.5;
	flex: auto;
	height: 4em;
	align-items: center;
	padding-left: 52px;

	&> div {
		cursor: pointer;
		display: flex;
		&:hover {
			.sort-indicator.hidden {
				visibility: visible;
				display: block;
			}
		}
	}
}

[class^='item__type']{
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

.poll-list__item {
	&.active {
		background-color: var(--color-primary-light);
	}
	&:hover {
		background-color: var(--color-background-hover);
	}
}

</style>

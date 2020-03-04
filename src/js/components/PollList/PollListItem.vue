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
	<div v-if="header" class="pollListItem header">
		<div class="title" @click="$emit('sortList', {sort: 'title'})">
			{{ t('polls', 'Title') }}
			<span :class="['sort-indicator', { 'hidden': sort !== 'title'}, reverse ? 'icon-triangle-s' : 'icon-triangle-n']" />
		</div>

		<div class="access" @click="$emit('sortList', {sort: 'access'})">
			{{ t('polls', 'Access') }}
			<span :class="['sort-indicator', { 'hidden': sort !== 'access'}, reverse ? 'icon-triangle-s' : 'icon-triangle-n']" />
		</div>

		<div class="owner" @click="$emit('sortList', {sort: 'owner'})">
			{{ t('polls', 'Owner') }}
			<span :class="['sort-indicator', { 'hidden': sort !== 'owner'}, reverse ? 'icon-triangle-s' : 'icon-triangle-n']" />
		</div>

		<div class="created" @click="$emit('sortList', {sort: 'created'})">
			{{ t('polls', 'Created') }}
			<span :class="['sort-indicator', { 'hidden': sort !== 'created'}, reverse ? 'icon-triangle-s' : 'icon-triangle-n']" />
		</div>

		<div class="expiry" @click="$emit('sortList', {sort: 'expire'})">
			{{ t('polls', 'Expires') }}
			<span :class="['sort-indicator', { 'hidden': sort !== 'expire'}, reverse ? 'icon-triangle-s' : 'icon-triangle-n']" />
		</div>
	</div>

	<div v-else class="pollListItem poll">
		<div v-tooltip.auto="pollType" class="thumbnail" :class="[poll.type, {expired : expired}]">
			{{ pollType }}
		</div>

		<router-link :to="{name: 'vote', params: {id: poll.id}}" class="title">
			<div class="name">
				{{ poll.title }}
			</div>
			<div class="description">
				{{ poll.description ? poll.description : t('polls', 'No description provided') }}
			</div>
		</router-link>

		<Actions>
			<ActionButton icon="icon-add" @click="clonePoll()">
				{{ t('polls', 'Clone poll') }}
			</ActionButton>

			<ActionButton v-if="poll.allowEdit && !poll.deleted" icon="icon-delete" @click="switchDeleted()">
				{{ (poll.isAdmin) ? t('polls', 'Delete poll as admin') : t('polls', 'Delete poll') }}
			</ActionButton>

			<ActionButton v-if="poll.allowEdit && poll.deleted" icon="icon-history" @click="switchDeleted()">
				{{ (poll.isAdmin) ? t('polls', 'Restore poll as admin') : t('polls', 'Restore poll') }}
			</ActionButton>

			<ActionButton v-if="poll.allowEdit && poll.deleted" icon="icon-delete" class="danger"
				@click="deletePermanently()">
				{{ (poll.isAdmin) ? t('polls', 'Delete poll permanently as admin') : t('polls', 'Delete poll permanently') }}
			</ActionButton>
		</Actions>

		<div v-tooltip.auto="accessType" class="thumbnail access" :class="poll.access">
			{{ accessType }}
		</div>

		<div class="owner">
			<user-div :user-id="poll.owner" :display-name="poll.ownerDisplayName" />
		</div>

		<div class="created ">
			{{ moment.unix(poll.created).fromNow() }}
		</div>
		<div class="expiry" :class="{ expired : poll.expired }">
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
			openedMenu: false,
			hostName: this.$route.query.page
		}
	},

	computed: {

		expired() {
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
				// TRANSLATORS This means that this is the type of the poll. Another type is a 'date poll'.
				return t('polls', 'Poll type')
			} else {
				return t('polls', 'Poll schedule')
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
		toggleMenu() {
			this.openedMenu = !this.openedMenu
		},

		refreshPolls() {
			this.$store.dispatch('loadPolls')
		},

		hideMenu() {
			this.openedMenu = false
		},

		switchDeleted() {
			this.$store.dispatch('switchDeleted', { pollId: this.poll.id })
				.then((response) => {
					this.refreshPolls()
				})
			this.hideMenu()
		},

		deletePermanently() {
			this.$store.dispatch('deletePermanently', { pollId: this.poll.id })
				.then((response) => {
					this.refreshPolls()
				})
			this.hideMenu()
		},

		clonePoll() {
			this.$store.dispatch('clonePoll', { pollId: this.poll.id })
				.then((response) => {
					this.refreshPolls()
				})
			this.hideMenu()
		}
	}
}
</script>

<style lang="scss" scoped>

.pollListItem {
}

.icon-more {
	right: 14px;
	opacity: 0.3;
	cursor: pointer;
	height: 44px;
	width: 44px;
}

.pollListItem {
	display: flex;
	flex: 1;
	border-bottom: 1px solid var(--color-border-dark);
	padding: 4px 8px;

	&> div {
		padding-right: 8px;
	}

	&.header {
		opacity: 0.5;
		flex: auto;
		height: 4em;
		align-items: center;
		padding-left: 44px;

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

	&.poll {
		.title {
			flex-direction: column;
			& > * {
				white-space: nowrap;
				overflow: hidden;
				text-overflow: ellipsis;
			}
		}
	}

	.title {
		display: flex;
		align-items: stretch;
		width: 210px;
		flex: 1 0 auto;

		.description {
			opacity: 0.5;
		}
	}

	.owner {
		flex: 0 0 auto;
		width: 230px;
		overflow: hidden;
		white-space: nowrap;
		text-overflow: ellipsis;
	}

	.actions {
		width: 44px;
		align-items: center;
		position: relative;
	}

	.created, .expiry {
		display: flex;
		flex-wrap: wrap;
		align-items: center;
		width: 110px;
		flex: 0 1 auto;
		overflow: hidden;
		white-space: nowrap;
		text-overflow: ellipsis;
	}
}

.thumbnail {
	flex: 0 0 auto;
	width: 44px;
	height: 44px;
	padding-right: 4px;
	font-size: 0;
	background-color: var(--color-text-light);
	&.datePoll {
		mask-image: var(--icon-calendar-000) no-repeat 50% 50%;
		-webkit-mask: var(--icon-calendar-000) no-repeat 50% 50%;
		mask-size: 16px;
	}
	&.textPoll {
		mask-image: var(--icon-organization-000) no-repeat 50% 50%;
		-webkit-mask: var(--icon-organization-000) no-repeat 50% 50%;
		mask-size: 16px;
	}
	&.expired {
		background-color: var(--color-background-darker);
	}
	&.access {
		display: inherit;
		&.hidden {
			mask-image: var(--icon-password-000) no-repeat 50% 50%;
			-webkit-mask: var(--icon-password-000) no-repeat 50% 50%;
			mask-size: 16px;
		}
		&.public {
			mask-image: var(--icon-link-000) no-repeat 50% 50%;
			-webkit-mask: var(--icon-link-000) no-repeat 50% 50%;
			mask-size: 16px;
		}
	}
}

</style>

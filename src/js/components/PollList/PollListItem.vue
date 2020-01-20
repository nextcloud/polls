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
		<div class="title" @click="$emit('sortList', {sort: 'title', reverse: !reverse})">
			{{ t('polls', 'Title') }}
		</div>

		<div class="access" @click="$emit('sortList', {sort: 'access', reverse: !reverse})">
			{{ t('polls', 'Access') }}
		</div>

		<div class="owner" @click="$emit('sortList', {sort: 'owner', reverse: !reverse})">
			{{ t('polls', 'Owner') }}
		</div>

		<div class="dates">
			<div class="created" @click="$emit('sortList', {sort: 'created', reverse: !reverse})">
				{{ t('polls', 'Created') }}
			</div>
			<div class="expiry" @click="$emit('sortList', {sort: 'expire', reverse: !reverse})">
				{{ t('polls', 'Expires') }}
			</div>
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
				{{ poll.description }}
			</div>
		</router-link>

		<div class="actions">
			<div class="toggleUserActions">
				<div v-click-outside="hideMenu" class="icon-more" @click="toggleMenu" />
				<div class="popovermenu" :class="{ 'open': openedMenu }">
					<PopoverMenu :menu="menuItems" />
				</div>
			</div>
		</div>

		<div v-tooltip.auto="accessType" class="thumbnail access" :class="poll.access">
			{{ accessType }}
		</div>

		<div class="owner">
			<user-div :user-id="poll.owner" :display-name="poll.ownerDisplayName" />
		</div>

		<div class="dates">
			<div class="created ">
				{{ moment.unix(poll.created).fromNow() }}
			</div>
			<div class="expiry" :class="{ expired : poll.expired }">
				{{ timeSpanExpiration }}
			</div>
		</div>
	</div>
</template>

<script>
import { PopoverMenu } from '@nextcloud/vue'

export default {
	name: 'PollListItem',

	components: {
		PopoverMenu
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
		},

		menuItems() {
			const items = [
				{
					key: 'clonePoll',
					icon: 'icon-add',
					text: t('polls', 'Clone poll'),
					action: this.clonePoll
				}
			]

			if (this.poll.owner === OC.getCurrentUser().uid && !this.poll.deleted) {
				items.push({
					key: 'switchDeleted',
					icon: 'icon-delete',
					text: t('polls', 'Delete poll'),
					action: this.switchDeleted
				})
			}

			if (this.poll.deleted) {
				items.push({
					key: 'switchDeleted',
					icon: 'icon-history',
					text: t('polls', 'Restore poll'),
					action: this.switchDeleted
				})
			}

			return items
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
	display: flex;
	flex: 1;
	padding-left: 8px;
	&.header {
		opacity: 0.5;
		flex: auto;
		height: 4em;
		align-items: center;
		margin-left: 44px;
	}
	&> div {
		padding-right: 8px;
	}
}

.icon-more {
	right: 14px;
	opacity: 0.3;
	cursor: pointer;
	height: 44px;
	width: 44px;
}

.title {
	display: flex;
	flex-direction: column;
	align-items: stretch;
	width: 210px;
	flex: 1 1 auto;
	.name,
	.description {
		overflow: hidden;
		white-space: nowrap;
		text-overflow: ellipsis;
	}

	.description {
		opacity: 0.5;
	}
}

.owner {
	flex: 0 0 auto;
	width: 130px;
	overflow: hidden;
	white-space: nowrap;
	text-overflow: ellipsis;
}

.actions {
	width: 44px;
	align-items: center;
	position: relative;
}

.dates {
	display: flex;
	flex-wrap: wrap;
	align-items: center;

	.created, .expiry {
		width: 100px;
		flex: 1 1;
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

@media all and (max-width: (740px)) {
	.dates {
		flex-direction: column;
	}
}

@media all and (max-width: (620px)) {
	.owner {
		display: none;
	}
}

@media all and (max-width: (490px)) {
	.dates {
		display: none;
	}
}

@media all and (max-width: (380px)) {
	.thumbnail.access, .access {
		width: 140px;
		display: none;
	}
}

</style>

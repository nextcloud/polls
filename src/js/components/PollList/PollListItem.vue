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
		<div class="title">
			{{ t('polls', 'Title') }}
		</div>

		<div class="access">
			{{ t('polls', 'Access') }}
		</div>

		<div class="owner">
			{{ t('polls', 'Owner') }}
		</div>

		<div class="dates">
			<div class="created">
				{{ t('polls', 'Created') }}
			</div>
			<div class="expiry">
				{{ t('polls', 'Expires') }}
			</div>
		</div>
	</div>

	<div v-else class="pollListItem poll">
		<div v-tooltip.auto="pollType" class="thumbnail" :class="[poll.type, {expired : expired}]">
			{{ pollType }}
		</div>

		<!-- <div v-if="votedBycurrentUser" class="symbol icon-voted" /> -->

		<router-link :to="{name: 'vote', params: {id: poll.id}}" class="title">
			<div class="name">
				{{ poll.title }}
			</div>
			<div class="description">
				{{ poll.description }}
			</div>
		</router-link>

		<!-- <div v-if="countComments" v-tooltip.auto="countCommentsHint" class="app-navigation-entry-utils-counter highlighted">
			<span>{{ countComments }}</span>
		</div> -->

		<div class="actions">
			<div class="toggleUserActions">
				<div v-click-outside="hideMenu" class="icon-more" @click="toggleMenu" />
				<div class="popovermenu" :class="{ 'open': openedMenu }">
					<popover-menu :menu="menuItems" />
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
export default {
	name: 'PollListItem',

	props: {
		header: {
			type: Boolean,
			default: false
		},
		poll: {
			type: Object,
			default: undefined
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
			let items = [
				{
					key: 'clonePoll',
					icon: 'icon-confirm',
					text: t('polls', 'Clone poll'),
					action: this.clonePoll
				}
			]

			if (this.poll.owner === OC.getCurrentUser().uid) {
				items.push({
					key: 'deletePoll',
					icon: 'icon-delete',
					text: t('polls', 'Delete poll'),
					action: this.deletePoll
				})
			} else if (OC.isUserAdmin()) {
				items.push({
					key: 'deletePoll',
					icon: 'icon-delete',
					text: t('polls', 'Delete poll as admin'),
					action: this.deletePoll
				})
			}

			return items
		}
	},

	methods: {
		toggleMenu() {
			this.openedMenu = !this.openedMenu
		},

		hideMenu() {
			this.openedMenu = false
		},

		deletePoll() {
			this.$emit('deletePoll')
			this.hideMenu()
		},

		clonePoll() {
			this.$emit('clonePoll')
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

.thumbnail {
	flex: 0 0 auto;
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

.thumbnail.access, .owner {
	flex: 0 0 auto;
}

.thumbnail.access {
	width: 75px;
}

.owner {
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
			&.select {
				mask-image: var(--icon-share-000) no-repeat 50% 50%;
				-webkit-mask: var(--icon-share-000) no-repeat 50% 50%;
				mask-size: 16px;
			}
			&.registered {
				mask-image: var(--icon-group-000) no-repeat 50% 50%;
				-webkit-mask: var(--icon-group-000) no-repeat 50% 50%;
				mask-size: 16px;
			}
		}
	}

	.icon-voted {
		background-image: var(--icon-checkmark-fff);
	}

	.comment-badge {
		position: absolute;
		top: 0;
		width: 26px;
		line-height: 26px;
		text-align: center;
		font-size: 0.7rem;
		color: white;
		background-image: var(--icon-comment-49bc49);
		background-repeat: no-repeat;
		background-size: 26px;
		z-index: 1;
	}

	.app-navigation-entry-utils-counter {
		padding-right: 0 !important;
		overflow: hidden;
		text-align: right;
		font-size: 9pt;
		line-height: 44px;
		padding: 0 12px;
		// min-width: 25px;
		&.highlighted {
			padding: 0;
			text-align: center;
			span {
				padding: 2px 5px;
				border-radius: 10px;
				background-color: var(--color-primary);
				color: var(--color-primary-text);
			}
		}
	}

	.symbol.icon-voted {
		position: absolute;
		left: 11px;
		top: 16px;
		background-size: 0;
		min-width: 8px;
		min-height: 8px;
		background-color: var(--color-success);
		border-radius: 50%;
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

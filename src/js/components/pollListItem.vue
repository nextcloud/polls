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
	<div>
		<div class="wrapper group-master">
			<div class="wrapper group-1">
				<div class="thumbnail has-tooltip" :class="[poll.event.type, {expired : poll.event.expired}] " :title="pollType">
					{{ pollType }}
				</div>
				<div v-if="votedBycurrentUser" class="symbol icon-voted" />
				<a :href="voteUrl" class="wrapper group-1-1">
					<div class="flex-column name">
						{{ poll.event.title }}
					</div>
					<div class="flex-column description">
						{{ poll.event.description }}
					</div>
				</a>
				<div v-if="countComments" class="app-navigation-entry-utils-counter highlighted">
					<span>{{ countComments }}</span>
				</div>
				<div class="actions">
					<div class="toggleUserActions">
						<div v-click-outside="hideMenu" class="icon-more" @click="toggleMenu" />
						<div class="popovermenu" :class="{ 'open': openedMenu }">
							<popover-menu :menu="menuItems" />
						</div>
					</div>
				</div>
			</div>
			<div class="wrapper group-2">
				<div class="wrapper group-2-1">
					<div class="flex-column thumbnail access has-tooltip" :class="this.poll.event.access" :title="accessType">
						{{ accessType }}
					</div>
				</div>
				<div class="flex-column owner">
					<user-div :user-id="poll.event.owner" :display-name="poll.event.ownerDisplayName" />
				</div>
				<div class="wrapper group-2-2">
					<div class="flex-column created ">
						{{ timeSpanCreated }}
					</div>
					<div class="flex-column expiry" :class="{ expired : poll.event.expired }">
						{{ timeSpanExpiration }}
					</div>
				</div>
			</div>
		</div>
	</div>
</template>

<script>
import moment from 'moment'

export default {
	props: {
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
		accessType() {
			if (this.poll.event.access === 'public') {
				return t('polls', 'Public access')
			} else if (this.poll.event.access === 'select') {
				return t('polls', 'Only shared')
			} else if (this.poll.event.access === 'registered') {
				return t('polls', 'Registered users only')
			} else if (this.poll.event.access === 'hidden') {
				return t('polls', 'Hidden poll')
			} else {
				return ''
			}
		},

		pollType() {
			if (this.poll.event.type === 'textPoll') {
				return t('polls', 'Text poll')
			} else if (this.poll.event.type === 'datePoll') {
				return t('polls', 'Date poll')
			}
		},

		timeSpanCreated() {
			return moment(this.poll.event.created).fromNow()
		},

		timeSpanExpiration() {
			if (this.poll.event.expiration) {
				return moment(this.poll.event.expirationDate).fromNow()
			} else {
				return t('polls', 'never')
			}
		},
		participants() {
			return this.poll.votes.map(item => item.userId)
				.filter((value, index, self) => self.indexOf(value) === index)
		},
		countvotes() {
			return this.participants.length
		},
		countComments() {
			if (this.poll.comments.length > 999) {
				return '999+'
			}
			return this.poll.comments.length
		},
		countShares() {
			return this.poll.shares.length
		},
		votedBycurrentUser() {
			return this.participants.indexOf(OC.getCurrentUser().uid) > -1
		},
		voteUrl() {
			return OC.generateUrl('apps/polls/poll/') + this.poll.event.hash

		},
		menuItems() {

			let items = [{
				icon: 'icon-clippy',
				text: t('polls', 'Copy Link'),
				action: this.copyLink
			},
			{
				icon: 'icon-confirm',
				text: t('polls', 'Clone poll'),
				action: this.clonePoll
			}]

			if (this.poll.event.owner === OC.getCurrentUser().uid) {

				items.push(
					{
						icon: 'icon-rename',
						text: t('polls', 'Edit poll'),
						action: this.editPoll
					})
				items.push({
					icon: 'icon-delete',
					text: t('polls', 'Delete poll'),
					action: this.deletePoll
				})

			} else if (OC.isUserAdmin()) {

				items.push(
					{
						icon: 'icon-rename',
						text: t('polls', 'Edit poll as admin'),
						action: this.editPoll
					})
				items.push({
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

		copyLink() {
			this.$copyText(window.location.origin + this.voteUrl).then(
				function(e) {
					OC.Notification.showTemporary(t('polls', 'Link copied to clipboard'))
				},
				function(e) {
					OC.Notification.showTemporary(t('polls', 'Error, while copying link to clipboard'))
				}
			)
			this.hideMenu()
		},

		deletePoll() {
			// Todo: Remove Item self and update transition group in parent.
			// Event must be triggert from parent

			this.$emit('deletePoll')
			this.hideMenu()
		},

		editPoll() {
			this.$router.push({
				name: 'edit',
				params: {
					hash: this.poll.event.id
				}
			})
		},

		clonePoll() {
			this.$router.push({
				name: 'clone',
				params: {
					hash: this.poll.event.id
				}
			})
		}

	}
}
</script>
<style lang="scss">

.thumbnail {
    width: 44px;
    height: 44px;
    padding-right: 4px;
	font-size: 0;
    background-color: var(--color-text-light);
    &.datePoll {
        mask-image: var(--icon-calendar-000) no-repeat 50% 50%;
        -webkit-mask: var(--icon-calendar-000) no-repeat 50% 50%;
        mask-size: 32px;
    }
    &.textPoll {
        mask-image: var(--icon-organization-000) no-repeat 50% 50%;
        -webkit-mask: var(--icon-organization-000) no-repeat 50% 50%;
        mask-size: 32px;
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
	left: 5px;
	top: 8px;
	background-size: 1em;
	min-width: 1.2em;
	min-height: 1.2em;
	background-color: var(--color-success);
	border-radius: 50%;
}

</style>

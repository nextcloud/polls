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
				<div class="thumbnail" :class="[poll.event.type, {expired : poll.event.expired}] " />
				<a :href="voteUrl" class="wrapper group-1-1">
					<div class="flex-column name">
						{{ poll.event.title }}
					</div>
					<div class="flex-column description">
						{{ poll.event.description }}
					</div>
				</a>
				<div class="flex-column actions">
					<div class="toggleUserActions">
						<div class="icon-more" v-click-outside="hideMenu" @click="toggleMenu"></div>
						<div class="popovermenu" :class="{ 'open': openedMenu }">
							<popover-menu :menu="menuItems" />
						</div>
					</div>
				</div>
			</div>
			<div class="wrapper group-2">
				<div class="flex-column owner">
					<user-div :user-id="poll.event.owner" :display-name="poll.event.ownerDisplayName" />
				</div>
				<div class="wrapper group-2-1">
					<div class="flex-column access">
						{{ accessType }}
					</div>
					<div class="flex-column created ">
						{{ timeSpanCreated }}
					</div>
				</div>
				<div class="wrapper group-2-2">
					<div class="flex-column expiry" :class="{ expired : poll.event.expired }">
						{{ timeSpanExpiration }}
					</div>
					<div class="flex-column participants">
						<div class="symbol alt-tooltip partic_voted icon-voted" />
						<div class="symbol alt-tooltip partic_commented icon-comment-yes" />
					</div>
				</div>
			</div>
		</div>
	</div>
</template>

<script>

export default {
	data() {
		return {
			openedMenu: false,
			hostName: this.$route.query.page
		}

	},

	props: {
		poll: {
			type: Object,
			default: undefined
		}
	},

	methods: {
		toggleMenu() {
			this.openedMenu = !this.openedMenu;
		},

		hideMenu() {
			this.openedMenu = false;
		},

		copyLink() {
			this.$copyText(window.location.origin + this.voteUrl).then(
				function (e) {
					OC.Notification.showTemporary(t('polls', 'Link copied to clipboard'))
				},
				function (e) {
					OC.Notification.showTemporary(t('polls', 'Error, while copying link to clipboard'))
				}
			)
			this.hideMenu()
		},

		deletePoll() {
			// Todo: Remove Item self and update transition group in parent.
			// Event must be triggert from parent
		},

		editPoll() {
			this.$router.push(
				{
					name: 'edit',
					params: {hash: this.poll.event.hash}
				}
			)
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

		timeSpanCreated() {
			return moment(this.poll.event.created).fromNow()
		},

		timeSpanExpiration() {
			if (this.poll.event.expiration) {
				return moment(this.poll.event.expirationDate).fromNow()
			} else {
				return t('polls','never')
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
			return this.poll.comments.length
		},
		countShares() {
			return this.poll.shares.length
		},
		voteUrl() {
			return 	OC.generateUrl('apps/polls/poll/') + this.poll.event.hash

		},
		menuItems() {
			return [{
				icon: 'icon-clippy',
				text: t('polls', 'Copy Link'),
				action: this.copyLink
			},
			{
				icon: 'icon-delete',
				text: t('polls', 'Delete poll'),
				action: this.deletePoll
			},
			{
				icon: 'icon-rename',
				text: t('polls', 'Edit poll'),
				action: this.editPoll
			}]
		}
	}
}

</script>
<style lang="scss">
.thumbnail {
	width: 44px;
	height: 44px;
	padding-right: 4px;
	background-color: var(--color-primary-element);
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
		background-color: var(--color-error);
	}
}

</style>

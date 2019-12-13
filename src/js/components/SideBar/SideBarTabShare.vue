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
		<h3>{{ t('polls','Invitations') }}</h3>
		<TransitionGroup :css="false" tag="ul" class="shared-list">
			<li v-for="(share) in invitationShares" :key="share.id">
				<UserDiv
					:user-id="resolveShareUser(share)"
					:display-name="shareDisplayName(share)"
					:type="share.type"
					:icon="true" />
				<div class="options">
					<a class="icon icon-clippy" @click="copyLink( { url: OC.generateUrl('apps/polls/s/') + share.token } )" />
					<a class="icon icon-delete svg delete-poll" @click="removeShare(share)" />
				</div>
			</li>
		</TransitionGroup>

		<Multiselect id="ajax"
			:options="users"
			:multiple="false"
			:user-select="true"
			:tag-width="80"
			:clear-on-select="false"
			:preserve-search="true"
			:options-limit="30"
			:loading="isLoading"
			:internal-search="false"
			:searchable="true"
			:preselect-first="true"
			:placeholder="placeholder"
			label="displayName"
			track-by="user"
			@select="addShare"
			@search-change="loadUsersAsync">
			<template slot="selection" slot-scope="{ values, search, isOpen }">
				<span v-if="values.length &amp;&amp; !isOpen" class="multiselect__single">
					{{ values.length }} users selected
				</span>
			</template>
		</Multiselect>

		<h3>{{ t('polls','Public shares') }}</h3>
		<TransitionGroup :css="false" tag="ul" class="shared-list">
			<li v-for="(share) in publicShares" :key="share.id">
				<div class="user-row user">
					<div class="avatar icon-public" />
					<div class="user-name">
						{{ t('polls', 'Share Link') }}
					</div>
				</div>
				<div class="options">
					<a class="icon icon-clippy" @click="copyLink( { url: OC.generateUrl('apps/polls/s/') + share.token } )" />
					<a class="icon icon-delete" @click="removeShare(share)" />
				</div>
			</li>
		</TransitionGroup>
		<div class="user-row user" @click="addShare({type: 'public', user: ''})">
			<div class="avatar icon-add" />
			<div class="user-name">
				{{ t('polls', 'Add a public link') }}
			</div>
		</div>
	</div>
</template>

<script>
import { Multiselect } from '@nextcloud/vue'
import { mapGetters } from 'vuex'

export default {
	name: 'SideBarTabShare',

	components: {
		Multiselect
	},

	data() {
		return {
			users: [],
			invitations: [],
			invitation: {},
			isLoading: false,
			siteUsersListOptions: {
				getUsers: true,
				getGroups: true,
				getContacts: true,
				query: ''
			}
		}
	},

	computed: {
		...mapGetters([
			'countShares',
			'sortedShares',
			'invitationShares',
			'publicShares'
		])

	},

	methods: {
		loadUsersAsync(query) {
			this.isLoading = false
			this.siteUsersListOptions.query = query
			this.$http.post(OC.generateUrl('apps/polls/get/siteusers'), this.siteUsersListOptions)
				.then((response) => {
					this.users = response.data.siteusers
					this.isLoading = false
				}, (error) => {
					console.error(error.response)
				})
		},

		copyLink(payload) {
			this.$copyText(window.location.origin + payload.url).then(
				function(e) {
					OC.Notification.showTemporary(t('polls', 'Link copied to clipboard'), { type: 'success' })
				},
				function(e) {
					OC.Notification.showTemporary(t('polls', 'Error while copying link to clipboard'), { type: 'error' })
				}
			)
		},

		resolveShareUser(share) {

			if (share.userId !== '' && share.userId !== null) {
				return share.userId
			} else if (share.type === 'mail') {
				return share.userEmail
			} else {
				return t('polls', 'Unknown user')
			}

		},

		shareDisplayName(share) {
			let displayName = ''

			if (share.type === 'user' || share.type === 'contact' || share.type === 'external') {
				displayName = share.userId
				if (share.userEmail) {
					displayName = displayName + ' (' + share.userEmail + ')'
				}
			} else if (share.type === 'mail') {
				displayName = share.userEmail
				if (share.userId) {
					displayName = share.userId + ' (' + displayName + ')'
				}
			} else if (share.type === 'group') {
				displayName = share.userId + ' (' + t('polls', 'Group') + ')'
			} else if (share.type === 'public') {
				displayName = t('polls', 'Public share')
			} else {
				displayName = t('polls', 'Unknown user')
			}
			return displayName
		},

		removeShare(share) {
			this.$store.dispatch('removeShareAsync', { share: share })
		},

		addShare(payload) {
			this.$store.dispatch('writeSharePromise', {
				'share': {
					'type': payload.type,
					'userId': payload.user,
					'pollId': '0',
					'userEmail': payload.emailAddress,
					'token': ''
				}
			})
				// .then(response => {
			// OC.Notification.showTemporary(t('polls', 'You added %n.', 1, payload.user), { type: 'success' })
				// })
				.catch(error => {
					console.error('Error while adding share comment - Error: ', error)
					OC.Notification.showTemporary(t('polls', 'Error while adding share'), { type: 'error' })
				})
		}
	}
}
</script>

<style lang="scss">
	.shared-list {
		display: flex;
		flex-wrap: wrap;
		flex-direction: column;
		justify-content: flex-start;
		padding-top: 8px;

		> li {
			display: flex;
			align-items: stretch;
			margin: 4px 0;
		}
	}

	.options {
		display: flex;

		.icon:not(.hidden) {
			padding: 14px;
			height: 44px;
			width: 44px;
			opacity: .5;
			display: block;
			cursor: pointer;
		}
	}

	.multiselect {
		width: 100% !important;
		max-width: 100% !important;
	}
</style>

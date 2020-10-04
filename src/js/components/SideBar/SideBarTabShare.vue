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
		<ConfigBox v-if="!acl.isOwner" :title="t('polls', 'As an admin you may edit this poll')" icon-class="icon-checkmark" />

		<ConfigBox :title="t('polls', 'Shares')" icon-class="icon-share">
			<TransitionGroup :css="false" tag="div" class="shared-list">
				<UserItem v-for="(share) in invitationShares"
					:key="share.id" v-bind="share"
					:icon="true">
					<Actions>
						<ActionButton
							v-if="share.userEmail || share.type === 'group'"
							icon="icon-confirm"
							@click="sendInvitation(share)">
							{{ share.invitationSent ? t('polls', 'Resend invitation mail') : t('polls', 'Send invitation mail') }}
						</ActionButton>
						<ActionButton icon="icon-clippy" @click="copyLink( { url: shareUrl(share) })">
							{{ t('polls', 'Copy link to clipboard') }}
						</ActionButton>
					</Actions>
					<Actions>
						<ActionButton icon="icon-delete" @click="removeShare(share)">
							{{ t('polls', 'Remove share') }}
						</ActionButton>
					</Actions>
				</UserItem>
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
				track-by="userId"
				@select="addShare"
				@search-change="loadUsersAsync">
				<template slot="selection" slot-scope="{ values, search, isOpen }">
					<span v-if="values.length &amp;&amp; !isOpen" class="multiselect__single">
						{{ values.length }} users selected
					</span>
				</template>
			</Multiselect>
		</ConfigBox>

		<ConfigBox :title="t('polls', 'Public shares')" icon-class="icon-public">
			<TransitionGroup :css="false" tag="ul" class="shared-list">
				<li v-for="(share) in publicShares" :key="share.id">
					<div class="share-item">
						<Avatar icon-class="icon-public" :is-no-user="true" />
						<!-- <div class="avatar icon-public" /> -->
						<div class="share-item__description">
							{{ t('polls', 'Public link ({token})', {token: share.token }) }}
						</div>
					</div>
					<Actions>
						<ActionButton icon="icon-clippy" @click="copyLink( { url: shareUrl(share) })">
							{{ t('polls', 'Copy link to clipboard') }}
						</ActionButton>
					</Actions>
					<Actions>
						<ActionButton icon="icon-delete" @click="removeShare(share)">
							{{ t('polls', 'Remove share') }}
						</ActionButton>
					</Actions>
				</li>
			</TransitionGroup>

			<ButtonDiv :title="t('polls', 'Add a public link')" icon="icon-add" @click="addShare({type: 'public', userId: '', emailAddress: ''})" />
		</ConfigBox>

		<ConfigBox v-if="unsentInvitations.length" :title="t('polls', 'Unsent invitations')" icon-class="icon-polls-mail">
			<TransitionGroup :css="false" tag="div" class="shared-list">
				<UserItem v-for="(share) in unsentInvitations"
					:key="share.id" v-bind="share"
					:icon="true">
					<Actions>
						<ActionButton
							v-if="share.userEmail || share.type === 'group'"
							icon="icon-confirm"
							@click="sendInvitation(share)">
							{{ t('polls', 'Send invitation mail') }}
						</ActionButton>
						<ActionButton
							v-if="share.type === 'contactGroup' || share.type === 'circle'"
							icon="icon-toggle-filelist"
							@click="resolveGroup(share)">
							{{ t('polls', 'Resolve into individual invitations') }}
						</ActionButton>
					</Actions>
					<Actions>
						<ActionButton icon="icon-delete" @click="removeShare(share)">
							{{ t('polls', 'Remove invitation') }}
						</ActionButton>
					</Actions>
				</UserItem>
			</TransitionGroup>
		</ConfigBox>
	</div>
</template>

<script>
import axios from '@nextcloud/axios'
import { Actions, ActionButton, Avatar, Multiselect } from '@nextcloud/vue'
import { mapState, mapGetters } from 'vuex'
import { generateUrl } from '@nextcloud/router'
import ConfigBox from '../Base/ConfigBox'
import ButtonDiv from '../Base/ButtonDiv'
import { showSuccess, showError } from '@nextcloud/dialogs'

export default {
	name: 'SideBarTabShare',

	components: {
		Actions,
		ActionButton,
		Avatar,
		ButtonDiv,
		ConfigBox,
		Multiselect,
	},

	data() {
		return {
			users: [],
			isLoading: false,
			placeholder: t('polls', 'Enter a name to start the search'),
			siteUsersListOptions: {
				getUsers: true,
				getGroups: true,
				getContacts: true,
				getMail: true,
				query: '',
			},
		}
	},

	computed: {
		...mapState({
			acl: state => state.poll.acl,
		}),

		...mapGetters({
			invitationShares: 'poll/shares/invitation',
			unsentInvitations: 'poll/shares/unsentInvitations',
			publicShares: 'poll/shares/public',
		}),
	},

	methods: {
		resolveGroup(share) {
			this.$store.dispatch('poll/shares/resolveGroup', { share: share })
				.then((response) => {
					this.$store.dispatch('poll/shares/delete', { share: share })
				})
		},

		sendInvitation(share) {
			this.$store.dispatch('poll/shares/sendInvitation', { share: share })
				.then((response) => {
					response.data.sentResult.sentMails.forEach((item) => {
						showSuccess(t('polls', 'Invitation sent to {name}', { name: item.displayName }))
					})
					response.data.sentResult.abortedMails.forEach((item) => {
						console.error('Mail could not be sent!', { recipient: item })
						showError(t('polls', 'Error sending invitation to {name}', { name: item.dispalyName }))
					})
				})
		},

		loadUsersAsync(query) {
			this.isLoading = false
			this.siteUsersListOptions.query = query
			axios.post(generateUrl('apps/polls/siteusers/get'), this.siteUsersListOptions)
				.then((response) => {
					this.users = response.data.siteusers
					this.isLoading = false
				})
				.catch((error) => {
					console.error(error.response)
				})
		},

		copyLink(payload) {
			this
				.$copyText(window.location.origin + payload.url)
				.then(() => {
					showSuccess(t('polls', 'Link copied to clipboard'))
				})
				.catch(() => {
					showError(t('polls', 'Error while copying link to clipboard'))
				})
		},

		shareUrl(share) {
			return generateUrl('apps/polls/s/') + share.token
		},

		removeShare(share) {
			this.$store.dispatch('poll/shares/delete', { share: share })
		},

		addShare(payload) {
			this.$store
				.dispatch('poll/shares/add', {
					type: payload.type,
					id: payload.id,
					userEmail: payload.emailAddress,
				})
				.catch(error => {
					console.error('Error while adding share - Error: ', error)
					showError(t('polls', 'Error while adding share'))
				})
		},
	},
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

	.share-item {
		display: flex;
		flex: 1;
		align-items: center;
		max-width: 100%;

		//dirty hack: AvatarDiv does not work properly with iconClass
		.avatardiv {
			&.avatardiv--unknown {
				background-color: transparent;
			}

			.avatar-class-icon {
				// background-color: var(--color-primary-element-light);
				min-height: 32px;
				min-width: 32px;
			}
		}
	}

	.share-item__description {
		flex: 1;
		min-width: 50px;
		color: var(--color-text-maxcontrast);
		padding-left: 8px;
		white-space: nowrap;
		overflow: hidden;
		text-overflow: ellipsis;
	}

	.multiselect {
		width: 100% !important;
		max-width: 100% !important;
	}
</style>

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
	<ConfigBox :title="t('polls', 'Shares')">
		<template #icon>
			<ShareIcon />
		</template>

		<UserSearch class="add-share" />
		<ShareItemAllUsers v-if="allowAllAccess" />
		<SharePublicAdd v-if="allowPublicShares" />

		<div v-if="invitationShares.length" class="shares-list shared">
			<TransitionGroup :css="false" tag="div">
				<UserItem v-for="(share) in invitationShares"
					:key="share.id"
					v-bind="share"
					show-email
					:icon="true">
					<template #status>
						<div v-if="hasVoted(share.userId)">
							<VotedIcon class="vote-status voted" :title="t('polls', 'Has voted')" />
						</div>
						<div v-else-if="['public', 'group'].includes(share.type)">
							<div class="vote-status empty" />
						</div>
						<div v-else>
							<UnvotedIcon class="vote-status unvoted" :title="t('polls', 'Has not voted')" />
						</div>
					</template>

					<NcActions>
						<NcActionButton v-if="share.emailAddress || share.type === 'group'" @click="sendInvitation(share)">
							<template #icon>
								<SendEmailIcon />
							</template>
							{{ share.invitationSent ? t('polls', 'Resend invitation mail') : t('polls', 'Send invitation mail') }}
						</NcActionButton>

						<NcActionButton v-if="share.type === 'user' || share.type === 'admin'" @click="switchAdmin({ share })">
							<template #icon>
								<span v-if="share.type === 'user'"
									aria-hidden="true"
									role="img"
									class="material-design-icon shield-crown-outline">
									<svg fill="currentColor"
										class="material-design-icon__svg"
										width="24"
										height="24"
										viewBox="0 0 24 24">
										<path d="M12 1L21 5V11C21 16.55 17.16 21.74 12 23C6.84 21.74 3 16.55 3 11V5L12 1M12 3.18L5 6.3V11.22C5 15.54 8.25 20 12 21C15.75 20 19 15.54 19 11.22V6.3L12 3.18M16 14V15.5L16 15.59C15.96 15.81 15.78 15.96 15.53 16L15.43 16H8.57L8.47 16C8.22 15.96 8.04 15.81 8 15.59L8 15.5V14H16M17 8L16 13H8L7 8L7 8L9.67 10.67L12 8.34L14.33 10.67L17 8L17 8Z" />
									</svg>
								</span>
								<span v-else
									aria-hidden="true"
									role="img"
									class="material-design-icon shield-crown-outline-strike-thru">
									<svg fill="currentColor"
										class="material-design-icon__svg"
										width="24"
										height="24"
										viewBox="0 0 24 24">
										<path d="M 12 1 L 6.2246094 3.5664062 L 7.7382812 5.0800781 L 12 3.1796875 L 19 6.3007812 L 19 11.220703 C 19 12.637983 18.643799 14.066906 18.041016 15.386719 L 19.527344 16.875 C 20.464898 15.090266 21 13.070015 21 11 L 21 5 L 12 1 z M 1.4101562 1.5800781 L 0 3 L 3 6 L 3 11 C 3 16.55 6.84 21.74 12 23 C 13.934538 22.527613 15.682612 21.502326 17.113281 20.113281 L 17.115234 20.113281 L 20.75 23.75 L 22.160156 22.339844 L 18.433594 18.611328 C 18.434683 18.609871 18.436412 18.60888 18.4375 18.607422 L 17.021484 17.191406 C 17.020461 17.192881 17.018602 17.193838 17.017578 17.195312 L 15.746094 15.923828 C 15.747717 15.922943 15.748389 15.920819 15.75 15.919922 L 13.830078 14 L 13.824219 14 L 12.824219 13 L 12.830078 13 L 10.085938 10.253906 L 10.082031 10.257812 L 5.7832031 5.953125 L 4.2675781 4.4375 L 1.4101562 1.5800781 z M 5 8 L 7.5 10.5 L 8 13 L 10 13 L 11 14 L 8 14 L 8 15.5 L 8 15.589844 C 8.04 15.809844 8.2207031 15.96 8.4707031 16 L 8.5703125 16 L 13 16 L 15.734375 18.734375 C 14.640664 19.827028 13.353093 20.639175 12 21 C 8.25 20 5 15.540703 5 11.220703 L 5 8 z M 17 8 L 14.330078 10.669922 L 12 8.3398438 L 11.498047 8.8417969 L 15.654297 13 L 16 13 L 17 8 z " />
									</svg>
								</span>
							</template>
							{{ share.type === 'user' ? t('polls', 'Grant poll admin access') : t('polls', 'Withdraw poll admin access') }}
						</NcActionButton>

						<NcActionButton @click="copyLink({ url: share.URL })">
							<template #icon>
								<ClippyIcon />
							</template>
							{{ t('polls', 'Copy link to clipboard') }}
						</NcActionButton>

						<NcActionButton v-if="share.URL" @click="openQrModal({ url: share.URL })">
							<template #icon>
								<QrIcon />
							</template>
							{{ t('polls', 'Show QR code') }}
						</NcActionButton>

						<NcActionCaption v-if="share.type === 'public'" :title="t('polls', 'Options for the registration dialog')" />

						<NcActionRadio v-if="share.type === 'public'"
							name="publicPollEmail"
							value="optional"
							:checked="share.publicPollEmail === 'optional'"
							@change="setPublicPollEmail({ share, value: 'optional' })">
							{{ t('polls', 'Email address is optional') }}
						</NcActionRadio>

						<NcActionRadio v-if="share.type === 'public'"
							name="publicPollEmail"
							value="mandatory"
							:checked="share.publicPollEmail === 'mandatory'"
							@change="setPublicPollEmail({ share, value: 'mandatory' })">
							{{ t('polls', 'Email address is mandatory') }}
						</NcActionRadio>

						<NcActionRadio v-if="share.type === 'public'"
							name="publicPollEmail"
							value="disabled"
							:checked="share.publicPollEmail === 'disabled'"
							@change="setPublicPollEmail({ share, value: 'disabled' })">
							{{ t('polls', 'Do not ask for an email address') }}
						</NcActionRadio>
					</NcActions>

					<ActionDelete :title="t('polls', 'Remove share')"
						@delete="removeShare({ share })" />
				</UserItem>
			</TransitionGroup>
		</div>
		<NcModal v-if="qrModal" size="small" @close="qrModal=false">
			<QrModal :title="pollTitle"
				:description="pollDescription"
				:encode-text="qrText"
				class="modal__content">
				<template #description>
					<MarkUpDescription />
				</template>
			</QrModal>
		</NcModal>
	</ConfigBox>
</template>

<script>
import { mapGetters, mapActions, mapState } from 'vuex'
import { showSuccess, showError } from '@nextcloud/dialogs'
import { NcActions, NcActionButton, NcActionCaption, NcActionRadio, NcModal } from '@nextcloud/vue'
import ActionDelete from '../Actions/ActionDelete.vue'
import ConfigBox from '../Base/ConfigBox.vue'
import VotedIcon from 'vue-material-design-icons/CheckboxMarked.vue'
import UnvotedIcon from 'vue-material-design-icons/MinusBox.vue'
import UserSearch from '../User/UserSearch.vue'
import SharePublicAdd from './SharePublicAdd.vue'
import ShareItemAllUsers from './ShareItemAllUsers.vue'
import ShareIcon from 'vue-material-design-icons/ShareVariant.vue'
import SendEmailIcon from 'vue-material-design-icons/EmailArrowRight.vue'
import ClippyIcon from 'vue-material-design-icons/ClipboardArrowLeftOutline.vue'
import QrIcon from 'vue-material-design-icons/Qrcode.vue'
import QrModal from '../Base/QrModal.vue'
import MarkUpDescription from '../Poll/MarkUpDescription.vue'

export default {
	name: 'SharesList',

	components: {
		ClippyIcon,
		QrIcon,
		ShareIcon,
		SendEmailIcon,
		UnvotedIcon,
		UserSearch,
		VotedIcon,
		NcActions,
		NcActionButton,
		NcActionCaption,
		NcActionRadio,
		ActionDelete,
		ConfigBox,
		SharePublicAdd,
		ShareItemAllUsers,
		QrModal,
		NcModal,
		MarkUpDescription,
	},

	data() {
		return {
			qrModal: false,
			qrText: '',
		}
	},

	computed: {
		...mapState({
			allowAllAccess: (state) => state.poll.acl.allowAllAccess,
			allowPublicShares: (state) => state.poll.acl.allowPublicShares,
			pollAccess: (state) => state.poll.access,
			pollTitle: (state) => state.poll.title,
			pollDescription: (state) => state.poll.description,
		}),
		...mapGetters({
			invitationShares: 'shares/invitation',
			hasVoted: 'votes/hasVoted',
		}),
	},

	methods: {
		...mapActions({
			removeShare: 'shares/delete',
			switchAdmin: 'shares/switchAdmin',
			setPublicPollEmail: 'shares/setPublicPollEmail',
		}),

		async sendInvitation(share) {
			const response = await this.$store.dispatch('shares/sendInvitation', { share })
			if (response.data?.sentResult?.sentMails) {
				response.data.sentResult.sentMails.forEach((item) => {
					showSuccess(t('polls', 'Invitation sent to {displayName} ({emailAddress})', { emailAddress: item.emailAddress, displayName: item.displayName }))
				})
			}
			if (response.data?.sentResult?.abortedMails) {
				response.data.sentResult.abortedMails.forEach((item) => {
					console.error('Mail could not be sent!', { recipient: item })
					showError(t('polls', 'Error sending invitation to {displayName} ({emailAddress})', { emailAddress: item.emailAddress, displayName: item.displayName }))
				})
			}
		},

		copyLink(payload) {
			try {
				navigator.clipboard.writeText(payload.url)
				showSuccess(t('polls', 'Link copied to clipboard'))
			} catch {
				showError(t('polls', 'Error while copying link to clipboard'))
			}
		},

		openQrModal(payload) {
			this.qrText = payload.url
			this.qrModal = true
		},
	},
}
</script>

<style lang="scss">
.shares-list.shared {
	border-top: 1px solid var(--color-border);
	padding-top: 24px;
	margin-top: 16px;
}

.vote-status {
	margin-left: 8px;
	width: 32px;

	&.voted {
		color: var(--color-polls-foreground-yes)
	}

	&.unvoted {
		color: var(--color-polls-foreground-no)
	}
}

</style>

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
	<NcAppContent :class="[{ closed: closed, scrolled: scrolled, 'vote-style-beta-510': settings.user.useAlternativeStyling }, poll.type]">
		<HeaderBar class="area__header">
			<template #title>
				{{ poll.title }}
			</template>
			<template #right>
				<PollHeaderButtons />
			</template>
			<PollInfoLine />
		</HeaderBar>

		<div class="vote_main">
			<CardDiv v-if="isNoAccessSet && options.length" type="warning">
				{{ t('polls', 'This poll is unpublished.') }}
				{{ t('polls', 'Invite users or allow internal access for all site users.') }}
				<template #button>
					<NcButton type="primary" @click="openShares">
						{{ t('polls', 'Edit access') }}
					</NcButton>
				</template>
			</CardDiv>

			<CardDiv v-if="acl.allowAddOptions && proposalsOpen && !closed" type="info">
				{{ t('polls', 'You are asked to propose more options. ') }}
				<p v-if="proposalsExpirySet && !proposalsExpired">
					{{ t('polls', 'The proposal period ends {timeRelative}.', { timeRelative: proposalsExpireRelative }) }}
				</p>
				<OptionProposals v-if="poll.type === 'textPoll'" />
				<template #button>
					<OptionProposals v-if="poll.type === 'datePoll'" />
				</template>
			</CardDiv>

			<CardDiv v-if="closed && !showConfirmationMail" type="warning">
				{{ t('polls', 'This poll is closed. No further action is possible.') }}
			</CardDiv>

			<CardDiv v-else-if="showConfirmationMail" :type="confirmationSent">
				{{ confirmationSendMessage }}
				<template #button>
					<ActionSendConfirmed @error="confirmationSendError()"
						@success="confirmationSendSuccess()" />
				</template>
			</CardDiv>
			<CardDiv v-else-if="useRegisterModal" type="info">
				{{ registrationInvitationText }}
				<template #button>
					<NcButton type="info" @click="showRegistration = true">
						{{ t('polls', 'Register') }}
					</NcButton>
				</template>
			</CardDiv>

			<CardDiv v-else-if="share.locked" type="warning">
				{{ lockedShareCardCaption }}
			</CardDiv>

			<div v-if="poll.description" class="area__description">
				<MarkUpDescription />
			</div>

			<div class="area__main" :class="viewMode">
				<VoteTable v-show="options.length" :view-mode="viewMode" />

				<NcEmptyContent v-if="!options.length" :title="t('polls', 'No vote options available')">
					<template #icon>
						<TextPollIcon v-if="poll.type === 'textPoll'" />
						<DatePollIcon v-else />
					</template>
					<template #action>
						<NcButton v-if="acl.allowEdit" type="primary" @click="openOptions">
							<template #default>
								{{ t('polls', 'Add some!') }}
							</template>
						</NcButton>
						<div v-if="!acl.allowEdit">
							{{ t('polls', 'Maybe the owner did not provide some until now.') }}
						</div>
					</template>
				</NcEmptyContent>
			</div>

			<div class="area__footer">
				<CardDiv v-if="countHiddenParticipants" type="warning">
					{{ t('polls', 'Due to possible performance issues {countHiddenParticipants} voters are hidden.', { countHiddenParticipants }) }}
					{{ t('polls', 'You can reveal them, but you may expect an unwanted long loading time.') }}
					<template #button>
						<NcButton type="info" @click="switchSafeTable">
							{{ t('polls', 'Reveal them') }}
						</NcButton>
					</template>
				</CardDiv>

				<CardDiv v-if="poll.anonymous" type="warning">
					{{ t('polls', 'Although participant\'s names are hidden, this is not a real anonymous poll because they are not hidden from the owner.') }}
					{{ t('polls', 'Additionally the owner can remove the anonymous flag at any time, which will reveal the participant\'s names.') }}
				</CardDiv>
			</div>
		</div>
		<div v-if="useRegisterModal">
			<NcModal :show.sync="showRegistration"
				:size="registerModalSize"
				:can-close="true"
				@close="closeRegisterModal()">
				<PublicRegisterModal @close="closeRegisterModal()" />
			</NcModal>
		</div>
		<LoadingOverlay v-if="isLoading" />
	</NcAppContent>
</template>

<script>
import { mapState, mapGetters, mapMutations } from 'vuex'
import { NcModal, NcAppContent, NcButton, NcEmptyContent } from '@nextcloud/vue'
import { emit } from '@nextcloud/event-bus'
import MarkUpDescription from '../components/Poll/MarkUpDescription.vue'
import PollInfoLine from '../components/Poll/PollInfoLine.vue'
import PollHeaderButtons from '../components/Poll/PollHeaderButtons.vue'
import { CardDiv, HeaderBar } from '../components/Base/index.js'
import { ActionSendConfirmed } from '../components/Actions/index.js'
import DatePollIcon from 'vue-material-design-icons/CalendarBlank.vue'
import TextPollIcon from 'vue-material-design-icons/FormatListBulletedSquare.vue'

export default {
	name: 'Vote',
	components: {
		ActionSendConfirmed,
		NcAppContent,
		NcButton,
		NcEmptyContent,
		NcModal,
		HeaderBar,
		MarkUpDescription,
		PollHeaderButtons,
		PollInfoLine,
		DatePollIcon,
		TextPollIcon,
		CardDiv,
		LoadingOverlay: () => import('../components/Base/modules/LoadingOverlay.vue'),
		OptionProposals: () => import('../components/Options/OptionProposals.vue'),
		PublicRegisterModal: () => import('../components/Public/PublicRegisterModal.vue'),
		VoteTable: () => import('../components/VoteTable/VoteTable.vue'),
	},

	data() {
		return {
			isLoading: false,
			showRegistration: false,
			registerModalSize: 'large',
			scrolled: false,
			scrollElement: null,
			confirmationSent: 'info',
			confirmationSendMessage: t('polls', 'You have confirmed options. Inform your participants about the result via email.'),
		}
	},

	computed: {
		...mapState({
			poll: (state) => state.poll,
			acl: (state) => state.poll.acl,
			share: (state) => state.share,
			settings: (state) => state.settings,
		}),

		...mapGetters({
			closed: 'poll/isClosed',
			options: 'options/rankedOptions',
			viewMode: 'poll/viewMode',
			proposalsAllowed: 'poll/proposalsAllowed',
			proposalsOpen: 'poll/proposalsOpen',
			proposalsExpirySet: 'poll/proposalsExpirySet',
			proposalsExpired: 'poll/proposalsExpired',
			proposalsExpireRelative: 'poll/proposalsExpireRelative',
			countHiddenParticipants: 'poll/countHiddenParticipants',
			safeTable: 'poll/safeTable',
			confirmedOptions: 'options/confirmed',
			hasShares: 'shares/hasShares',
		}),

		isNoAccessSet() {
			return this.poll.access === 'private' && !this.hasShares && this.acl.allowEdit
		},

		registrationInvitationText() {
			if (this.share.publicPollEmail === 'mandatory') {
				return t('polls', 'To participate, register with your email address and a name.')
			}
			if (this.share.publicPollEmail === 'optional') {
				return t('polls', 'To participate, register a name and optionally with your email address.')
			}
			return t('polls', 'To participate, register with a name.')
		},

		lockedShareCardCaption() {
			return this.$route.name === 'publicVote' ? t('polls', 'This share is locked and allows only read access. Registering is not possible.') : t('polls', 'Your share is locked and you have just read access to this poll.')
		},

		showConfirmationMail() {
			return this.acl.isOwner && this.closed && this.confirmedOptions.length > 0
		},

		/* eslint-disable-next-line vue/no-unused-properties */
		windowTitle() {
			return `${t('polls', 'Polls')} - ${this.poll.title}`
		},

		useRegisterModal() {
			return (this.$route.name === 'publicVote'
				&& ['public', 'email', 'contact'].includes(this.share.type)
				&& !this.closed
				&& !this.share.locked
				&& !!this.poll.id
			)
		},
	},

	mounted() {
		this.scrollElement = document.getElementById('app-content-vue')
		this.scrollElement.addEventListener('scroll', this.handleScroll)
	},

	beforeDestroy() {
		this.scrollElement.removeEventListener('scroll', this.handleScroll)
		this.$store.dispatch({ type: 'poll/reset' })
	},

	methods: {
		...mapMutations({
			switchSafeTable: 'poll/switchSafeTable',
		}),

		confirmationSendError() {
			this.confirmationSent = 'error'
			this.confirmationSendMessage = t('polls', 'Some confirmation messages could not been sent.')
		},

		confirmationSendSuccess() {
			this.confirmationSent = 'success'
			this.confirmationSendMessage = t('polls', 'Messages sent.')
		},

		closeRegisterModal() {
			this.showRegistration = false
		},

		handleScroll() {
			if (this.scrollElement.scrollTop > 20) {
				this.scrolled = true
			} else {
				this.scrolled = false
			}
		},

		openOptions() {
			emit('polls:sidebar:toggle', { open: true, activeTab: 'options' })
		},

		openShares() {
			emit('polls:sidebar:toggle', { open: true, activeTab: 'sharing' })
		},
	},
}

</script>

<style lang="scss">
.vote_main {
	display: flex;
	flex-direction: column;
	row-gap: 8px;
}

.vote_head {
	display: flex;
	flex-wrap: wrap-reverse;
	justify-content: flex-end;
	.poll-title {
		flex: 1 270px;
	}
}

.area__main {
	display: flex;
	flex-direction: column;
}

.app-content .area__header {
	transition: all var(--animation-slow) linear;
}

.app-content.scrolled .area__header {
	box-shadow: 6px 6px 6px var(--color-box-shadow);
}

.area__proposal .mx-input-wrapper > button {
	width: initial;
}

.icon.icon-settings.active {
	display: block;
	width: 44px;
	height: 44px;
}

.card-with-action {
	display: flex;
	align-items: center;
	column-gap: 8px;
}

.left-card-side {
  flex: 1;
}

.right-card-side {
  flex: 0;
}

// hack
.notecard > div {
  flex: 1;
}

</style>

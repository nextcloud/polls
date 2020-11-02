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
	<div v-show="share.type !== 'public'">
		<h2 class="title">
			{{ t('polls', 'This is a public poll.') }}
		</h2>
		<p>{{ t('polls', 'The following link is your personal access to this poll. You can reenter this poll at any time, change your vote and leave comments.') }}</p>
		<p>{{ t('polls', 'Your personal link to this poll: {linkURL}', { linkURL: personalLink} ) }}</p>
		<ButtonDiv icon="icon-clippy" :title="t('polls','Copy this link to the clipboard')" @click="copyLink()" />
		<ButtonDiv v-if="share.emailAddress"
			icon="icon-mail"
			:title="t('polls','Resend invitation mail to {emailAdress}', { emailAdress: share.emailAddress })"
			@click="sendInvitation()" />
	</div>
</template>

<script>
import ButtonDiv from '../Base/ButtonDiv'
import { showSuccess, showError } from '@nextcloud/dialogs'
import { mapState } from 'vuex'

export default {
	name: 'PersonalLink',

	components: {
		ButtonDiv,
	},

	computed: {
		...mapState({
			share: state => state.poll.share,
		}),

		personalLink() {
			return window.location.origin
				+ this.$router.resolve({
					name: 'publicVote',
					params: { token: this.$route.params.token },
				}).href
		},

	},

	methods: {
		sendInvitation() {
			this.$store.dispatch('poll/share/sendInvitation')
				.then((response) => {
					response.data.sentResult.sentMails.forEach((item) => {
						showSuccess(t('polls', 'Invitation sent to {emailAddress}', { emailAddress: item.eMailAddress }))
					})
					response.data.sentResult.abortedMails.forEach((item) => {
						console.error('Mail could not be sent!', { recipient: item })
						showError(t('polls', 'Error sending invitation to {emailAddress}', { emailAddress: item.eMailAddress }))
					})
				})
		},

		copyLink() {
			this.$copyText(this.personalLink).then(
				function() {
					showSuccess(t('polls', 'Link copied to clipboard'))
				},
				function() {
					showError(t('polls', 'Error while copying link to clipboard'))
				}
			)
		},
	},
}
</script>

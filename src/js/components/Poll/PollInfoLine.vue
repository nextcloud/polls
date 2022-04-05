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

<template lang="html">
	<div class="poll-info-line">
		<span v-for="(subText) in subTexts" :key="subText.id" :class="subText.class">
			<span :class="subText.icon" />
			<span class="sub-text">{{ subText.text }}</span>
		</span>
	</div>
</template>

<script>
import { mapState, mapGetters } from 'vuex'
import moment from '@nextcloud/moment'

export default {
	name: 'PollInfoLine',

	computed: {
		...mapState({
			access: (state) => state.poll.access,
			title: (state) => state.poll.title,
			expire: (state) => state.poll.expire,
			isDeleted: (state) => state.poll.deleted,
			ownerDisplayName: (state) => state.poll.owner.displayName,
			pollCreated: (state) => state.poll.created,
			mayEdit: (state) => state.poll.acl.allowEdit,
		}),

		...mapGetters({
			isClosed: 'poll/isClosed',
			hasShares: 'shares/hasShares',
			proposalsExpirySet: 'poll/proposalsExpirySet',
			proposalsExpired: 'poll/proposalsExpired',
			proposalsExpireRelative: 'poll/proposalsExpireRelative',
		}),

		isNoAccessSet() {
			return this.access === 'private' && !this.hasShares && this.mayEdit
		},

		subTexts() {
			const subTexts = []

			if (this.access === 'private') {
				subTexts.push({
					id: this.access,
					text: t('polls', 'A private poll from {name}', { name: this.ownerDisplayName }),
					icon: '',
					class: '',
				})
			} else {
				subTexts.push({
					id: this.access,
					text: t('polls', 'An open accessible poll from {name}', { name: this.ownerDisplayName }),
					icon: '',
					class: '',
				})
			}

			if (this.isNoAccessSet) {
				subTexts.push({
					id: 'no-access',
					text: t('polls', 'Invite users via the share tab in the sidebar'),
					icon: 'icon-mask-md-sidebar-share',
					class: 'closed',
				})
				return subTexts
			}
			if (this.isDeleted) {
				subTexts.push({
					id: 'deleted',
					text: t('polls', 'Archived'),
					icon: 'icon-mask-md-archived-poll',
					class: 'archived',
				})
				return subTexts
			}

			if (this.isClosed) {
				subTexts.push({
					id: 'closed',
					text: this.timeExpirationRelative,
					icon: 'icon-mask-md-closed-poll',
					class: 'closed',
				})
				return subTexts
			}

			if (!this.isClosed && this.expire) {
				subTexts.push({
					id: 'expiring',
					text: t('polls', 'Closing {relativeExpirationTime}', { relativeExpirationTime: this.timeExpirationRelative }),
					icon: 'icon-mask-md-expiration',
					class: this.closeToClosing ? 'closing' : 'open',
				})
				return subTexts
			}

			if (this.proposalsExpirySet && this.proposalsExpired) {
				subTexts.push({
					id: 'expired',
					text: t('polls', 'Proposal period ended {timeRelative}', { timeRelative: this.proposalsExpireRelative }),
					icon: 'icon-mask-md-proposals',
					class: 'proposal',
				})
				return subTexts
			}

			if (this.proposalsExpirySet && !this.proposalsExpired) {
				subTexts.push({
					id: 'proposal-open',
					text: t('polls', 'Proposal period ends {timeRelative}', { timeRelative: this.proposalsExpireRelative }),
					icon: 'icon-mask-md-proposals',
					class: 'proposal',
				})
				return subTexts
			}

			if (subTexts.length < 2) {
				subTexts.push({
					id: 'created',
					text: this.dateCreatedRelative,
					icon: 'icon-mask-md-creation',
					class: 'created',
				})
			}
			return subTexts
		},

		dateCreatedRelative() {
			return moment.unix(this.pollCreated).fromNow()
		},

		closeToClosing() {
			return (!this.isClosed && this.expire && moment.unix(this.expire).diff() < 86400000)
		},

		timeExpirationRelative() {
			if (this.expire) {
				return moment.unix(this.expire).fromNow()
			}
			return t('polls', 'never')

		},
	},
}

</script>

<style lang="scss">
.poll-info-line {
	display: flex;
	flex-wrap: wrap;
	opacity: 0.7;
	font-size: 1em;

	& > span:not(:last-child)::after {
		content: "|";
		padding: 0 2px;
	}

	[class^="icon-"],
	[class*=" icon-"] {
		padding-right: 21px;
	}

	[class^="icon-md"],
	[class*=" icon-md"] {
		mask-size: 1em;
	}

	.closed {
		.sub-text{
			color: var(--color-error);
			font-weight: 700;
		}
	}

	.closing {
		.sub-text{
			color: var(--color-warning);
			font-weight: 700;
		}
	}

	.open {
		.sub-text{
			font-weight: 700;
		}
	}

	.archived {
		.sub-text{
			color: var(--color-error);
			font-weight: 700;
		}
	}

	.created {
		.sub-text{
			color: var(--color-text-light);
		}
	}
}
</style>

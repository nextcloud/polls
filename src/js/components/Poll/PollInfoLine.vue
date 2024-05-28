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
	<div class="poll-info-line">
		<span v-for="(subText) in subTexts" :key="subText.id" :class="['sub-text', subText.class]">
			<Component :is="subText.iconComponent" :size="16" />
			<span class="sub-text">{{ subText.text }}</span>
		</span>
	</div>
</template>

<script>
import { mapState, mapGetters } from 'vuex'
import moment from '@nextcloud/moment'
import unpublishedIcon from 'vue-material-design-icons/PublishOff.vue'
import archivedPollIcon from 'vue-material-design-icons/Archive.vue'
import closedPollIcon from 'vue-material-design-icons/Lock.vue'
import creationIcon from 'vue-material-design-icons/ClockOutline.vue'
import ProposalsIcon from 'vue-material-design-icons/Offer.vue'
import ExpirationIcon from 'vue-material-design-icons/CalendarEnd.vue'

export default {
	name: 'PollInfoLine',

	computed: {
		...mapState({
			access: (state) => state.poll.access,
			expire: (state) => state.poll.expire,
			isDeleted: (state) => state.poll.deleted,
			ownerDisplayName: (state) => state.poll.owner.displayName,
			pollCreated: (state) => state.poll.created,
			mayEdit: (state) => state.poll.acl.permissions.edit,
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

			if (this.isDeleted) {
				subTexts.push({
					id: 'deleted',
					text: t('polls', 'Archived'),
					class: 'archived',
					iconComponent: archivedPollIcon,
				})
				return subTexts
			}

			if (this.isNoAccessSet) {
				subTexts.push({
					id: 'no-access',
					text: [
						t('polls', 'Unpublished'),
					].join('. '),
					class: 'unpublished',
					iconComponent: unpublishedIcon,
				})
				return subTexts
			}

			if (this.access === 'private') {
				subTexts.push({
					id: this.access,
					text: t('polls', 'A private poll from {name}', { name: this.ownerDisplayName }),
					class: '',
					iconComponent: null,
				})
			} else {
				subTexts.push({
					id: this.access,
					text: t('polls', 'An openly accessible poll from {name}', { name: this.ownerDisplayName }),
					class: '',
					iconComponent: null,
				})
			}

			if (this.isClosed) {
				subTexts.push({
					id: 'closed',
					text: this.timeExpirationRelative,
					class: 'closed',
					iconComponent: closedPollIcon,
				})
				return subTexts
			}

			if (!this.isClosed && this.expire) {
				subTexts.push({
					id: 'expiring',
					text: t('polls', 'Closing {relativeExpirationTime}', { relativeExpirationTime: this.timeExpirationRelative }),
					class: this.closeToClosing ? 'closing' : 'open',
					iconComponent: ExpirationIcon,
				})
				return subTexts
			}

			if (this.proposalsExpirySet && this.proposalsExpired) {
				subTexts.push({
					id: 'expired',
					text: t('polls', 'Proposal period ended {timeRelative}', { timeRelative: this.proposalsExpireRelative }),
					class: 'proposal',
					iconComponent: ProposalsIcon,
				})
				return subTexts
			}

			if (this.proposalsExpirySet && !this.proposalsExpired) {
				subTexts.push({
					id: 'proposal-open',
					text: t('polls', 'Proposal period ends {timeRelative}', { timeRelative: this.proposalsExpireRelative }),
					class: 'proposal',
					iconComponent: ProposalsIcon,
				})
				return subTexts
			}

			if (subTexts.length < 2) {
				subTexts.push({
					id: 'created',
					text: this.dateCreatedRelative,
					class: 'created',
					iconComponent: creationIcon,
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

	.material-design-icon {
		padding: 0 2px;
	}

	.sub-text {
		display: flex;
	}

	& > span:not(:last-child)::after {
		content: "|";
		padding: 0 2px;
	}

	.closed, .archived {
		.sub-text{
			color: var(--color-error);
			font-weight: 700;
		}
	}

	.unpublished, .open {
		.sub-text{
			font-weight: 700;
		}
	}

	.closing {
		.sub-text{
			color: var(--color-warning);
			font-weight: 700;
		}
	}

	.created {
		.sub-text{
			color: var(--color-main-text);
		}
	}
}
</style>

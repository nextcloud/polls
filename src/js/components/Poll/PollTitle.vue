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
	<div class="title">
		<div class="title__title">
			{{ title }}
		</div>
		<Badge v-if="showBadge" v-bind="badge" />
	</div>
</template>

<script>
import { mapState, mapGetters } from 'vuex'
import moment from '@nextcloud/moment'
import Badge from '../Base/Badge'

export default {
	name: 'PollTitle',

	components: {
		Badge,
	},

	computed: {
		...mapState({
			title: state => state.poll.title,
			expire: state => state.poll.expire,
			deleted: state => state.poll.deleted,
		}),

		...mapGetters({
			isClosed: 'poll/closed',
		}),

		showBadge() {
			return (this.deleted || this.expire)
		},

		badge() {
			if (this.deleted) {
				return {
					title: t('polls', 'Deleted'),
					icon: 'icon-delete',
					class: 'error',
				}
			}

			if (this.isClosed) {
				return {
					title: t('polls', 'Closed {relativeTimeAgo}', { relativeTimeAgo: this.timeExpirationRelative }),
					icon: 'icon-polls-closed-fff',
					class: 'error',
				}
			}

			if (!this.isClosed && this.expire) {
				return {
					title: t('polls', 'Closing {relativeExpirationTime}', { relativeExpirationTime: this.timeExpirationRelative }),
					icon: 'icon-calendar',
					class: this.closeToClosing ? 'warning' : 'success',
				}
			}

			return {
				show: false,
				title: '',
				icon: '',
				class: '',
			}
		},

		closeToClosing() {
			return (!this.isClosed && this.expire && moment.unix(this.expire).diff() < 86400000)
		},

		timeExpirationRelative() {
			if (this.expire) {
				return moment.unix(this.expire).fromNow()
			} else {
				return t('polls', 'never')
			}
		},
	},
}

</script>

<style lang="scss">
	.title {
		display: flex;
		flex: 1 0 230px;
		align-items: center;
		flex-wrap: wrap;

		.title__title {
			font-weight: bold;
			font-size: 20px;
			line-height: 30px;
			color: var(--color-text-light);
		}
	}
</style>

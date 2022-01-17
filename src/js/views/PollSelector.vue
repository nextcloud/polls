<!--
  - @copyright Copyright (c) 2019 Julius Härtl <jus@bitgrid.net>
  -
  - @author Julius Härtl <jus@bitgrid.net>
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
  - along with this program. If not, see <http://www.gnu.org/licenses/>.
  -
  -->

<template>
	<Modal
		:container="container"
		@close="close">
		<div id="modal-inner" class="polls-picker-modal" :class="{ 'icon-loading': loading }">
			<div id="modal-content">
				<h2>
					{{ dialogTitle }} toll
				</h2>
				<p v-if="dialogSubtitle" class="subtitle">
					{{ dialogSubtitle }}
				</p>
				<div id="polls-list">
					<ul v-if="!loading && availablePolls.length > 0">
						<li v-for="(poll) in availablePolls"
							:key="poll.id"
							:class="['poll-item', {selected: selectedPollId === poll.id }]"
							@click="selectedPollId = poll.id">
							<UserItem condensed :user-id="poll.owner" />
							<div class="poll-title-box">
								{{ poll.title }}
							</div>
						</li>
					</ul>
					<div v-else-if="!loading">
						{{ t('polls', 'No polls found') }}
					</div>
				</div>
				<div id="modal-buttons">
					<button
						v-if="!loading && availablePolls.length > 0"
						class="primary"
						:disabled="!selectedPollId"
						@click="select">
						{{ t('poll', 'Select poll') }}
					</button>
				</div>
			</div>
		</div>
	</Modal>
</template>

<script>
import axios from '@nextcloud/axios'
import { Modal } from '@nextcloud/vue'
import { generateUrl } from '@nextcloud/router'
import UserItem from '../components/User/UserItem'

export default {
	name: 'PollSelector',
	components: {
		// ConversationIcon,
		Modal,
		UserItem,
	},
	props: {
		container: {
			type: String,
			default: undefined,
		},

		dialogTitle: {
			type: String,
			default: t('polls', 'Link to a poll'),
		},

		dialogSubtitle: {
			type: String,
			default: '',
		},
	},
	data() {
		return {
			selectedPollId: null,
			loading: false,
			polls: [],
		}
	},
	computed: {
		availablePolls() {
			return this.polls
		},
	},
	beforeMount() {
		this.loadPolls()
	},

	methods: {
		async loadPolls() {
			try {
				const response = await axios.get(generateUrl('apps/polls/polls'), { params: { time: +new Date() } })
				this.polls = response.data.list
			} catch (e) {
				console.error('Error loading polls', { error: e.response })
				this.polls = []
			} finally {
				this.loading = false
			}
		},
		close() {
			this.$root.$emit('close')
			this.$emit('close')
		},
		select() {
			this.$root.$emit('select', this.selectedPollId)
		},
	},
}
</script>

<style lang="scss" scoped>
#modal-inner {
	width: 90vw;
	max-width: 400px;
	height: 50vh;
	position: relative;

	h2 {
		margin-bottom: 4px;
	}
}

#modal-content {
	position: absolute;
	width: calc(100% - 40px);
	height: calc(100% - 40px);
	display: flex;
	flex-direction: column;
	padding: 20px;
}

#polls-list {
	overflow-y: auto;
	flex: 0 1 auto;
	height: 100%;
}

li.poll-item {
	padding: 6px;
	border: 1px solid transparent;
	display: flex;
	align-items: center;

	&:hover,
	&:focus {
		background-color: var(--color-background-dark);
		border-radius: var(--border-radius-pill);
	}

	&.selected {
		background-color: var(--color-primary-light);
		border-radius: var(--border-radius-pill);
	}

	& > span {
		padding: 5px 5px 5px 10px;
		vertical-align: middle;
		text-overflow: ellipsis;
		white-space: nowrap;
		overflow: hidden;
	}
}

#modal-buttons {
	overflow: hidden;
	flex-shrink: 0;
	button {
		height: 44px;
		margin: 0;
	}

	.primary {
		float: right;
	}
}

.subtitle {
	color: var(--color-text-maxcontrast);
	margin-bottom: 8px;
}

</style>

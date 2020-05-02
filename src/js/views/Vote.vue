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
	<AppContent>
		<div class="header-actions">
			<Actions>
				<ActionButton :icon="tableMode ? 'icon-toggle-filelist' : 'icon-toggle-pictures'" @click="tableMode = !tableMode">
					{{ t('polls', 'Switch view') }}
				</ActionButton>
			</Actions>
			<Actions>
				<ActionButton icon="icon-settings" @click="emit('toggle-sidebar')">
					{{ t('polls', 'Toggle Sidebar') }}
				</ActionButton>
			</Actions>
		</div>
		<h2 class="title">
			{{ poll.title }}
			<span v-if="expired" class="label error">{{ t('polls', 'Expired') }}</span>
			<span v-if="!expired && poll.expire" class="label success">{{ t('polls', 'Place your votes until %n', 1, moment.unix(poll.expire).format('LLLL')) }}</span>
			<span v-if="poll.deleted" class="label error">{{ t('polls', 'Deleted') }}</span>
		</h2>
		<PollInformation />

		<VoteHeaderPublic v-if="getCurrentUser" />

		<h3 class="description">
			{{ poll.description ? poll.description : t('polls', 'No description provided') }}
		</h3>

		<VoteList v-show="!tableMode && options.list.length" />

		<VoteTable v-show="tableMode && options.list.length" />

		<div v-if="!options.list.length" class="emptycontent">
			<div class="icon-toggle-filelist" />
			<button v-if="acl.allowEdit" @click="openOptions">
				{{ t('polls', 'There are no vote options, add some in the options section of the right side bar.') }}
			</button>
			<div v-if="!acl.allowEdit">
				{{ t('polls', 'There are no vote options. Maybe the owner did not provide some until now.') }}
			</div>
		</div>

		<Subscription v-if="getCurrentUser" />

		<div class="additional">
			<ParticipantsList v-if="acl.allowSeeUsernames" />
		</div>
		<LoadingOverlay v-if="isLoading" />
	</AppContent>
</template>

<script>
import { Actions, ActionButton, AppContent } from '@nextcloud/vue'
import Subscription from '../components/Subscription/Subscription'
import ParticipantsList from '../components/Base/ParticipantsList'
import PollInformation from '../components/Base/PollInformation'
import LoadingOverlay from '../components/Base/LoadingOverlay'
import VoteHeaderPublic from '../components/VoteTable/VoteHeaderPublic'
import VoteList from '../components/VoteTable/VoteList'
import VoteTable from '../components/VoteTable/VoteTable'
import { mapState, mapGetters } from 'vuex'
import { emit } from '@nextcloud/event-bus'

export default {
	name: 'Vote',
	components: {
		Actions,
		ActionButton,
		AppContent,
		Subscription,
		ParticipantsList,
		PollInformation,
		LoadingOverlay,
		VoteHeaderPublic,
		VoteTable,
		VoteList,
	},

	data() {
		return {
			voteSaved: false,
			delay: 50,
			isLoading: true,
			tableMode: true,
		}
	},

	computed: {
		...mapState({
			poll: state => state.poll,
			acl: state => state.acl,
			options: state => state.options,
		}),

		...mapGetters([
			'expired',
		]),

		windowTitle: function() {
			return t('polls', 'Polls') + ' - ' + this.poll.title
		},

	},

	watch: {
		$route() {
			this.loadPoll()
		},
	},

	created() {
		this.loadPoll()
		emit('toggle-sidebar', { open: (window.innerWidth > 920) })
	},

	beforeDestroy() {
		this.$store.commit({ type: 'resetPoll' })
	},

	methods: {
		openOptions() {
			emit('toggle-sidebar', { open: true, activeTab: 'options' })
		},

		openConfiguration() {
			emit('toggle-sidebar', { open: true, activeTab: 'configuration' })
		},

		loadPoll() {
			this.isLoading = true
			this.$store.dispatch({ type: 'loadPollMain', pollId: this.$route.params.id, token: this.$route.params.token })
				.then((response) => {
					if (response.status === 200) {
						this.$store.dispatch({ type: 'loadPoll', pollId: this.$route.params.id, token: this.$route.params.token })
							.then(() => {
								if (this.acl.allowEdit && moment.unix(this.poll.created).diff() > -10000) {
									this.openConfiguration()
								}
								this.isLoading = false
							})
						window.document.title = this.windowTitle
					} else {
						this.$router.replace({ name: 'notfound' })
					}
				})
				.catch((error) => {
					console.error(error)
					this.isLoading = false
					this.$router.replace({ name: 'notfound' })
				})
		},
	},
}
</script>

<style lang="scss" scoped>
#emptycontent, .emptycontent {
	margin: 44px 0;
}

.additional {
	display: flex;
	flex-wrap: wrap;
	.participants {
		flex: 1;
	}
	.comments {
		flex: 3;
	}
}

.header-actions {
	display: flex;
	float: right;
}

.icon.icon-settings.active {
	display: block;
	width: 44px;
	height: 44px;
}
@media (max-width: (1024px)) {
	.title {
		padding-left: 14px;
	}
}
</style>

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
		<div v-if="poll.id > 0" v-show="!isLoading" class="main-container">
			<div class="header-actions">
				<button class="button btn primary" @click="tableMode = !tableMode">
					<span>{{ t('polls', 'Switch view') }}</span>
				</button>
				<a href="#" class="icon icon-settings active"
					:title="t('polls', 'Open Sidebar')" @click="toggleSideBar()" />
			</div>
			<PollTitle />
			<PollInformation />
			<VoteHeaderPublic v-if="!OC.currentUser" />
			<PollDescription />
			<VoteList v-show="!tableMode && options.list.length" />
			<VoteTable v-show="tableMode && options.list.length" />
			<div v-if="!options.list.length" class="emptycontent">
				<div class="icon-toggle-filelist" />
				<button v-if="acl.getAllowEdit" @click="openOptions">
					{{ t('polls', 'There are no vote options, add some in the options section of the right side bar.') }}
				</button>
				<div v-if="!acl.getAllowEdit">
					{{ t('polls', 'There are no vote options. Maybe the owner did not provide some until now.') }}
				</div>
			</div>

			<Subscription v-if="OC.currentUser" />
			<div class="additional">
				<ParticipantsList v-if="acl.allowSeeUsernames" />
			</div>
		</div>

		<SideBar v-if="sideBarOpen" :active="activeTab" @closeSideBar="toggleSideBar" />
		<LoadingOverlay v-if="isLoading" />
	</AppContent>
</template>

<script>
import { AppContent } from '@nextcloud/vue'
import Subscription from '../components/Subscription/Subscription'
import ParticipantsList from '../components/Base/ParticipantsList'
import PollDescription from '../components/Base/PollDescription'
import PollInformation from '../components/Base/PollInformation'
import PollTitle from '../components/Base/PollTitle'
import LoadingOverlay from '../components/Base/LoadingOverlay'
import VoteHeaderPublic from '../components/VoteTable/VoteHeaderPublic'
import SideBar from '../components/SideBar/SideBar'
import VoteList from '../components/VoteTable/VoteList'
import VoteTable from '../components/VoteTable/VoteTable'
import { mapState } from 'vuex'

export default {
	name: 'Vote',
	components: {
		AppContent,
		Subscription,
		ParticipantsList,
		PollDescription,
		PollInformation,
		PollTitle,
		LoadingOverlay,
		VoteHeaderPublic,
		SideBar,
		VoteTable,
		VoteList
	},

	data() {
		return {
			voteSaved: false,
			delay: 50,
			sideBarOpen: true,
			isLoading: false,
			initialTab: 'comments',
			tableMode: true,
			activeTab: t('polls', 'Comments').toLowerCase()
		}
	},

	computed: {
		...mapState({
			poll: state => state.poll,
			acl: state => state.acl,
			options: state => state.options
		}),

		windowTitle: function() {
			return t('polls', 'Polls') + ' - ' + this.poll.title
		}

	},

	watch: {
		$route() {
			this.loadPoll()
		}
	},

	mounted() {
		this.loadPoll()
	},

	methods: {
		openOptions() {
			this.sideBarOpen = true
			this.activeTab = t('polls', 'Options').toLowerCase()
		},

		openConfiguration() {
			this.sideBarOpen = true
			this.activeTab = t('polls', 'Configuration').toLowerCase()
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

		toggleSideBar() {
			this.sideBarOpen = !this.sideBarOpen
		}
	}
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

.main-container {
	position: relative;
	flex: 1;
	padding: 8px 24px;
	margin: 0;
	flex-direction: column;
	flex-wrap: nowrap;
	overflow-x: scroll;
}

.header-actions {
	right: 0;
	position: absolute;
	display: flex;
}

.icon.icon-settings.active {
	display: block;
	width: 44px;
	height: 44px;
}

</style>

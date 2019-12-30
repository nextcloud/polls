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
		<div v-if="poll.id > 0" class="main-container">
			<a v-if="!sideBarOpen" href="#" class="icon icon-settings active"
				:title="t('polls', 'Open Sidebar')" @click="toggleSideBar()" />
			<VoteHeader />
			<VoteTable v-show="!loading" />
			<Subscription />
		</div>

		<SideBar v-if="sideBarOpen && acl.allowEdit" @closeSideBar="toggleSideBar" />
		<SideBarOnlyComments v-if="sideBarOpen && !acl.allowEdit" @closeSideBar="toggleSideBar" />
		<LoadingOverlay v-if="loading" />
	</AppContent>
</template>

<script>
import Subscription from '../components/Subscription/Subscription'
import VoteHeader from '../components/VoteTable/VoteHeader'
import VoteTable from '../components/VoteTable/VoteTable'
import SideBar from '../components/SideBar/SideBar'
import SideBarOnlyComments from '../components/SideBar/SideBarOnlyComments'
import { mapState, mapGetters } from 'vuex'

export default {
	name: 'Vote',
	components: {
		Subscription,
		VoteHeader,
		VoteTable,
		SideBarOnlyComments,
		SideBar
	},

	data() {
		return {
			voteSaved: false,
			delay: 50,
			sideBarOpen: false,
			loading: false,
			initialTab: 'comments',
			newName: ''
		}
	},

	computed: {
		...mapState({
			poll: state => state.poll,
			shares: state => state.shares,
			acl: state => state.acl
		}),

		...mapGetters([
			'expired'
		]),

		windowTitle: function() {
			return t('polls', 'Polls') + ' - ' + this.poll.title
		},

		votePossible() {
			return this.acl.allowVote && !this.expired
		}
	},

	watch: {
		'$route'(to, from) {
			this.loadPoll()
		},

		'poll.id'(to, from) {
			this.$store.dispatch({ type: 'loadPoll', pollId: this.$route.params.id })
				.then(() => {
					if (this.acl.allowEdit && moment.unix(this.poll.created).diff() > -10000) {
						this.sideBarOpen = true
					}
					this.loading = false
				})
		}
	},

	mounted() {
		this.loadPoll()
	},

	methods: {
		loadPoll() {
			this.loading = false
			this.$store.dispatch({ type: 'loadPollMain', pollId: this.$route.params.id })
				.catch(() => {
					this.loading = false
				})
		},

		toggleSideBar() {
			this.sideBarOpen = !this.sideBarOpen
		}
	}
}
</script>

<style lang="scss" scoped>
	.main-container {
		flex: 1;
		margin: 0;
		flex-direction: column;
		flex: 1;
		flex-wrap: nowrap;
		overflow-x: scroll;
		h1, h2, h3, h4 {
			margin-left: 24px;
		}
	}

	.icon.icon-settings.active {
		display: block;
		width: 44px;
		height: 44px;
		right: 0;
		position: absolute;
	}

</style>

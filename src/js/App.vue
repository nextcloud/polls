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
	<Content app-name="polls" :style="appStyle" :class="[transitionClass, { 'experimental': settings.experimental, 'bgimage': settings.useImage, 'bgcolored': settings.experimental }]">
		<Navigation v-if="getCurrentUser()" :class="{ 'glassy': settings.glassyNavigation }" />
		<router-view />
		<SideBar v-if="sideBarOpen && $store.state.poll.id"
			:active="activeTab"
			:class="{ 'glassy': settings.glassySidebar }" />
	</Content>
</template>

<script>
import Navigation from './components/Navigation/Navigation'
import SideBar from './components/SideBar/SideBar'
import { getCurrentUser } from '@nextcloud/auth'
import { showError } from '@nextcloud/dialogs'
import { Content } from '@nextcloud/vue'
import { subscribe, unsubscribe } from '@nextcloud/event-bus'
import { mapState } from 'vuex'
import '@nextcloud/dialogs/styles/toast.scss'

export default {
	name: 'App',
	components: {
		Navigation,
		Content,
		SideBar,
	},

	data() {
		return {
			sideBarOpen: (window.innerWidth > 920),
			activeTab: 'comments',
			transitionClass: 'transitions-active',
		}
	},

	computed: {
		...mapState({
			settings: state => state.settings.user,
		}),
		appStyle() {
			if (this.settings.useImage && this.settings.experimental) {
				return {
					backgroundImage: 'url(' + this.settings.imageUrl + ')',
					backgroundSize: 'cover',
					backgroundPosition: 'center center',
					backgroundAttachment: 'fixed',
					backgroundRepeat: 'no-repeat',
				}
			} else {
				return {}
			}
		},
	},

	created() {
		subscribe('transitions-off', (delay) => {
			this.transitionClass = ''
			if (delay) {
				setTimeout(() => {
					this.transitionClass = 'transitions-active'
				}, delay)
			}
		})

		subscribe('transitions-on', () => {
			this.transitionClass = 'transitions-active'
		})

		subscribe('toggle-sidebar', (payload) => {
			if (payload === undefined) {
				this.sideBarOpen = !this.sideBarOpen
			} else {
				if (payload.activeTab !== undefined) {
					this.activeTab = payload.activeTab
				}
				if (payload.open !== undefined) {
					this.sideBarOpen = payload.open
				} else {
					this.sideBarOpen = !this.sideBarOpen
				}
			}

		})

		this.$store.dispatch('settings/get')
		if (getCurrentUser()) {
			this.updatePolls()
			subscribe('update-polls', () => {
				this.updatePolls()
			})
		}
	},

	beforeDestroy() {
		unsubscribe('update-polls')
		unsubscribe('toggle-sidebar')
	},

	methods: {
		updatePolls() {
			if (getCurrentUser()) {

				this.$store.dispatch('polls/load')
					.catch(() => {
						showError(t('polls', 'Error loading poll list'))
					})
			}
		},
	},
}

</script>

<style  lang="scss">
:root {
	--polls-vote-rows: 1;
	--polls-vote-columns: 1;
	--color-background-error: #f9c5c5;
	--color-background-success: #d6fdda;
	--color-polls-foreground-yes: #49bc49;
	--color-polls-foreground-no: #f45573;
	--color-polls-foreground-maybe: #ffc107;
	--color-polls-background-yes: #ebf5d6;
	--color-polls-background-no: #ffede9;
	--color-polls-background-maybe: #fcf7e1;
	--icon-polls-confirmed: url('/index.php/svg/polls/confirmed?color=000&v=1');
	--icon-polls-unconfirmed: url('/index.php/svg/polls/unconfirmed?color=000&v=1');
	--icon-polls-clone: url('/index.php/svg/polls/clone?color=000&v=1');
	--icon-polls-expired: url('/index.php/svg/polls/clock?color=000&v=1');
	--icon-polls-move: url('/index.php/svg/polls/move?color=000&v=1');
	--icon-polls-yes: url('/index.php/svg/core/actions/checkmark?color=49bc49&v=1');
	--icon-polls-no: url('/index.php/svg/core/actions/close?color=f45573&v=1');
	--icon-polls-maybe: url('/index.php/svg/polls/maybe-vote?color=ffc107&v=1');
	--icon-polls: url('/index.php/svg/polls/app?color=000&v=1');
	--icon-polls-handle: url('/index.php/svg/polls/handle?color=000&v=1');
	--icon-polls-mail: url('/index.php/svg/polls/mail?color=000&v=1');
	--icon-polls-sidebar-toggle: url('/index.php/svg/polls/sidebar-toggle?color=000&v=1');

	// filters to colorize background svg from black
	// generated with https://codepen.io/jsm91/embed/ZEEawyZ?height=600&default-tab=result&embed-version=2
	--color-polls-foreground-filter-yes: invert(74%) sepia(7%) saturate(3830%) hue-rotate(68deg) brightness(85%) contrast(85%);
	--color-polls-foreground-filter-no: invert(43%) sepia(100%) saturate(1579%) hue-rotate(318deg) brightness(99%) contrast(94%);
	--color-polls-foreground-filter-maybe: invert(81%) sepia(22%) saturate(3383%) hue-rotate(353deg) brightness(101%) contrast(101%);
}

.icon-polls {
	background-image: var(--icon-polls);
}

.icon-polls-confirmed {
	background-image: var(--icon-polls-confirmed);
}

.icon-polls-unconfirmed {
	background-image: var(--icon-polls-unconfirmed);
}

.icon-polls-expired {
	background-image: var(--icon-polls-expired);
}

.icon-polls-move {
	background-image: var(--icon-polls-move);
}

.icon-polls-clone {
	background-image: var(--icon-polls-clone);
}

.icon-polls-yes {
	background-image: var(--icon-polls-yes);
}

.icon-polls-no {
	background-image: var(--icon-polls-no);
}

.icon-polls-maybe {
	background-image: var(--icon-polls-maybe);
}

.icon-polls-mail {
	background-image: var(--icon-polls-mail);
}

.icon-polls-sidebar-toggle {
	background-image: var(--icon-polls-sidebar-toggle);
}

.title {
	margin: 8px 0;
}

.description {
	white-space: break-spaces;
	margin: 8px 0;
}

.icon-handle {
	background-image: var(--icon-polls-handle);
}

.transitions-active {
	.list-enter-active,
	.list-leave-active {
		transition: all 0.5s ease;
	}

	.list-enter,
	.list-leave-to {
		opacity: 0;
	}

	.list-move {
		transition: transform 0.5s;
	}

	.fade-leave-active .fade-enter-active{
		transition: opacity 0.5s;
	}

	.fade-enter, .fade-leave-to {
		opacity: 0;
	}
}

input {
	background-repeat: no-repeat;
	background-position: 98%;

	&.error {
		border-color: var(--color-error);
		background-color: var(--color-background-error);
		background-image: var(--icon-polls-no);
	}

	&.success, &.icon-confirm.success {
		border-color: var(--color-success);
		background-image: var(--icon-polls-yes);
		background-color: var(--color-background-success) !important;
	}

	&.icon {
		flex: 0;
		padding: 0 17px;
	}
}

.label {
	border: solid 1px;
	border-radius: var(--border-radius);
	padding: 1px 4px;
	margin: 0 4px;
	font-size: 60%;
	text-align: center;

	&.error {
		border-color: var(--color-error);
		background-color: var(--color-error);
		color: var(--color-primary-text);
	}

	&.success {
		border-color: var(--color-success);
		background-color: var(--color-success);
		color: var(--color-primary-text);
	}
}

.modal__content {
	padding: 14px;
	display: flex;
	flex-direction: column;
	color: var(--color-main-text);
	input {
		width: 100%;
	}
}

.modal__buttons__spacer {
	flex: 1;
}

.modal__buttons {
	display: flex;
	justify-content: flex-end;
	align-items: center;
	.button {
		margin-left: 10px;
		margin-right: 0;
	}
}

.modal__buttons__link {
	text-decoration: underline;
}

.app-content {
	display: flex;
	flex-direction: column;
	padding: 0 8px;
	min-width: 320px;
}

// experimental colored background in the main area

[class*='area__'] {
	padding: 8px;
	background-color: var(--color-main-background);
	border-radius: var(--border-radius);
	margin: 12px 6px;
	min-width: 320px;
}

.experimental {
	&.app-polls.bgcolored {
		.app-navigation {
			border-right: 0px;
			box-shadow: 2px 0 6px var(--color-box-shadow);
		}
		.app-content {
			background-color: var(--color-primary-light);
			[class*='area__'] {
				box-shadow: 2px 2px 6px var(--color-box-shadow);
				margin: 12px;
			}
		}
	}

	// experimental background image
	&.app-polls.bgimage {
		.glassy {
			backdrop-filter: blur(10px);
			background-color: rgba(255, 255, 255, 0.5);
		}
		.app-navigation {
			border-right: 0px;
			box-shadow: 2px 0 6px var(--color-box-shadow);
		}
		.app-content {
			background-color: transparent;
		}
		[class*='area__'] {
			box-shadow: 2px 2px 6px var(--color-box-shadow);
			margin: 12px;
		}
	}
}

</style>

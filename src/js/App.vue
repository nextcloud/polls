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
	<Content app-name="polls">
		<Navigation v-if="OC.currentUser" />
		<router-view />
		<SideBar v-if="sideBarOpen && $store.state.poll.id" :active="activeTab" />
	</Content>
</template>

<script>
import Navigation from './components/Navigation/Navigation'
import { Content } from '@nextcloud/vue'
import SideBar from './components/SideBar/SideBar'
import { subscribe, unsubscribe } from '@nextcloud/event-bus'

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
		}
	},

	created() {
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

		if (OC.currentUser) {
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
			if (OC.currentUser) {

				this.$store.dispatch('loadPolls')
					.then(() => {
					})
					.catch((error) => {
						console.error('refresh poll: ', error.response)
						OC.Notification.showTemporary(t('polls', 'Error loading poll list'), { type: 'error' })
					})
			}
		},
	},
}

</script>

<style  lang="scss">
:root {
	--color-background-error: #f9c5c5;
	--color-background-success: #d6fdda;
	--color-polls-foreground-yes: #49bc49;
	--color-polls-foreground-no: #f45573;
	--color-polls-foreground-maybe: #ffc107;
	--color-polls-background-yes: #ebf5d6;
	--color-polls-background-no: #ffede9;
	--color-polls-background-maybe: #fcf7e1;
	--icon-polls-yes: url(data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIGhlaWdodD0iMTYiIHdpZHRoPSIxNiIgdmVyc2lvbj0iMS4xIiB2aWV3Qm94PSIwIDAgMTYgMTYiPjxwYXRoIGQ9Im0yLjM1IDcuMyA0IDRsNy4zLTcuMyIgc3Ryb2tlPSIjNDliYzQ5IiBzdHJva2Utd2lkdGg9IjIiIGZpbGw9Im5vbmUiLz48L3N2Zz4K);
	--icon-polls-handle: url(data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIGhlaWdodD0iMTYiIHdpZHRoPSIxNiI+DQogIDxwYXRoDQogICAgIGQ9Ik0yIDJ2MmgxMnYtMnptMCAzdjJoMTJ2LTJ6bTAgM3YyaDEydi0yem0wIDN2MmgxMnYtMnoiDQogICAgIHN0eWxlPSJmaWxsOiMwMDAwMDA7ZmlsbC1vcGFjaXR5OjE7c3Ryb2tlOm5vbmU7c3Ryb2tlLW9wYWNpdHk6MSIgLz4NCjwvc3ZnPg0K);
	--icon-polls-no: url(data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIGhlaWdodD0iMTYiIHdpZHRoPSIxNiIgdmVyc2lvbj0iMS4xIiB2aWV3Ym94PSIwIDAgMTYgMTYiPjxwYXRoIGQ9Im0xNCAxMi4zLTEuNyAxLjctNC4zLTQuMy00LjMgNC4zLTEuNy0xLjcgNC4zLTQuMy00LjMtNC4zIDEuNy0xLjcgNC4zIDQuMyA0LjMtNC4zIDEuNyAxLjctNC4zIDQuM3oiIGZpbGw9IiNmNDU1NzMiLz48L3N2Zz4K);
	--icon-polls-maybe: url(data:image/svg+xml;base64,PD94bWwgdmVyc2lvbj0iMS4wIiBlbmNvZGluZz0iVVRGLTgiIHN0YW5kYWxvbmU9Im5vIj8+CjxzdmcKICAgeG1sbnM6ZGM9Imh0dHA6Ly9wdXJsLm9yZy9kYy9lbGVtZW50cy8xLjEvIgogICB4bWxuczpjYz0iaHR0cDovL2NyZWF0aXZlY29tbW9ucy5vcmcvbnMjIgogICB4bWxuczpyZGY9Imh0dHA6Ly93d3cudzMub3JnLzE5OTkvMDIvMjItcmRmLXN5bnRheC1ucyMiCiAgIHhtbG5zOnN2Zz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciCiAgIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyIKICAgeG1sbnM6c29kaXBvZGk9Imh0dHA6Ly9zb2RpcG9kaS5zb3VyY2Vmb3JnZS5uZXQvRFREL3NvZGlwb2RpLTAuZHRkIgogICB4bWxuczppbmtzY2FwZT0iaHR0cDovL3d3dy5pbmtzY2FwZS5vcmcvbmFtZXNwYWNlcy9pbmtzY2FwZSIKICAgaWQ9InN2ZzQiCiAgIHZlcnNpb249IjEuMSIKICAgd2lkdGg9IjE2IgogICBoZWlnaHQ9IjE2IgogICBzb2RpcG9kaTpkb2NuYW1lPSJtYXliZS12b3RlLXZhcmlhbnQuc3ZnIgogICBpbmtzY2FwZTp2ZXJzaW9uPSIwLjkyLjIgKDVjM2U4MGQsIDIwMTctMDgtMDYpIj4KICA8c29kaXBvZGk6bmFtZWR2aWV3CiAgICAgcGFnZWNvbG9yPSIjZmZmZmZmIgogICAgIGJvcmRlcmNvbG9yPSIjNjY2NjY2IgogICAgIGJvcmRlcm9wYWNpdHk9IjEiCiAgICAgb2JqZWN0dG9sZXJhbmNlPSIxMCIKICAgICBncmlkdG9sZXJhbmNlPSIxMCIKICAgICBndWlkZXRvbGVyYW5jZT0iMTAiCiAgICAgaW5rc2NhcGU6cGFnZW9wYWNpdHk9IjAiCiAgICAgaW5rc2NhcGU6cGFnZXNoYWRvdz0iMiIKICAgICBpbmtzY2FwZTp3aW5kb3ctd2lkdGg9IjE5MjAiCiAgICAgaW5rc2NhcGU6d2luZG93LWhlaWdodD0iMTAxNyIKICAgICBpZD0ibmFtZWR2aWV3NiIKICAgICBzaG93Z3JpZD0iZmFsc2UiCiAgICAgaW5rc2NhcGU6em9vbT0iMTQuNzUiCiAgICAgaW5rc2NhcGU6Y3g9IjgiCiAgICAgaW5rc2NhcGU6Y3k9IjE0Ljg2NTIwMSIKICAgICBpbmtzY2FwZTp3aW5kb3cteD0iLTgiCiAgICAgaW5rc2NhcGU6d2luZG93LXk9Ii04IgogICAgIGlua3NjYXBlOndpbmRvdy1tYXhpbWl6ZWQ9IjEiCiAgICAgaW5rc2NhcGU6Y3VycmVudC1sYXllcj0ic3ZnNCI+CiAgICA8aW5rc2NhcGU6Z3JpZAogICAgICAgdHlwZT0ieHlncmlkIgogICAgICAgaWQ9ImdyaWQ4MzYiIC8+CiAgPC9zb2RpcG9kaTpuYW1lZHZpZXc+CiAgPG1ldGFkYXRhCiAgICAgaWQ9Im1ldGFkYXRhMTAiPgogICAgPHJkZjpSREY+CiAgICAgIDxjYzpXb3JrCiAgICAgICAgIHJkZjphYm91dD0iIj4KICAgICAgICA8ZGM6Zm9ybWF0PmltYWdlL3N2Zyt4bWw8L2RjOmZvcm1hdD4KICAgICAgICA8ZGM6dHlwZQogICAgICAgICAgIHJkZjpyZXNvdXJjZT0iaHR0cDovL3B1cmwub3JnL2RjL2RjbWl0eXBlL1N0aWxsSW1hZ2UiIC8+CiAgICAgICAgPGRjOnRpdGxlPjwvZGM6dGl0bGU+CiAgICAgIDwvY2M6V29yaz4KICAgIDwvcmRmOlJERj4KICA8L21ldGFkYXRhPgogIDxkZWZzCiAgICAgaWQ9ImRlZnM4IiAvPgogIDx0ZXh0CiAgICAgeG1sOnNwYWNlPSJwcmVzZXJ2ZSIKICAgICBzdHlsZT0iZm9udC1zdHlsZTpub3JtYWw7Zm9udC13ZWlnaHQ6bm9ybWFsO2ZvbnQtc2l6ZToxNS4wMDY0OTA3MXB4O2xpbmUtaGVpZ2h0OjEuMjU7Zm9udC1mYW1pbHk6c2Fucy1zZXJpZjtsZXR0ZXItc3BhY2luZzowcHg7d29yZC1zcGFjaW5nOjBweDtmaWxsOiNmZmMxMDc7ZmlsbC1vcGFjaXR5OjE7c3Ryb2tlOm5vbmU7c3Ryb2tlLXdpZHRoOjEuMDIzMTY5NzYiCiAgICAgeD0iLTAuODAzNjg5NDgiCiAgICAgeT0iMTIuNTU5MzEyIgogICAgIGlkPSJ0ZXh0ODE4IgogICAgIHRyYW5zZm9ybT0ic2NhbGUoMS4wOTAxOSwwLjkxNzI3MTMpIj48dHNwYW4KICAgICAgIHNvZGlwb2RpOnJvbGU9ImxpbmUiCiAgICAgICBpZD0idHNwYW44MTYiCiAgICAgICB4PSItMC44MDM2ODk0OCIKICAgICAgIHk9IjEyLjU1OTMxMiIKICAgICAgIHN0eWxlPSJmb250LXNpemU6MTQuNjY2NjY2OThweDtmaWxsOiNmZmMxMDc7ZmlsbC1vcGFjaXR5OjE7c3Ryb2tlLXdpZHRoOjEuMDIzMTY5NzYiPig8L3RzcGFuPjwvdGV4dD4KICA8dGV4dAogICAgIHhtbDpzcGFjZT0icHJlc2VydmUiCiAgICAgc3R5bGU9ImZvbnQtc3R5bGU6bm9ybWFsO2ZvbnQtd2VpZ2h0Om5vcm1hbDtmb250LXNpemU6NDAuOTI3MTQzMXB4O2xpbmUtaGVpZ2h0OjEuMjU7Zm9udC1mYW1pbHk6c2Fucy1zZXJpZjtsZXR0ZXItc3BhY2luZzowcHg7d29yZC1zcGFjaW5nOjBweDtmaWxsOiNmZmMxMDc7ZmlsbC1vcGFjaXR5OjE7c3Ryb2tlOm5vbmU7c3Ryb2tlLXdpZHRoOjEuMDIzMTc4NDYiCiAgICAgeD0iOS45NjMwNDQyIgogICAgIHk9IjEyLjQ3ODk0NSIKICAgICBpZD0idGV4dDgyOCIKICAgICB0cmFuc2Zvcm09InNjYWxlKDEuMDkwMTk5MywwLjkxNzI2MzQ4KSI+PHRzcGFuCiAgICAgICBzb2RpcG9kaTpyb2xlPSJsaW5lIgogICAgICAgaWQ9InRzcGFuODI2IgogICAgICAgeD0iOS45NjMwNDQyIgogICAgICAgeT0iMTIuNDc4OTQ1IgogICAgICAgc3R5bGU9ImZvbnQtc2l6ZToxNC42NjY2NjY5OHB4O2ZpbGw6I2ZmYzEwNztmaWxsLW9wYWNpdHk6MTtzdHJva2Utd2lkdGg6MS4wMjMxNzg0NiI+KTwvdHNwYW4+PC90ZXh0PgogIDxwYXRoCiAgICAgaW5rc2NhcGU6Y29ubmVjdG9yLWN1cnZhdHVyZT0iMCIKICAgICBkPSJtIDExLjkyNCw0LjA2NTk5OTIgLTQuOTMyMDAwMSw0Ljk3IC0yLjgyOCwtMi44MyBMIDIuNzUsNy42MTc5OTkyIDYuOTkxOTk5OSwxMS44NjEgMTMuMzU3LDUuNDk1OTk5MiBsIC0xLjQzMywtMS40MzIgeiIKICAgICBpZD0icGF0aDgxNiIKICAgICBzdHlsZT0iZmlsbDojZmZjMTA3O2ZpbGwtb3BhY2l0eToxIiAvPgo8L3N2Zz4K);

	// filters to colorize background svg from black
	// generated with https://codepen.io/jsm91/embed/ZEEawyZ?height=600&default-tab=result&embed-version=2
	--color-polls-foreground-filter-yes: invert(74%) sepia(7%) saturate(3830%) hue-rotate(68deg) brightness(85%) contrast(85%);
	--color-polls-foreground-filter-no: invert(43%) sepia(100%) saturate(1579%) hue-rotate(318deg) brightness(99%) contrast(94%);
	--color-polls-foreground-filter-maybe: invert(81%) sepia(22%) saturate(3383%) hue-rotate(353deg) brightness(101%) contrast(101%);
}

// .main-container {
// 	flex: 1;
// 	overflow-x: scroll;
// }

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

.fade-leave-active {
	transition: opacity 2.5s;
}

.fade-enter, .fade-leave-to {
	opacity: 0;
}

.app-content {
	padding: 8px 24px 8px 36px;
	overflow: hidden;
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

.config-box {
	display: flex;
	flex-direction: column;
	// padding: 8px;
	& > * {
		margin-left: 21px;
	}

	& > input {
		margin-left: 24px;
		width: auto;

	}

	& > textarea {
		// margin-left: 24px;
		width: auto;
		padding: 7px 6px;
	}

	& > .title {
		display: flex;
		background-position: 0 4px;
		// padding-left: 24px;
		opacity: 0.7;
		font-weight: bold;
		margin-bottom: 4px;
		margin-left: 0;
		padding-left: 24px;
		& > span {
			padding-left: 4px;
		}
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

</style>

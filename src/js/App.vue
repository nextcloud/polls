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
	<div id="app-polls">
		<Navigation v-if="OC.currentUser" />
		<router-view />
	</div>
</template>

<script>
import Navigation from './components/Navigation/Navigation'

export default {
	name: 'App',
	components: {
		Navigation
	},

	created() {
		if (OC.currentUser) {
			this.updatePolls()
			this.$root.$on('updatePolls', () => {
				this.updatePolls()
			})
		}
	},

	methods: {
		updatePolls() {
			if (OC.currentUser) {

				this.$store
					.dispatch('loadPolls')
					.then(() => {
					})
					.catch((error) => {
						console.error('refresh poll: ', error.response)
						OC.Notification.showTemporary(t('polls', 'Error loading polls'), { type: 'error' })
					})
			}
		}
	}
}

</script>

<style  lang="scss">
.main-container {
	position: relative;
	flex: 1;
	padding: 8px 24px;
	margin: 0;
	flex-direction: column;
	flex-wrap: nowrap;
	overflow-x: scroll;
}

.title {
	margin: 8px 0;
}

.description {
	white-space: break-spaces;
	margin: 8px 0;
}

.poll-item {
	display: flex;
	align-items: center;
	padding-left: 8px;
	padding-right: 8px;
	line-height: 2em;
	min-height: 4em;
	overflow: visible;
	white-space: nowrap;

	&:active,
	&:hover {
		transition: var(--background-dark) 0.3s ease;
		background-color: var(--color-background-dark);
	}

	> div {
		display: flex;
		flex: 1;
		font-size: 1.2em;
		opacity: 1;
		white-space: normal;
		padding-right: 4px;
		&.avatar {
			flex: 0;
		}
	}

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

#app-polls {
	width: 100%;
	color: var(--color-main-text)
}

#app-content {
	display: flex;
	width: auto;

	input {
		&.hasTimepicker {
			width: 75px;
		}
		&.error {
			border-color: var(--color-error);
			background-color: #f9c5c5;
			background-image: var(--icon-error-e9322d);
			background-repeat: no-repeat;
			background-position: right;
		}
		&.success, &.icon-confirn.success {
			border-color: var(--color-success);
			background-color: #d6fdda !important;
			&.icon-confirm {
				border-color: var(--color-success) !important;
				border-left-color: transparent !important;
			}
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
}

.config-box {
	display: flex;
	flex-direction: column;
	padding: 8px;
	& > * {
		padding-left: 21px;
	}

	& > input {
		margin-left: 24px;
		width: auto;

	}

	& > textarea {
		margin-left: 24px;
		width: auto;
		padding: 7px 6px;
	}

	& > .title {
		display: flex;
		background-position: 0 2px;
		padding-left: 24px;
		opacity: 0.7;
		font-weight: bold;
		margin-bottom: 4px;
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

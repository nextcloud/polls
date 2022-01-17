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
	<div :class="['user-item', type, { disabled, 'condensed' : condensed }]">
		<Avatar :disable-menu="disableMenu"
			:disable-tooltip="disableTooltip"
			class="user-item__avatar"
			:is-guest="isGuestComputed"
			:menu-position="menuPosition"
			:size="iconSize"
			:icon-class="avatarIcon"
			:show-user-status="showUserStatus"
			:user="userId"
			:display-name="name"
			:is-no-user="isNoUser" />

		<div v-if="icon" :class="['type-icon', iconClass]" />

		<slot name="status" />

		<div v-if="!hideNames" class="user-item__name">
			<div class="name">
				{{ name }}
			</div>
			<div v-if="type === 'admin'" class="description">
				{{ t('polls', 'Is granted admin rights for this poll') }}
			</div>
			<div v-else-if="displayEmailAddress" class="description">
				{{ displayEmailAddress }}
			</div>
		</div>

		<slot />
	</div>
</template>

<script>
import { getCurrentUser } from '@nextcloud/auth'
import { Avatar } from '@nextcloud/vue'

export default {
	name: 'UserItem',

	components: {
		Avatar,
	},

	inheritAttrs: false,

	props: {
		disabled: {
			type: Boolean,
			default: false,
		},
		hideNames: {
			type: Boolean,
			default: false,
		},
		showEmail: {
			type: Boolean,
			default: false,
		},
		disableMenu: {
			type: Boolean,
			default: true,
		},
		disableTooltip: {
			type: Boolean,
			default: false,
		},
		resolveInfo: {
			type: Boolean,
			default: false,
		},
		menuPosition: {
			type: String,
			default: 'left',
		},
		userId: {
			type: String,
			default: undefined,
		},
		displayName: {
			type: String,
			default: undefined,
		},
		emailAddress: {
			type: String,
			default: '',
		},
		type: {
			type: String,
			default: 'user',
		},
		isNoUser: {
			type: Boolean,
			default: false,
		},
		isGuest: {
			type: Boolean,
			default: false,
		},
		icon: {
			type: Boolean,
			default: false,
		},
		iconSize: {
			type: Number,
			default: 32,
		},
		condensed: {
			type: Boolean,
			default: false,
		},
	},

	computed: {
		isGuestComputed() {
			return this.$route?.name === 'publicVote' ? true : this.isGuest
		},
		name() {
			if (this.type === 'public') {
				return t('polls', 'Public link')
			}

			if (this.type === 'all') {
				return t('polls', 'All users')
			}

			if (this.displayName) {
				return this.displayName
			}

			return this.userId

		},

		displayEmailAddress() {
			if (this.type === 'public') {
				if (this.userId === 'addPublic') {
					return t('polls', 'Add a new public link')
				}
				return t('polls', 'Token: {token}', { token: this.userId })
			}

			if (this.type === 'all') {
				if (this.disabled) {
					return t('polls', 'Access for all users of this site is disabled')
				}
				return t('polls', 'Access for all users of this site is enabled')
			}

			if (this.resolveInfo && ['contactGroup', 'circle'].includes(this.type)) {
				return t('polls', 'Resolve this group first!')
			}

			if (this.showEmail && ['external', 'email'].includes(this.type) && this.emailAddress !== this.name) {
				return this.emailAddress
			}
			return ''
		},

		showUserStatus() {
			return Boolean(getCurrentUser())
		},

		avatarIcon() {
			if (this.type === 'public') {
				return 'icon-public'
			}

			if (this.type === 'all') {
				return 'icon-public'
			}

			if (this.type === 'contact') {
				return 'icon-mail'
			}

			if (this.type === 'email') {
				return 'icon-mail'
			}

			if (this.type === 'external') {
				return 'icon-share'
			}

			if (this.type === 'contactGroup') {
				return 'icon-group'
			}

			if (this.type === 'group') {
				return 'icon-group'
			}

			if (this.type === 'circle') {
				return 'icon-circles'
			}

			return ''
		},

		iconClass() {
			if (this.icon) {
				if (this.type === 'admin') {
					return 'icon-user-admin'
				}
			}
			return ''
		},

	},
}

</script>

<style lang="scss">

.avatar-class-icon {
	border-radius: 50%;
	background-color: var(--color-primary-element) !important;
	height: 100%;
	background-size: 16px;
}

.user-item {
	position: relative;
	display: flex;
	align-items: center;
	padding: 4px;
	max-width: 100%;
	&.disabled {
		opacity: 0.6;
	}
}

.user-item__name {
	flex: 1;
	min-width: 50px;
	padding-left: 8px;
	white-space: nowrap;
	> div {
		overflow: hidden;
		text-overflow: ellipsis;
	}
	.description {
		color: var(--color-text-maxcontrast);
		font-size: 0.7em;
	}
}

.condensed {
	&.user-item {
		flex-direction: column;
		justify-content: center;
		max-width: 70px;
	}
	.user-item__name {
		font-size: 0.7em;
		text-align: center;
		width: 70px;
		max-width: 70px;
		padding: 0 4px;
	}
}

.type-icon {
	background-size: 16px;
	position: absolute;
	left: 0;
	top: 3px;
}

</style>

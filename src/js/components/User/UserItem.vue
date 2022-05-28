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
	<div :class="['user-item', type, { disabled, condensed: condensed }]">
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

		<AdminIcon v-if="icon && type === 'admin'" :size="16" class="type-icon" />

		<slot name="status" />

		<div v-if="!hideNames" class="user-item__name">
			<div class="name">
				{{ name }}
			</div>
			<div v-if="type === 'admin'" class="description">
				{{ t("polls", "Is granted admin rights for this poll") }}
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
		AdminIcon: () => import('../AppIcons/ShieldCrownOutline.vue'),
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
			validator(value) {
				return [
					'public',
					'internalAccess',
					'user',
					'admin',
					'group',
					'contact',
					'contactGroup',
					'circle',
					'external',
					'email',
				].includes(value)
			},

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
			return this.$route?.name === 'publicVote' || this.isGuest || this.isNoUser
		},

		name() {
			if (this.type === 'public' && this.userId !== 'addPublic') {
				return t('polls', 'Public link')
			}

			if (this.type === 'internalAccess') {
				return t('polls', 'Internal access')
			}

			if (this.displayName) {
				return this.displayName
			}

			return this.userId

		},

		displayEmailAddress() {
			if (this.type === 'public' && this.userId !== 'addPublic') {
				return t('polls', 'Token: {token}', { token: this.userId })
			}

			if (this.type === 'internalAccess') {
				if (this.disabled) {
					return t('polls', 'This poll is private')
				}
				return t('polls', 'This is an openly accessible poll')
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
				return 'icon-svg-md-link'
			}

			if (this.type === 'internalAccess') {
				return 'icon-svg-md-link'
			}

			if (this.type === 'contact') {
				return 'icon-svg-md-email'
			}

			if (this.type === 'email') {
				return 'icon-svg-md-email'
			}

			if (this.type === 'external') {
				return 'icon-svg-md-share'
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

.type-icon {
	position: absolute;
	background-size: 16px;
	top: 3px;
	left: 0;
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

</style>

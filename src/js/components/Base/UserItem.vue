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
	<div class="user-item" :class="type">
		<Avatar :disable-menu="disableMenu"
			class="user-item__avatar"
			:menu-position="menuPosition"
			:user="userId"
			:is-guest="!Boolean(getCurrentUser())"
			:display-name="resolveDisplayName"
			:is-no-user="isNoUser" />

		<div v-if="icon" :class="iconClass" />

		<div v-if="!hideNames" class="user-item__name">
			{{ resolveDisplayName }}
		</div>
		<slot />
	</div>
</template>

<script>
import { Avatar } from '@nextcloud/vue'

export default {
	name: 'UserItem',

	components: {
		Avatar,
	},

	inheritAttrs: false,

	props: {
		hideNames: {
			type: Boolean,
			default: false,
		},
		disableMenu: {
			type: Boolean,
			default: true,
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
			default: '',
		},
		userEmail: {
			type: String,
			default: '',
		},
		type: {
			type: String,
			default: 'user',
		},
		icon: {
			type: Boolean,
			default: false,
		},
	},

	data() {
		return {
			nothidden: false,
		}
	},

	computed: {
		isNoUser() {
			return this.type !== 'user'
		},

		iconClass() {
			if (this.icon) {
				if (this.type === 'contact') {
					return 'icon-mail'
				} else if (this.type === 'email') {
					return 'icon-mail'
				} else if (this.type === 'external') {
					return 'icon-share'
				}
				return 'icon-' + this.type
			} else {
				return ''
			}
		},

		resolveDisplayName() {
			let displayName = ''

			if (this.type === 'user') {
				displayName = this.displayName
			} else if (this.type === 'contact' || this.type === 'external') {
				displayName = this.userId
				if (this.userEmail) {
					displayName = displayName + ' (' + this.userEmail + ')'
				}
			} else if (this.type === 'email') {
				displayName = this.userEmail
				if (this.userId) {
					displayName = this.userId + ' (' + displayName + ')'
				}
			} else if (this.type === 'group') {
				displayName = this.userId + ' (' + t('polls', 'Group') + ')'
			} else if (this.type === 'public') {
				displayName = t('polls', 'Public share')
			} else {
				displayName = t('polls', 'Unknown user')
			}
			return displayName
		},
	},
}

</script>

<style lang="scss" scoped>
.user-item {
	display: flex;
	flex: 1;
	align-items: center;
	max-width: 100%;
}

.user-item__avatar {
	margin: 2px 4px;
}

.user-item__name {
	flex: 1;
	min-width: 50px;
	color: var(--color-text-maxcontrast);
	padding-left: 8px;
	white-space: nowrap;
	overflow: hidden;
	text-overflow: ellipsis;
}

@media (max-width: 576px) {
	.user-item__name {
		display: none;
	}
}
</style>

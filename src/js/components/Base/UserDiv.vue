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
	<div class="user-row" :class="type">
		<Avatar :disable-menu="disableMenu" :menu-position="menuPosition" :user="userId"
			:is-guest="!Boolean(getCurrentUser())"
			:display-name="displayName"
			:is-no-user="isNoUser" />

		<div v-if="icon" class="avatar" :class="iconClass" />

		<div v-if="!hideNames" class="user-name">
			{{ displayName }}
		</div>
		<slot />
	</div>
</template>

<script>
import { Avatar } from '@nextcloud/vue'

export default {
	name: 'UserDiv',

	components: {
		Avatar,
	},

	props: {
		hideNames: {
			type: Boolean,
			default: false,
		},
		disableMenu: {
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

		isValidUser() {
			return (this.userId)
		},

		iconClass() {
			if (this.icon) {
				if (this.type === 'contact') {
					return 'icon-mail'
				} else if (this.type === 'email') {
					return 'icon-mail'
				}
				return 'icon-' + this.type
			} else {
				return ''
			}
		},
	},
}

</script>

<style lang="scss">
.user-row {
	display: flex;
	flex: 1;
	align-items: center;
	margin-left: 0;
	margin-top: 0;

	> div {
		margin: 2px 4px;
	}

	.description {
		opacity: 0.7;
		flex: 0;
	}

	.icon-class {
		height: 32px;
		width: 32px;
		flex: 0;
	}

	.user-name {
		opacity: 0.5;
		flex: 1;
		white-space: nowrap;
		overflow: hidden;
		text-overflow: ellipsis;
	}
}
</style>

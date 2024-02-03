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
	<div :class="['user-item', typeComputed, { disabled, condensed: condensed }]">
		<div class="avatar-wrapper">
			<NcAvatar :disable-menu="disableMenu"
				:disable-tooltip="disableTooltip"
				class="user-item__avatar"
				:is-guest="isGuestComputed"
				:menu-position="menuPosition"
				:size="iconSize"
				:show-user-status="showUserStatus"
				:user="avatarUserId"
				:display-name="displayName"
				:is-no-user="user.isNoUser"
				@click="showMenu()">
				<template v-if="useIconSlot" #icon>
					<LinkIcon v-if="typeComputed === 'public'" :size="mdIconSize" />
					<InternalLinkIcon v-if="typeComputed === 'internalAccess'" :size="mdIconSize" />
					<ContactGroupIcon v-if="typeComputed === 'contactGroup'" :size="mdIconSize" />
					<GroupIcon v-if="typeComputed === 'group'" :size="mdIconSize" />
					<CircleIcon v-if="typeComputed === 'circle'" :size="mdIconSize" />
					<DeletedUserIcon v-if="typeComputed === 'deleted'" :size="mdIconSize" />
				</template>
			</NcAvatar>

			<AdminIcon v-if="typeComputed === 'admin' && showTypeIcon" :size="16" class="type-icon" />
			<ContactIcon v-if="typeComputed === 'contact' && showTypeIcon" :size="16" class="type-icon" />
			<EmailIcon v-if="typeComputed === 'email' && showTypeIcon" :size="16" class="type-icon" />
			<ShareIcon v-if="typeComputed === 'external' && showTypeIcon" :size="16" class="type-icon" />
		</div>

		<slot name="status" />

		<div v-if="!hideNames" class="user-item__name">
			<div class="name">
				{{ displayName }}
			</div>
			<div class="description">
				{{ description }}
			</div>
		</div>

		<slot />
	</div>
</template>

<script>
import { getCurrentUser } from '@nextcloud/auth'
import { NcAvatar } from '@nextcloud/vue'

export default {
	name: 'UserItem',

	components: {
		NcAvatar,
		AdminIcon: () => import('vue-material-design-icons/ShieldCrown.vue'),
		LinkIcon: () => import('vue-material-design-icons/LinkVariant.vue'),
		InternalLinkIcon: () => import('vue-material-design-icons/LinkVariant.vue'),
		ContactIcon: () => import('vue-material-design-icons/CardAccountDetails.vue'),
		EmailIcon: () => import('vue-material-design-icons/Email.vue'),
		ShareIcon: () => import('vue-material-design-icons/ShareVariant.vue'),
		ContactGroupIcon: () => import('vue-material-design-icons/AccountGroupOutline.vue'),
		GroupIcon: () => import('vue-material-design-icons/AccountMultiple.vue'),
		CircleIcon: () => import('vue-material-design-icons/GoogleCirclesExtended.vue'),
		DeletedUserIcon: () => import('vue-material-design-icons/AccountOff.vue'),
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
		forcedDescription: {
			type: String,
			default: null,
		},
		label: {
			type: String,
			default: null,
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
					'deleted',
				].includes(value)
			},

		},
		user: {
			type: Object,
			default() {
				return {
					userId: '',
					displayName: '',
					emailAddress: '',
					isNoUser: false,
					type: null,
				}
			},
		},
		showTypeIcon: {
			type: Boolean,
			default: false,
		},
		iconSize: {
			type: Number,
			default: 32,
		},
		mdIconSize: {
			type: Number,
			default: 20,
		},
		condensed: {
			type: Boolean,
			default: false,
		},
	},

	computed: {
		isGuestComputed() {
			return this.$route?.name === 'publicVote' || this.user.isNoUser
		},

		useIconSlot() {
			return ['internalAccess', 'public', 'contactGroup', 'group', 'circle', 'deleted'].includes(this.typeComputed)
		},
		typeComputed() {
			return this.user.type ?? this.type
		},
		description() {
			if (this.condensed) return ''
			if (this.forcedDescription) return this.forcedDescription
			if (this.typeComputed === 'deleted') return t('polls', 'The participant got removed from this poll')
			if (this.typeComputed === 'admin') return t('polls', 'Is granted admin rights for this poll')
			if (this.displayEmailAddress) return this.displayEmailAddress
			return ''
		},

		displayName() {
			if (this.typeComputed === 'deleted') return t('polls', 'Deleted User')
			if (this.typeComputed === 'internalAccess') return t('polls', 'Internal access')
			if (this.user.displayName) return this.user.displayName
			if (this.typeComputed === 'public' && this.user.userId !== 'addPublic') return this.label ?? t('polls', 'Public link')
			return this.user.userId
		},

		avatarUserId() {
			if (this.isGuestComputed) return this.user.displayName
			return this.user.userId
		},

		displayEmailAddress() {
			if (this.typeComputed === 'public' && this.user.userId !== 'addPublic') {
				if (!this.user.displayName) {
					return t('polls', 'Token: {token}', { token: this.user.userId })
				}
				return t('polls', 'Public link: {token}', { token: this.user.userId })
			}

			if (this.typeComputed === 'internalAccess') {
				if (this.disabled) {
					return t('polls', 'This poll is private')
				}
				return t('polls', 'This is an openly accessible poll')
			}

			if (this.resolveInfo && ['contactGroup', 'circle'].includes(this.typeComputed)) {
				return t('polls', 'Resolve this group first!')
			}

			if (this.showEmail && ['external', 'email'].includes(this.typeComputed) && this.user.emailAddress !== this.user.displayName) {
				return this.user.emailAddress
			}

			return ''
		},

		showUserStatus() {
			return Boolean(getCurrentUser())
		},
	},
}

</script>

<style lang="scss">
.avatar-wrapper {
	position: relative;
	.type-icon {
		position: absolute;
		background-size: 16px;
		top: -6px;
		left: -6px;
	}
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

.user-item__avatar .material-design-icon {
	background-color: var(--color-primary-element);
	border-radius: 50%;
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

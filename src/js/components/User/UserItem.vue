<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<div :class="['user-item', typeComputed, { disabled, condensed: condensed }]">
		<div class="avatar-wrapper">
			<NcAvatar v-bind="avatarProps" class="user-item__avatar" @click="showMenu()">
				<template v-if="useIconSlot" #icon>
					<LinkIcon v-if="typeComputed === 'public'" :size="mdIconSize" />
					<LinkIcon v-if="typeComputed === 'addPublicLink'" :size="mdIconSize" />
					<AnoymousIcon v-if="typeComputed === 'anonymous'" :size="mdIconSize" />
					<LinkIcon v-if="typeComputed === 'internalAccess'" :size="mdIconSize" />
					<ContactGroupIcon v-if="typeComputed === 'contactGroup'" :size="mdIconSize" />
					<GroupIcon v-if="typeComputed === 'group'" :size="mdIconSize" />
					<CircleIcon v-if="typeComputed === 'circle'" :size="mdIconSize" />
					<DeletedUserIcon v-if="typeComputed === 'deleted'" :size="mdIconSize" />
				</template>
			</NcAvatar>

			<AdminIcon v-if="typeComputed === 'admin' && showTypeIcon" :size="typeIconSize" class="type-icon" />
			<ContactIcon v-if="typeComputed === 'contact' && showTypeIcon" :size="typeIconSize" class="type-icon" />
			<EmailIcon v-if="typeComputed === 'email' && showTypeIcon" :size="typeIconSize" class="type-icon" />
			<ShareIcon v-if="typeComputed === 'external' && showTypeIcon" :size="typeIconSize" class="type-icon" />
		</div>

		<slot name="status" />

		<div v-if="!hideNames" class="user-item__name">
			<div class="name" :title="labelComputed">
				{{ labelComputed }}
			</div>
			<div class="description">
				{{ descriptionComputed }}
			</div>
		</div>

		<slot />
	</div>
</template>

<script>
import { NcAvatar } from '@nextcloud/vue'
import AdminIcon from 'vue-material-design-icons/ShieldCrown.vue'
import LinkIcon from 'vue-material-design-icons/LinkVariant.vue'
import ContactIcon from 'vue-material-design-icons/CardAccountDetails.vue'
import EmailIcon from 'vue-material-design-icons/Email.vue'
import ShareIcon from 'vue-material-design-icons/ShareVariant.vue'
import ContactGroupIcon from 'vue-material-design-icons/AccountGroupOutline.vue'
import GroupIcon from 'vue-material-design-icons/AccountMultiple.vue'
import CircleIcon from 'vue-material-design-icons/GoogleCirclesExtended.vue'
import DeletedUserIcon from 'vue-material-design-icons/AccountOff.vue'
import AnoymousIcon from 'vue-material-design-icons/Incognito.vue'


export default {
	name: 'UserItem',

	components: {
		NcAvatar,
		AdminIcon,
		LinkIcon,
		ContactIcon,
		EmailIcon,
		ShareIcon,
		ContactGroupIcon,
		GroupIcon,
		CircleIcon,
		DeletedUserIcon,
		AnoymousIcon,
	},

	inheritAttrs: false,

	props: {
		disabled: {
			type: Boolean,
			default: false,
		},
		deletedState: {
			type: Boolean,
			default: false,
		},
		lockedState: {
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
		description: {
			type: String,
			default: '',
		},
		label: {
			type: String,
			default: '',
		},
		type: {
			type: String,
			default: '',
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
					'addPublicLink',
					'',
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
					isNoUser: true,
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
		typeIconSize: {
			type: Number,
			default: 16,
		},
		hideStatus: {
			type: Boolean,
			default: false,
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

		avatarProps() {
			return {
				disableMenu: this.disableMenu,
				disableTooltip: this.disableTooltip,
				isGuest: this.isGuestComputed,
				menuPosition: this.menuPosition,
				size: this.iconSize,
				hideStatus: this.hideStatus,
				user: this.avatarUserId,
				displayName: this.displayName,
				isNoUser: this.user.isNoUser,
			}
		},

		useIconSlot() {
			return [
				'internalAccess',
				'public',
				'addPublicLink',
				'contactGroup',
				'group',
				'circle',
				'deleted',
				'anonymous',
			].includes(this.typeComputed)
		},

		typeComputed() {
			return this.user.type ?? this.type
		},

		descriptionComputed() {
			if (this.condensed) return ''
			if (this.deletedState) return t('polls', '(deleted)')
			if (this.lockedState) return t('polls', '(locked)')
			if (this.description !== '') return this.description
			if (this.typeComputed === 'public') return this.publicShareDescription
			if (this.typeComputed === 'deleted') return t('polls', 'The participant got removed from this poll')
			if (this.typeComputed === 'admin') return t('polls', 'Is granted admin rights for this poll')
			if (this.typeComputed === 'anonymous') return t('polls', 'Anonymized participant')
			return this.emailAddressComputed
		},

		labelComputed() {
			if (this.label !== '') return this.label
			if (this.typeComputed === 'public') return this.publicShareLabel
			if (this.typeComputed === 'deleted') return t('polls', 'Deleted participant')
			return this.user.displayName ?? this.user.userId
		},

		avatarUserId() {
			if (this.isGuestComputed) return this.user.displayName
			return this.user.userId
		},

		publicShareDescription() {
			if (this.label === '') {
				return t('polls', 'Token: {token}', { token: this.user.userId })
			}
			return t('polls', 'Public link: {token}', { token: this.user.userId })
		},

		publicShareLabel() {
			if (this.label === '') {
				return t('polls', 'Public link')
			}
			return this.label
		},

		emailAddressComputed() {
			if (this.resolveInfo && ['contactGroup', 'circle'].includes(this.typeComputed)) {
				return t('polls', 'Resolve this group first!')
			}

			if (this.showEmail && ['external', 'email'].includes(this.typeComputed) && this.user.emailAddress !== this.user.displayName) {
				return this.user.emailAddress
			}

			return ''
		},
	},
}

</script>

<style lang="scss">
.avatar-wrapper {
	position: relative;
	display: flex;
	.type-icon {
		position: absolute;
		background-size: 16px;
		top: -6px;
		inset-inline-start: -6px;
	}
}

.user-item {
	position: relative;
	display: flex;
	align-items: center;
	justify-content: space-around;
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
	padding-inline-start: 8px;
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

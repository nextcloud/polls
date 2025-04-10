<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import { computed, type PropType } from 'vue'
import { useRoute } from 'vue-router'
import { getCurrentUser } from '@nextcloud/auth'
import { t } from '@nextcloud/l10n'

import NcAvatar from '@nextcloud/vue/components/NcAvatar'
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

import { User, UserType, VirtualUserItemType } from '../../Types/index.ts'

const route = useRoute()

defineOptions({
	inheritAttrs: true,
})

const props = defineProps({
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
	description: {
		type: String,
		default: '',
	},
	label: {
		type: String,
		default: '',
	},
	type: {
		type: String as PropType<UserType | VirtualUserItemType>,
		default: UserType.None,
		validator(value: UserType | VirtualUserItemType) {
			return [
				UserType.Public,
				UserType.User,
				UserType.Admin,
				UserType.Group,
				UserType.Contact,
				UserType.ContactGroup,
				UserType.Circle,
				UserType.External,
				UserType.Email,
				UserType.None,
				VirtualUserItemType.InternalAccess,
				VirtualUserItemType.Deleted,
				VirtualUserItemType.AddPublicLink,
			].includes(value)
		},
	},
	user: {
		type: Object as PropType<User>,
		default() {
			return {
				userId: '',
				displayName: '',
				emailAddress: '',
				isNoUser: true,
				type: UserType.None,
				subName: null,
				subtitle: null,
				desc: null,
				organisation: null,
				languageCode: null,
				localeCode: null,
				timeZone: null,
				categories: null,
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
})

const isGuestComputed = computed(
	() => route.name === 'publicVote' || props.user.isNoUser,
)
const avatarProps = computed(() => ({
	user: avatarUserId.value,
	showUserStatus: showUserStatusComputed.value,
	isGuest: isGuestComputed.value,
	displayName: labelComputed.value,
	size: props.iconSize,
	disableTooltip: props.disableTooltip,
	disableMenu: props.disableMenu,
	isNoUser: props.user.isNoUser,
}))

const useIconSlot = computed(() =>
	[
		VirtualUserItemType.InternalAccess,
		VirtualUserItemType.AddPublicLink,
		UserType.Public,
		UserType.ContactGroup,
		UserType.Group,
		UserType.Circle,
		VirtualUserItemType.Deleted,
		VirtualUserItemType.Anonymous,
	].includes(typeComputed.value),
)

const typeComputed = computed<UserType | VirtualUserItemType>(
	() => props.user.type ?? props.type,
)
const descriptionComputed = computed(() => {
	if (props.condensed) {
		return ''
	}
	if (props.deletedState) {
		return t('polls', '(deleted)')
	}
	if (props.lockedState) {
		return t('polls', '(locked)')
	}
	if (props.description !== '') {
		return props.description
	}
	if (typeComputed.value === UserType.Public) {
		return publicShareDescription
	}
	if (typeComputed.value === VirtualUserItemType.Deleted) {
		return t('polls', 'The participant got removed from this poll')
	}
	if (typeComputed.value === UserType.Admin) {
		return t('polls', 'Is granted admin rights for this poll')
	}
	if (typeComputed.value === VirtualUserItemType.Anonymous) {
		return t('polls', 'Anonymized participant')
	}
	return emailAddressComputed
})
const labelComputed = computed(() => {
	if (props.label !== '') {
		return props.label
	}
	if (typeComputed.value === UserType.Public) {
		return publicShareLabel.value
	}
	if (typeComputed.value === VirtualUserItemType.Deleted) {
		return t('polls', 'Deleted participant')
	}
	return props.user.displayName ?? props.user.id
})

const avatarUserId = computed(() => {
	if (isGuestComputed.value) {
		return props.user.displayName
	}
	return props.user.id
})

const publicShareDescription = computed(() => {
	if (props.label === '') {
		return t('polls', 'Token: {token}', { token: props.user.id })
	}
	return t('polls', 'Public link: {token}', { token: props.user.id })
})

const publicShareLabel = computed(() => {
	if (props.label === '') {
		return t('polls', 'Public link')
	}
	return props.label
})

const emailAddressComputed = computed(() => {
	if (
		props.resolveInfo
		&& (typeComputed.value === UserType.ContactGroup
			|| typeComputed.value === UserType.Circle)
	) {
		return t('polls', 'Resolve this group first!')
	}

	if (
		props.showEmail
		&& props.user.emailAddress !== props.user.displayName
		&& (typeComputed.value === UserType.External
			|| typeComputed.value === UserType.Email)
	) {
		return props.user.emailAddress
	}

	return ''
})
const showUserStatusComputed = computed(
	() => props.hideStatus && Boolean(getCurrentUser()),
)

/**
 *
 */
function showMenu() {
	// TODO: implement
	return true
}
</script>

<template>
	<div
		:class="[
			'user-item',
			typeComputed,
			{
				disabled,
				condensed: props.condensed,
			},
		]">
		<div class="avatar-wrapper">
			<NcAvatar
				v-bind="avatarProps"
				class="user-item__avatar"
				@click="showMenu()">
				<template v-if="useIconSlot" #icon>
					<LinkIcon
						v-if="typeComputed === UserType.Public"
						:size="props.mdIconSize" />
					<LinkIcon
						v-if="typeComputed === VirtualUserItemType.AddPublicLink"
						:size="props.mdIconSize" />
					<AnoymousIcon
						v-if="typeComputed === VirtualUserItemType.Anonymous"
						:size="props.mdIconSize" />
					<LinkIcon
						v-if="typeComputed === VirtualUserItemType.InternalAccess"
						:size="props.mdIconSize" />
					<ContactGroupIcon
						v-if="typeComputed === UserType.ContactGroup"
						:size="props.mdIconSize" />
					<GroupIcon
						v-if="typeComputed === UserType.Group"
						:size="props.mdIconSize" />
					<CircleIcon
						v-if="typeComputed === UserType.Circle"
						:size="props.mdIconSize" />
					<DeletedUserIcon
						v-if="typeComputed === VirtualUserItemType.Deleted"
						:size="props.mdIconSize" />
				</template>
			</NcAvatar>

			<AdminIcon
				v-if="showTypeIcon && typeComputed === UserType.Admin"
				:size="props.typeIconSize"
				class="type-icon" />
			<ContactIcon
				v-if="showTypeIcon && typeComputed === UserType.Contact"
				:size="props.typeIconSize"
				class="type-icon" />
			<EmailIcon
				v-if="showTypeIcon && typeComputed === UserType.Email"
				:size="props.typeIconSize"
				class="type-icon" />
			<ShareIcon
				v-if="showTypeIcon && typeComputed === UserType.Email"
				:size="props.typeIconSize"
				class="type-icon" />
		</div>

		<slot name="status" />

		<div v-if="!props.hideNames" class="user-item__name">
			<div class="name">
				{{ labelComputed }}
			</div>
			<div class="description">
				{{ descriptionComputed }}
			</div>
		</div>

		<slot />
	</div>
</template>

<style lang="scss">
.avatar-wrapper {
	position: relative;
	display: flex;
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

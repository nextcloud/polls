<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import { computed } from 'vue'
import { t } from '@nextcloud/l10n'

import { createDefault, User, UserType, VirtualUserItemType } from '../../Types'
import UserAvatar from './UserAvatar.vue'

defineOptions({ inheritAttrs: false })

interface Props {
	condensed?: boolean
	description?: string
	disabled?: boolean
	hideNames?: boolean
	showEmail?: boolean
	tag?: string
	virtualUserType?: VirtualUserItemType
	user?: User
	itemStyle?: Record<string, string | number>
}

const {
	condensed = false,
	description,
	disabled = false,
	hideNames = false,
	showEmail = false,
	tag = 'div',
	virtualUserType,
	user = createDefault<User>(),
	itemStyle = {},
} = defineProps<Props>()

const computedRoleType = computed<UserType | VirtualUserItemType>(
	() => user.type ?? virtualUserType,
)

const computedDescription = computed(() => {
	if (condensed) {
		return ''
	}
	if (description !== '') {
		return description
	}
	if (user.type === 'deleted') {
		return t('polls', 'The participant got removed from this poll')
	}
	if (user.type === 'admin') {
		return t('polls', 'Administrative rights granted')
	}
	if (user.type === 'anonymous') {
		return t('polls', 'Anonymized participant')
	}
	return emailAddressComputed
})

const computedLabel = computed(() => {
	if (virtualUserType === 'internalAccess') {
		return t('polls', 'Internal access')
	}
	if (virtualUserType === 'addPublicLink') {
		return t('polls', 'Add public link')
	}
	if (user.type === 'public') {
		return user.displayName || t('polls', 'Public link')
	}
	if (user.type === 'deleted') {
		return t('polls', 'Deleted participant')
	}
	return user.displayName ?? user.id
})

const emailAddressComputed = computed(() => {
	if (
		showEmail
		&& user.emailAddress !== user.displayName
		&& (computedRoleType.value === 'external'
			|| computedRoleType.value === 'email')
	) {
		return user.emailAddress
	}

	return ''
})

/**
 *
 */
function showMenu() {
	// TODO: implement
	return true
}

const componentClass = computed(() => [
	'user-item',
	virtualUserType || user.type,
	{
		disabled,
		condensed,
	},
])
</script>

<template>
	<component :is="tag" :class="componentClass" :style="itemStyle">
		<div class="avatar-wrapper">
			<UserAvatar
				v-bind="$attrs"
				:virtual-user-type="virtualUserType"
				:user="user"
				:label="computedLabel"
				@click="showMenu()" />
			<div v-if="$slots.typeIcon" class="type-icon">
				<slot name="typeIcon" />
			</div>
			<div v-if="$slots.menu" class="hover-menu">
				<slot name="menu" />
			</div>
		</div>

		<slot name="status" />

		<div v-if="!hideNames" class="user-item__name">
			<div class="name" :title="computedLabel">
				{{ computedLabel }}
			</div>
			<div class="description">
				{{ computedDescription }}
			</div>
		</div>

		<slot />
	</component>
</template>

<style lang="scss">
.avatar-wrapper {
	position: relative;
	display: flex;

	.type-icon {
		position: absolute;
		background-size: 16px;
		top: -3px;
		inset-inline-start: -6px;
	}
}

.user-item {
	display: grid;
	grid-template-columns: auto auto 1fr auto;
	align-items: center;
	column-gap: 8px;
	margin: 8px 0;
	&.disabled {
		opacity: 0.6;
	}
	&.condensed {
		grid-template-columns: 1fr;
		justify-items: center;
	}
}

.hover-menu {
	position: absolute;
	top: 0;
	inset-inline-start: 0;
	opacity: 0;
	&:hover {
		opacity: 1;
	}
}

.user-item__avatar .material-design-icon {
	background-color: var(--color-primary-element);
	border-radius: 50%;
	color: var(--color-primary-element-text);
}

.user-item__name {
	white-space: nowrap;
	> div {
		overflow: hidden;
		text-overflow: ellipsis;
	}

	.description {
		color: var(--color-text-maxcontrast);
		font-size: 0.7em;
	}

	.condensed & {
		font-size: 0.7em;
		text-align: center;
		width: 70px;
		max-width: 70px;
		padding: 0 4px;
	}
}
</style>

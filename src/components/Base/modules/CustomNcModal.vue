<!--
  - SPDX-FileCopyrightText: 2026 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import NcModal from '@nextcloud/vue/components/NcModal'

defineOptions({ inheritAttrs: false })
</script>

<template>
	<NcModal v-bind="$attrs" class="custom-nc-modal">
		<template v-for="(_, name) in $slots" #[name]="slotProps">
			<slot :name="name" v-bind="slotProps ?? {}" />
		</template>
	</NcModal>
</template>

<style lang="scss" scoped>
.custom-nc-modal {
	.modal__content {
		padding: 14px;
		color: var(--color-main-text);
	}
}
</style>

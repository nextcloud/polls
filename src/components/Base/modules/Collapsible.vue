<!--
  - SPDX-FileCopyrightText: 2025 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue'

interface Props {
	initialState?: 'min' | 'max'
	minHeight?: number
	noCollapse: boolean
}

const {
	initialState = 'max',
	minHeight = 100,
	noCollapse = false,
} = defineProps<Props>()

// Reference to the inner content wrapper
const slotWrapper = ref<HTMLElement | null>(null)

// Measured content height
const contentHeight = ref(0)

// Current visible container height
const height = ref(minHeight)

// Max height: either content or 40vh
const maxHeight = computed(() =>
	Math.min(contentHeight.value, window.innerHeight * 0.4),
)

// Watch for manual height changes (e.g., via drag or click)
const isTransitioning = ref(false)

// Effective minHeight (capped by maxHeight)
const effectiveMinHeight = computed(() => Math.min(minHeight, maxHeight.value))

// Drag logic
const drag = {
	startY: 0,
	startHeight: 0,
	isDragging: false,
	hasInitializedHeight: false,
}

function getClientY(event: MouseEvent | TouchEvent): number {
	return 'touches' in event ? (event.touches[0]?.clientY ?? 0) : event.clientY
}

// Start dragging the resize handle
function startResize(event: MouseEvent | TouchEvent) {
	if (noCollapse) return

	drag.startY = getClientY(event)
	drag.startHeight = height.value
	drag.isDragging = false

	document.addEventListener('mousemove', onMove)
	document.addEventListener('touchmove', onMove)
	document.addEventListener('mouseup', stopResize)
	document.addEventListener('touchend', stopResize)
}

// Handle vertical dragging
function onMove(event: MouseEvent | TouchEvent) {
	const y = getClientY(event)
	const dy = y - drag.startY

	if (Math.abs(dy) > 3) drag.isDragging = true

	let newHeight = drag.startHeight + dy
	newHeight = Math.max(
		effectiveMinHeight.value,
		Math.min(maxHeight.value, newHeight),
	)
	height.value = newHeight
}

// Stop dragging – or toggle between min/max if it was just a click
function stopResize() {
	if (noCollapse) return

	document.removeEventListener('mousemove', onMove)
	document.removeEventListener('touchmove', onMove)
	document.removeEventListener('mouseup', stopResize)
	document.removeEventListener('touchend', stopResize)

	if (drag.isDragging) {
		drag.isDragging = false
	} else {
		requestAnimationFrame(() => {
			drag.isDragging = false
			height.value =
				height.value > effectiveMinHeight.value + 10
					? effectiveMinHeight.value
					: maxHeight.value
		})
	}
}

const containerRef = ref<HTMLElement | null>(null)

const hasTopOverflow = ref(false)
const hasBottomOverflow = ref(false)

function updateOverflowIndicators() {
	const el = containerRef.value
	if (!el) return

	hasTopOverflow.value = el.scrollTop > 0
	hasBottomOverflow.value = el.scrollTop + el.clientHeight < el.scrollHeight
}

let observer: ResizeObserver | null = null

onMounted(() => {
	updateOverflowIndicators()

	containerRef.value?.addEventListener('scroll', updateOverflowIndicators)

	if (slotWrapper.value) {
		// Watch actual content size via ResizeObserver
		observer = new ResizeObserver(() => {
			if (!slotWrapper.value) return

			// If collapse is disabled, always follow content size (with 50vh cap)
			if (noCollapse) {
				contentHeight.value = slotWrapper.value.scrollHeight
				height.value = maxHeight.value
				return
			}

			const scrollHeight = slotWrapper.value.scrollHeight
			const previousMax = maxHeight.value
			contentHeight.value = scrollHeight

			const newMax = maxHeight.value
			const wasAtMax = height.value === previousMax

			// Expand height if previously at max and content grew
			if (wasAtMax && newMax > previousMax) {
				height.value = newMax
			}

			// Reduce height if content shrunk below current height
			if (height.value > newMax) {
				height.value = newMax
			}

			// Set initial height only once
			if (!drag.hasInitializedHeight) {
				const target =
					initialState === 'min' ? effectiveMinHeight.value : newMax

				drag.isDragging = true // disable transition
				height.value = target
				drag.hasInitializedHeight = true

				requestAnimationFrame(() => {
					drag.isDragging = false
				})
			}
			updateOverflowIndicators()
		})
		observer.observe(slotWrapper.value)
	}
})

// Watch for manual height changes (e.g., via drag)
watch(height, () => {
	requestAnimationFrame(updateOverflowIndicators)
})

onBeforeUnmount(() => {
	observer?.disconnect()
	containerRef.value?.removeEventListener('scroll', updateOverflowIndicators)
})
</script>

<template>
	<div class="collapsible">
		<div
			:class="[
				'collapsible_wrapper',
				{
					'has-top-shadow': hasTopOverflow,
					'has-bottom-shadow': hasBottomOverflow,
				},
			]">
			<div
				ref="containerRef"
				:class="[
					'collapsible_container',
					{ 'no-transition': drag.isDragging },
				]"
				:style="{ height: height + 'px' }">
				<div ref="slotWrapper" class="collapsible_content">
					<slot />
				</div>
			</div>
		</div>
		<div
			v-show="!noCollapse && contentHeight >= minHeight"
			class="resize-handle"
			:style="{ top: isTransitioning ? undefined : height + 'px' }"
			@touchstart.prevent="startResize"
			@mousedown.prevent="startResize" />
	</div>
</template>

<style lang="scss">
.collapsible {
	position: relative;
	margin-bottom: 1.5rem;

	.collapsible_container {
		position: relative;
		overflow: auto;
		width: 100%;
		transition: height 0.3s ease;
		padding-right: 8px;

		&.no-transition {
			transition: none !important;
		}
	}

	.collapsible_wrapper {
		position: relative;
		overflow: hidden;

		&::before,
		&::after {
			content: '';
			position: absolute;
			left: 0;
			right: 8px;
			height: 1.5rem;
			pointer-events: none;
			z-index: 1;
		}

		// Fade top
		&::before {
			top: 0;
			background: linear-gradient(
				to bottom,
				var(--color-main-background),
				rgba(0, 0, 0, 0)
			);
			opacity: 0;
			transition: opacity 0.2s;
		}

		// Fade bottom
		&::after {
			bottom: 0;
			background: linear-gradient(
				to top,
				var(--color-main-background),
				rgba(0, 0, 0, 0)
			);
			opacity: 0;
			transition: opacity 0.2s;
		}

		&.has-top-shadow::before {
			opacity: 1;
		}
		&.has-bottom-shadow::after {
			opacity: 1;
		}
	}
}

.resize-handle {
	position: absolute;
	left: 0;
	right: 0;
	height: 12px;
	cursor: ns-resize;
	background: transparent;
	z-index: 1;

	&::before {
		content: '';
		position: absolute;
		top: 50%;
		left: 0;
		right: 0;
		height: 1px;
		background: var(--color-border);
		transform: translateY(-0.5px);
	}

	&::after {
		content: '• • •';
		position: absolute;
		top: 50%;
		left: 50%;
		transform: translate(-50%, -50%);
		padding: 0.15rem 0.5rem;
		font-size: 1rem;
		line-height: 1;
		color: var(--color-main-text);
		background: var(--color-border, rgba(0, 0, 0, 0.05));
		border-radius: 0.5rem;
		pointer-events: none;
		user-select: none;
	}

	&:hover::after {
		color: var(--color-loading-dark);
		background: var(--color-background-darker, rgba(0, 0, 0, 0.1));
	}
}
</style>

<template>
	<div>
		<ul class="tabHeaders">
			<li v-for="(tab, index) in tabs" :key="index"
				:class="{ 'selected': tab === value }"
				class ="tabHeader"
				@click="$emit('input', tab)"
			>
				{{ tab.displayTitle }}
			</li>
		</ul>
		
		<div class="tabsContainer">
			<transition name="fade">
				<slot></slot>
			</transition>
		</div>
	</div>
</template>

<script>
	export default {
		props: {
			value: Object,
			tabs: Array
		}
	}
</script>


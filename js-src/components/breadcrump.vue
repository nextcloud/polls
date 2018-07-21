<template>
	<div class="breadcrump flex-row">
		<div class="crumb svg">
			<a :href="indexPage">
				<img class="svg" :src="imagePath" alt="Home">
			</a>
		</div>
		<div class="crumb svg last">
			<span v-text="intitle" />
		</div>
	</div>
</template>

<script>
	export default {
		props: ['intitle','indexPage'],
		data: function () {
			return {
				imagePath: OC.imagePath('core', 'places/home.svg'),
			};
		}
	}
</script>

<template>
	<div id="breadcrump">
		<div class="crumb svg">
			<a :href="home">
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
		props: ['intitle'],
		data: function () {
			return {
				home: OC.generateUrl('apps/polls'),
				imagePath: OC.imagePath('core', 'places/home.svg'),
			};
		}
	}
</script>

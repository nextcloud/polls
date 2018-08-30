<template>
	<div class="breadcrump">
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

<style lang="scss" scoped>
.breadcrump {
	flex-shrink: 1;
	overflow: hidden;
	min-width: 0;
	flex-grow: 1;
	display: flex;

	div.crumb:last-child {
		flex-shrink: 1;
		overflow: hidden;
		
		> span {
			flex-shrink: 1;
			text-overflow: ellipsis;
		}
	}
}

</style>

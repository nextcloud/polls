/* global: Vue */
Vue.component('breadcrump', {
	props: ['intitle'],
	template: 	
		'<div id="breadcrump">' +
		'	<div class="crumb svg">' +
		'		<a :href="home">' +
		'			<img class="svg" :src="imagePath" alt="Home">' +
		'		</a>' +
		'	</div>' +
		'	<div class="crumb svg last">' +
		'		<span v-text="intitle" />' +
		'	</div>' +
		'</div>',
	data: function () {
		return {
			home: OC.generateUrl('apps/polls'),
			imagePath: OC.imagePath('core', 'places/home.svg'),
		};
	}
});
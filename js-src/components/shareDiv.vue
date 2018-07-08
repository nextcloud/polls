<template>
	<div>
		<h2> {{ t('polls', 'Share with') }}</h2>
		<input v-model="query" 
			:placeholder="placeholder" 
			class="shareWithField" 
			autocomplete="off"
			type="text">
		<transition-group v-show="query !== ''" name="staggered-fade" tag="ul" v-bind:css="false"	>
			<li v-for="(item, index) in computedList" 
				v-bind:key="item.displayName" 
				v-bind:data-index="index" 
				class="flex-row">
				<div class="avatar has-tooltip-bottom" style="height: 32px; width: 40px;" >
					<img :src="item.avatarURL" width="32" height="32">
				</div>
				<div>{{ item.displayName }}  {{ item.type === 'group' ? '(group)' : '' }}</div>
			</li>
		</transition-group>
	</div>
</template>

<script>
	import Velocity from 'velocity-animate';
	import axios from 'axios';
	export default {
		props: ['placeholder', 'value'],
		data: function () {
			return {
				query: '',
				users: []
			}
		},
		mounted: function() {
			this.loadSiteUsers()
		},

		computed: {
			computedList: function () {
				var vm = this
				return this.users.filter(function (item) {
					return item.displayName.toLowerCase().indexOf(vm.query.toLowerCase()) !== -1
				})
			}
		},
		methods: {
			loadSiteUsers: function () {
				axios.get(OC.generateUrl('apps/polls/get/siteusers'))
				.then((response) => {
					this.users = response.data.siteusers;
					var i;
					for (i = 0; i < this.users.length; i++) {
						this.users[i].avatarURL = OC.generateUrl(
						'/avatar/{user}/{size}?v={version}', {
							user: this.users[i].id,
							size: Math.ceil(32 * window.devicePixelRatio),
							version: oc_userconfig.avatar.version
						})
					}
					
				}, (error) => {
					console.log(error.response);
				});
			}		
		}
	}
</script>

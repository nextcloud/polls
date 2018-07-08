<template>
	<div>
		<h2> {{ t('polls', 'Share with') }}</h2>
		
		<div class="autocomplete">
			<input v-model="query" 
				:placeholder="placeholder" 
				class="shareWithField" 
				autocomplete="off"
				type="text">
			
			<transition-group v-show="query !== ''" name="user-list-fade" tag="ul" v-bind:css="false" class="user-list suggestion">
				<li v-for="(item, index) in computedList" 
					v-bind:key="item.displayName" 
					v-bind:data-index="index" 
					class="flex-row"
					v-on:click="addShare(index, item)">
					<div class="avatar has-tooltip-bottom" style="height: 32px; width: 40px;" >
						<img :src="item.avatarURL" width="32" height="32">
					</div>
					<div>{{ item.displayName }}  {{ item.type === 'group' ? '(group)' : '' }}</div>
				</li>
			</transition-group>
		</div>
		
		<transition-group name="shared-list-fade" tag="ul" v-bind:css="false" class="shared-list">
			<li v-for="(item, index) in sortedShares" 
				v-bind:key="item.displayName" 
				v-bind:data-index="index" 
				class="flex-row">
				<div class="avatar has-tooltip-bottom" style="height: 32px; width: 40px;" >
					<img :src="item.avatarURL" width="32" height="32">
				</div>

				<div>{{ item.displayName }} {{ item.type === 'group' ? '(group)' : '' }}</div>
				<div class="flex-row options">
					<a @click="removeShare(index, item)" class="icon icon-delete svg delete-poll"></a>
				</div>
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
				users: [],
			}
		},
		mounted: function() {
			this.loadSiteUsers()
			document.addEventListener('click', this.handleClickOutside)
		},
		
		destroyed() {
			document.removeEventListener('click', this.handleClickOutside)
		},

		computed: {
			computedList: function () {
				var vm = this
				return this.users.filter(function (item) {
					return item.displayName.toLowerCase().indexOf(vm.query.toLowerCase()) !== -1
				})
			},
			sortedShares: function() {
				function sortByDisplayname(a, b) {
					if (a.displayName.toLowerCase() < b.displayName.toLowerCase()) return -1;
					if (a.displayName.toLowerCase() > b.displayName.toLowerCase()) return 1;
					return 0;
				}
				return this.value.sort(sortByDisplayname);
			}
		},
		
		methods: {
			addShare: function (index, item){
				this.$emit('add-share', item);
				this.users.splice(this.users.indexOf(item), 1);
				this.query='';
			},
			removeShare: function (index, item){
				this.$emit('remove-share', item);
				this.users.push(item);
			},
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
			},
			handleClickOutside(evt) {
				if (!this.$el.contains(evt.target)) {
					this.isOpen = false;
					this.arrowCounter = -1;
				}
			}			
		}
	}
</script>

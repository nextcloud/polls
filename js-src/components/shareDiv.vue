<template>
	<div>
		<h2> {{ t('polls', 'Share with') }}</h2>
		
		<div class="autocomplete">
			<input class="shareWithField" 
				autocomplete="off"
				type="text"
				:placeholder="placeholder" 
				v-model="query"
				@input="onInput"
				@focus="onInput">
			
			<transition-group v-show="openList" name="user-list-fade" tag="ul" v-bind:css="false" class="user-list suggestion">
				<li v-for="(item, index) in sortedSiteusers" 
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
				openList: false
			}
		},
		
		mounted: function() {
			this.loadSiteUsers()
			document.addEventListener('click', this.handleClickOutside)
		},
		
		destroyed: function() {
			document.removeEventListener('click', this.handleClickOutside)
		},

		computed: {
			filteredSiteusers: function () {
				var vm = this
				return this.users.filter(function (item) {
					return item.displayName.toLowerCase().indexOf(vm.query.toLowerCase()) !== -1
				})
			},
			
			sortedSiteusers: function() {
				return this.filteredSiteusers.sort(this.sortByDisplayname);
			},
			
			sortedShares: function() {
				return this.value.sort(this.sortByDisplayname);
			}
		},
		
		methods: {
			addShare: function (index, item){
				this.$emit('add-share', item);
				this.users.splice(this.users.indexOf(item), 1);
			},
			
			removeShare: function (index, item){
				this.$emit('remove-share', item);
				this.users.push(item);
			},
			
			loadSiteUsers: function () {
				axios.get(OC.generateUrl('apps/polls/get/siteusers'))
				.then((response) => {
					this.users = response.data.siteusers;
					
					this.users.forEach(function(item) {
						item.avatarURL = OC.generateUrl(
							'/avatar/{user}/{size}?v={version}', {
								user: item.id,
								size: Math.ceil(32 * window.devicePixelRatio),
								version: oc_userconfig.avatar.version
							}
						)
					});
					
				}, (error) => {
					console.log(error.response);
				});
				this.users.forEach(function(item) {
					console.log(item.id);
				});
			},
			
			onInput: function() {
				if (this.query !== '') {
					this.openList = true;
				}
			},
			
			sortByDisplayname: function (a, b) {
					if (a.displayName.toLowerCase() < b.displayName.toLowerCase()) return -1;
					if (a.displayName.toLowerCase() > b.displayName.toLowerCase()) return 1;
					return 0;
			},
			
			handleClickOutside: function(evt) {
				if (!this.$el.contains(evt.target)) {
					this.openList = false;
				}
			}			
		}
	}
</script>

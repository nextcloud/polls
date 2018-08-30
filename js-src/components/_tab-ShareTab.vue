<template id="share-tab">
	<div>
		<div class="autocomplete">
			<input class="shareWithField" 
				autocomplete="off"
				type="text"
				:placeholder="placeholder" 
				v-model="query"
				@input="onInput"
				@focus="onInput">
			
			<transition-group v-show="openList" tag="ul" v-bind:css="false" class="user-list suggestion">
				<li v-for="(item, index) in sortedSiteusers" 
					v-bind:key="item.displayName" 
					v-bind:data-index="index" 
					class="flex-row"
					v-on:click="addShare(index, item)">
					<user-div :user-id="item.id" :display-name="item.displayName" :type="item.type"></user-div>
				</li>
			</transition-group>
		</div>
		
		<transition-group tag="ul" v-bind:css="false" class="shared-list">
			<li v-for="(item, index) in sortedShares" 
				v-bind:key="item.displayName" 
				v-bind:data-index="index" 
				class="flex-row">
				<user-div :user-id="item.id" :display-name="item.displayName" :type="item.type"></user-div>

				<div class="flex-row options">
					<a @click="removeShare(index, item)" class="icon icon-delete svg delete-poll"></a>
				</div>
			</li>
		</transition-group>
	</div>
</template>

<script>
	import axios from 'axios';

	export default {
		template: "#share-tab",
		props: ['value', 'mode', 'protect'],

		data: function() {
			return {
				activeShares: Object,
				placeholder: 'BlaBlubb',
				query: '',
				users: [],
				openList: false,
				siteUsersLoaded: false,
				siteUsersListOptions: {
					getUsers: true,
					getGroups: true,
					skipUsers: [],
					skipGroups: []
				}

			}
		},

		watch: {
			value () {
				this.activeShares = this.value.shares;
			}
		},

		created: function() {
			this.loadSiteUsers();
		},
		
		mounted: function() {
			document.addEventListener('click', this.handleClickOutside)
		},
		
		destroyed: function() {
			document.removeEventListener('click', this.handleClickOutside)
		},

		computed: {
			filteredSiteusers: function() {
				var vm = this;
				return this.users.filter(function (item) {
					return item.displayName.toLowerCase().indexOf(vm.query.toLowerCase()) !== -1
				})
			},
			
			sortedSiteusers: function() {
				return this.filteredSiteusers.sort(this.sortByDisplayname);
			},

			sortedShares: function() {
				return this.activeShares.sort(this.sortByDisplayname);
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
				var vm = this;
				vm.siteUsersListOptions.skipUsers = [];
				vm.siteUsersListOptions.skipGroups = [];
				this.activeShares.forEach(function(item) {
					if (item.type === 'group') { 
						vm.siteUsersListOptions.skipGroups.push(item.id)
					} else if (item.type === 'user') {
						vm.siteUsersListOptions.skipUsers.push(item.id)
					}
				});

				axios.post(OC.generateUrl('apps/polls/get/siteusers'), this.siteUsersListOptions)
				.then((response) => {
					this.users = response.data.siteusers;
				}, (error) => {
					console.log(error.response);
				});
			},
			
			onInput: function() {
				this.loadSiteUsers();
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
<style lang="scss" scoped>

.tab {
	display: flex;
	flex-grow: 1;
	flex-wrap: wrap;

	.configBox > .title {
		font-weight: bold;
		margin-bottom: 4px;
	}

}

.configBox {
	display: flex;
	flex-direction: column;
	flex-grow: 0;
	flex-shrink: 0;
}



</style>

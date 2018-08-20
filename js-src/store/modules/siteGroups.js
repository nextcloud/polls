import ncGroups from '../../api/nc_groups'

// initial state
const state = {
  all: []
}

// getters
const getters = {}

// actions
const actions = {
  getSiteGroups ({ commit }) {
    ncGroups.getGroups(groups => {
      commit('setGroups', groups)
    })
  }
}

// mutations
const mutations = {
  setGroups (state, groups) {
    state.all = groups
  },

}

export default {
  namespaced: true,
  state,
  getters,
  actions,
  mutations
}

import ncUsers from '../../api/nc_users'

// initial state
const state = {
  all: []
}

// getters
const getters = {}

// actions
const actions = {
  getSiteUsers ({ commit }) {
    ncUsers.getUsers(users => {
      commit('setUsers', users)
    })
  }
}

// mutations
const mutations = {
  setUsers (state, users) {
    state.all = users
  },

}

export default {
  namespaced: true,
  state,
  getters,
  actions,
  mutations
}

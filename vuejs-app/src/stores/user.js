import { defineStore } from 'pinia';

export const useUserStore = defineStore('user',
  {
    state: () => ({
      id: null,
      name: null,
      email: null,
    }),
    getters: {
      isAuthenticated: (state) => !!state.id,
    },
    actions: {
      // User state management
      setState(user) {
        this.id = user.id;
        this.name = user.name;
        this.email = user.email;
      },
      resetState() {
        this.id = null;
        this.name = null;
        this.email = null;
      },

      // User Sanctum Token management
      setSanctumToken(token) {
        localStorage.setItem('SANCTUM-TOKEN', token);
      },
      getSanctumToken() {
        return localStorage.getItem('SANCTUM-TOKEN');
      },
      removeSanctumToken() {
        localStorage.removeItem('SANCTUM-TOKEN');
      },

      // Reset user state and remove Sanctum token (e.g., on sign out)
      reset() {
        this.resetState();
        this.removeSanctumToken();
      },
    },
    persist: true,
  }
);

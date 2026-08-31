import { defineStore } from "pinia";

export const useUserStore = defineStore("user", {
    state: () => ({
        id: null,
        name: null,
        email: null,
        profile_image: null,
        profile_thumbnail: null,
        password_null: true,
        level: null,
    }),
    getters: {
        isAuthenticated: (state) => !!state.id,
        isAdmin: (state) => state.level === "ADMIN",
    },
    actions: {
        // User state management
        setState(user) {
            this.id = user.id;
            this.name = user.name;
            this.email = user.email;
            this.profile_image = user.profile_image;
            this.profile_thumbnail = user.profile_thumbnail;
            this.password_null = user.password_null;
            this.level = user.level;
        },
        resetState() {
            this.id = null;
            this.name = null;
            this.email = null;
            this.profile_image = null;
            this.profile_thumbnail = null;
            this.password_null = true;
            this.level = null;
        },

        // User Sanctum Token management
        setSanctumToken(token) {
            localStorage.setItem("SANCTUM-TOKEN", token);
        },
        getSanctumToken() {
            return localStorage.getItem("SANCTUM-TOKEN");
        },
        removeSanctumToken() {
            localStorage.removeItem("SANCTUM-TOKEN");
        },

        // Reset user state and remove Sanctum token (e.g., on sign out)
        reset() {
            this.resetState();
            this.removeSanctumToken();
        },
    },
    persist: true,
});

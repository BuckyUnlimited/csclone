import 'bootstrap/dist/js/bootstrap.bundle.min.js';
import 'admin-lte/dist/js/adminlte.min.js';

import { createApp } from 'vue'
import { createPinia } from 'pinia'
import piniaPluginPersistedstate from 'pinia-plugin-persistedstate'
import App from './App.vue'
import router from './router'
import { useUserStore } from '@/stores/user';
import { apiVerify } from '@/functions/api/auth';

const app = createApp(App)

const pinia = createPinia();
pinia.use(piniaPluginPersistedstate);
app.use(pinia);
app.use(router);
app.mount('#app');


const userStore = useUserStore();
router.beforeEach(async (to, from) => {
  const { guarded } = to.meta;
  if (guarded === undefined) { // if the route is not guarded, we don't need to verify the token
    return;
  }

  try {
    const token = userStore.getSanctumToken();
    const response = await apiVerify(token);
    const { data } = response;
    userStore.setState(data.user);
  } catch (error) {
    userStore.reset();
  }

  if (guarded && !userStore.isAuthenticated) { // if the route is guarded and the user is not authenticated, redirect to signin page
    return { name: 'auth.signin' };
  }
  if (!guarded && userStore.isAuthenticated) { // if the route is not guarded and the user is authenticated, redirect to dashboard page
    return { name: 'dashboard' };
  }
});

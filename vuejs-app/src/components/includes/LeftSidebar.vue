<template>
  <aside class="main-sidebar sidebar-dark-primary elevation-4">
    <router-link to="/" class="brand-link">
      <img :src="logoImage" alt="Chat System Logo" class="brand-image img-circle elevation-3" style="opacity: .8">
      <span class="brand-text font-weight-light">Chat System</span>
    </router-link>

    <div class="sidebar">
      <div class="user-panel mt-3 pb-3 mb-3 d-flex">
        <div class="image">
          <img :src="userStore.profile_thumbnail || emptyImage" class="img-circle elevation-2" alt="User Image">
        </div>
        <div class="info">
          <router-link :to="{ name: 'profile' }" class="d-block">{{ userStore.name }}</router-link>
        </div>
      </div>
      <nav class="mt-2">
        <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu" data-accordion="false">
          <li class="nav-item">
            <router-link :to="{ name: 'dashboard' }" active-class="active" class="nav-link">
              <i class="nav-icon fas fa-tachometer-alt"></i>
              <p>
                Dashboard
              </p>
            </router-link>
          </li>
          <li class="nav-header" v-if="userStore.isAdmin">MANAGEMENT</li>
          <li class="nav-item" v-if="userStore.isAdmin">
            <router-link :to="{ name: 'users' }" active-class="active" class="nav-link">
              <i class="nav-icon fas fa-users"></i>
              <p>
                Users
              </p>
            </router-link>
          </li>
          <li class="nav-item" v-if="userStore.isAdmin">
            <router-link :to="{ name: 'backups' }" active-class="active" class="nav-link">
              <i class="nav-icon fas fa-database"></i>
              <p>
                Backups
              </p>
            </router-link>
          </li>
        </ul>
      </nav>

      <!-- SidebarSearch Form -->
      <div class="form-inline">
        <div class="input-group">
          <input class="form-control form-control-sidebar" type="text" placeholder="Search" aria-label="Search">
          <div class="input-group-append">
            <button class="btn btn-sidebar">
              <i class="fas fa-search fa-fw"></i>
            </button>
          </div>
        </div>
      </div>
      <nav class="mt-2">
        <ChatList :chats="chats"></ChatList>
        <UserList :users="users"></UserList>
      </nav>
    </div>
  </aside>
</template>
<script setup>
import emptyImage from '@/assets/images/emptyImage.png';
import logoImage from '@/assets/images/logoImage.webp';
import { useUserStore } from '@/stores/user';
import { ref, onMounted } from 'vue';
import { apiGetChats, apiGetChatUsers } from '@/functions/api/chat';
import ChatList from '@/components/includes/controls/ChatList.vue';
import UserList from '@/components/includes/controls/UserList.vue';
const userStore = useUserStore();
const chats = ref([]);
const users = ref([]);

onMounted(() => {
  generateChats();
  generateUsers();
});
async function generateChats() {
  const response = await apiGetChats();
  chats.value = response.data.chats;
}
async function generateUsers() {
  const response = await apiGetChatUsers();
  users.value = response.data.users;
}
</script>

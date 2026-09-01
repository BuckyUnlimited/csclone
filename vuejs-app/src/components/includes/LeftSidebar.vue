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
          <input v-model="keyword" class="form-control form-control-sidebar" type="text" placeholder="Search"
            aria-label="Search">
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

        <li v-if="isLoadingMore" class="nav-item text-center text-light p-2">
          <i class="fas fa-spinner fa-spin"></i> Loading...
        </li>
      </nav>
    </div>
  </aside>
</template>
<script setup>
import emptyImage from "@/assets/images/emptyImage.png";
import logoImage from "@/assets/images/logoImage.webp";
import { useUserStore } from "@/stores/user";
import { useRecentChatsStore } from "@/stores/recentChats";
import { ref, onMounted, watch, computed } from "vue";
import { apiGetChats, apiGetChatUsers } from "@/functions/api/chat";
import ChatList from "@/components/includes/controls/ChatList.vue";
import UserList from "@/components/includes/controls/UserList.vue";
import $ from "jquery";
import { useRoute } from "vue-router";

const route = useRoute();
watch(route, (newRoute) => {
  keyword.value = "";
});

const userStore = useUserStore();
const recentChatsStore = useRecentChatsStore();

const chats = computed(() => recentChatsStore.chats);
const users = ref([]);

// Pagination state chat
const chatCurrentPage = ref(1);
const chatLastPage = ref(1);

// Pagination state users
const userCurrentPage = ref(1);
const userLastPage = ref(1);

const pageSize = ref(50);
const keyword = ref("");
const isLoadingMore = ref(false);

onMounted(() => {
  generateChats();

  // jQuery infinite scroll on sidebar
  $(".sidebar").on("scroll", async function () {
    if (isLoadingMore.value) {
      return; // Prevent multiple simultaneous fetches
    }

    const $this = $(this);
    const scrollTop = $this.scrollTop();
    const innerHeight = $this.innerHeight();
    const scrollHeight = $this[0].scrollHeight;

    if (scrollTop + innerHeight < scrollHeight - 50) {
      return; // Not near the bottom yet
    }

    isLoadingMore.value = true;

    // load more users
    if (userCurrentPage.value < userLastPage.value) {
      await generateUsers(keyword.value, userCurrentPage.value + 1);
    }

    // load more chats
    if (chatCurrentPage.value < chatLastPage.value) {
      await generateChats(keyword.value, chatCurrentPage.value + 1);
    }

    isLoadingMore.value = false;
  });
});

watch(keyword, async (newKeyword) => {
  if (isLoadingMore.value) {
    return;
  }

  users.value = [];
  chats.value = [];

  isLoadingMore.value = true;

  await Promise.all([
    generateChats(newKeyword, 1,),
    generateUsers(newKeyword, 1),
  ]);

  isLoadingMore.value = false;
});

async function generateChats(
  searchKeyword = "",
  page = 1,
  per_page = pageSize.value,
) {
  const response = await apiGetChats({
    keyword: searchKeyword,
    page: page,
    per_page: per_page,
  });

  recentChatsStore.syncMultiChats([...chats.value, ...response.data.chats]);

  chatCurrentPage.value = response.data.meta.current_page;
  chatLastPage.value = response.data.meta.last_page;
}

async function generateUsers(
  searchKeyword = "",
  page = 1,
  per_page = pageSize.value,
) {
  const response = await apiGetChatUsers({
    keyword: searchKeyword,
    page: page,
    per_page: per_page,
  });

  users.value = [...users.value, ...response.data.users];

  userCurrentPage.value = response.data.meta.current_page;
  userLastPage.value = response.data.meta.last_page;
}
</script>

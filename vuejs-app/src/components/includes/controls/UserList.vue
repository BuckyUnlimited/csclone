<template>
  <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu" data-accordion="false">
    <li class="nav-item" v-for="user in users" :key="user.id">
      <a role="button" class="nav-link">
        <img class="nav-icon img-circle elevation-3 my-1" :src="user.profile_image || emptyImage" />
        <p class="chat-name">{{ user.name }}</p>
        <br />
        <p class="chat-message mt-1">
          <span class="text-bold text-muted">Start a new conversation</span>
        </p>
      </a>
    </li>
  </ul>
</template>

<script setup>
import emptyImage from "@/assets/images/emptyImage.png";
const props = defineProps({
  users: {
    type: Array,
    required: true,
  },
});
</script>

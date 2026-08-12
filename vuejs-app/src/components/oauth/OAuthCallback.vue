<template></template>
<script setup>
import { onMounted } from 'vue';
import { useRoute } from 'vue-router';
import { LoadingModal, MessageModal, CloseModal } from "@/functions/swal";
import { apiOAuthExchangeToken } from "@/functions/api/oauth";
import { useUserStore } from "@/stores/user";
import { useRouter } from 'vue-router';

const userStore = useUserStore();
const router = useRouter();
const route = useRoute();

onMounted(async () => {
  try {
    LoadingModal("Processing authentication...");
    const error = route.query.error;
    if (error === 'oauth_failed') {
      return MessageModal({ icon: "error", title: "Error", text: "OAuth authentication failed. Please try again." }, () => {
        return router.replace({ name: 'auth.signin' });
      });
    }

    const token = route.query.token;
    const response = await apiOAuthExchangeToken(token);
    userStore.setState(response.data.user);
    userStore.setSanctumToken(response.data.token);
    CloseModal();
    return router.replace({ name: 'dashboard' });
  } catch (e) {
    return MessageModal({ icon: "error", title: "Error", text: "OAuth authentication failed. Please try again." }, () => {
      return router.replace({ name: 'auth.signin' });
    });
  }
});
</script>

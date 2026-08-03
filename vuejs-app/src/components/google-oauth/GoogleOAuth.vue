<template></template>
<script setup>
import { onMounted } from 'vue';
import { useRoute } from 'vue-router';
import { LoadingModal, MessageModal, CloseModal } from "@/functions/swal";
import { apiGoogleOAuthExchangeToken } from "@/functions/api/google-oauth";
import { useUserStore } from "@/stores/user";
import { useRouter } from 'vue-router';

const userStore = useUserStore();
const router = useRouter();
const route = useRoute();

onMounted(async () => {
  try {
    LoadingModal("Processing Google authentication...");
    const error = route.query.error;
    if (error === 'google_oauth_failed') {
      return MessageModal({ icon: "error", title: "Error", text: "Google authentication failed. Please try again." }, () => {
        return router.replace({ name: 'auth.signin' });
      });
    }

    const token = route.query.token;
    const response = await apiGoogleOAuthExchangeToken(token);
    userStore.setState(response.data.user);
    userStore.setSanctumToken(response.data.token);
    CloseModal();
    return router.replace({ name: 'dashboard' });
  } catch (e) {
    return MessageModal({ icon: "error", title: "Error", text: "Google authentication failed. Please try again." }, () => {
      return router.replace({ name: 'auth.signin' });
    });
  }
});
</script>

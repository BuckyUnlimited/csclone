<template></template>
<script setup>
import { apiSignOut } from "@/functions/api/auth";
import { onMounted } from "vue";
import { useRouter } from "vue-router";
import { useUserStore } from "@/stores/user";
const router = useRouter();
const userStore = useUserStore();

onMounted(async () => {
  const token = userStore.getSanctumToken();
  apiSignOut(token); // no need to await since we will remove the token regardless of the response
  userStore.reset();
  router.replace({ name: "auth.signin" });
});
</script>

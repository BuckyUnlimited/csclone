<template>
  <div class="login-page">
    <div class="login-box">
      <div class="card card-outline card-primary">
        <div class="card-header text-center">
          <router-link to="/" class="h1"><b>Admin</b>LTE</router-link>
        </div>
        <div class="card-body">
          <div v-if="status" :class="{
            'alert alert-success': status === 'success',
            'alert alert-danger': status === 'error'
          }" role="alert">
            {{ message }}
          </div>
          <p class="mb-1">
            <router-link :to="{ name: 'auth.signin' }" class="text-center">Go back to login</router-link>
          </p>
          <p class="mb-0">
            <router-link :to="{ name: 'auth.signup' }" class="text-center">Register a new membership</router-link>
          </p>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import axios from "axios";
import { onMounted, ref } from "vue";
import { useRoute } from "vue-router";
import { LoadingModal, CloseModal } from "@/functions/swal";
const route = useRoute();

const status = ref(null);
const message = ref("");

onMounted(async () => {
  try {
    LoadingModal('Verifying email...');
    const response = await axios.get(new URL(route.query['forwarded-url']));
    status.value = "success";
    message.value = response.data.message;
  } catch (error) {
    status.value = "error";
    message.value = error.response?.data?.message || error.message || "An error occurred during email verification.";
  } finally {
    return CloseModal();
  }
});
</script>

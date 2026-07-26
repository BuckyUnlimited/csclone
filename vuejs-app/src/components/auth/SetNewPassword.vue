<template>
  <div class="login-page">
    <div class="login-box">
      <div class="card card-outline card-primary">
        <div class="card-header text-center">
          <router-link to="/" class="h1"><b>Admin</b>LTE</router-link>
        </div>
        <div class="card-body">
          <p class="login-box-msg">Enter your new password</p>
          <form @submit.prevent="setNewPassword">
            <div class="input-group mb-3">
              <input v-model="user.password" type="password" class="form-control"
                :class="{ 'is-invalid': !!userError.password }" placeholder="Password" autocomplete />
              <div class="input-group-append">
                <div class="input-group-text">
                  <span class="fas fa-lock"></span>
                </div>
              </div>
              <div class="invalid-feedback">
                {{ userError.password }}
              </div>
            </div>
            <div class="input-group mb-3">
              <input v-model="user.password_confirmation" type="password" class="form-control"
                placeholder="Confirm Password" autocomplete />
              <div class="input-group-append">
                <div class="input-group-text">
                  <span class="fas fa-lock"></span>
                </div>
              </div>
            </div>
            <div class="row">
              <div class="col-8"></div>
              <div class="col-4">
                <button type="submit" class="btn btn-primary btn-block">Reset</button>
              </div>
            </div>
          </form>
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
import Swal from "sweetalert2";
import { reactive } from "vue";
import { useRoute, useRouter } from "vue-router";
import { LoadingModal, MessageModal, CloseModal } from "@/functions/swal";
const route = useRoute();
const router = useRouter();

const user = reactive({
  password: "",
  password_confirmation: "",
});

const userError = reactive({
  password: "",
});

const defaultUser = JSON.parse(JSON.stringify(user));
const defaultUserError = JSON.parse(JSON.stringify(userError));

function resetAllState() {
  Object.assign(user, defaultUser);
  Object.assign(userError, defaultUserError);
}

async function setNewPassword() {
  try {
    LoadingModal('Setting new password...');
    const response = await axios.post(new URL(route.query['forwarded-url']), user);
    resetAllState();
    await MessageModal({ icon: "success", title: "Success", text: response.data.message }, () => {
      router.push({ name: 'auth.signin' });
    });
  } catch (error) {
    const { response } = error;
    if (!response) {
      return MessageModal({ icon: "error", title: "Error", text: error.message });
    }
    const { status, data } = response;
    if (status === 422) {
      Object.keys(userError).forEach((key) => {
        userError[key] = data.errors[key]
          ? data.errors[key][0]
          : "";
      });
      return CloseModal();
    }
    return MessageModal({ icon: "error", title: "Error", text: data.message });
  }
}
</script>

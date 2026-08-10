<template>
  <div class="content-wrapper" style="min-height: 1416px">
    <!-- Content Header (Page header) -->
    <section class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-6">
            <h1>Profile</h1>
          </div>
          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
              <li class="breadcrumb-item">
                <router-link :to="{ name: 'dashboard' }">Home</router-link>
              </li>
              <li class="breadcrumb-item active">Profile</li>
            </ol>
          </div>
        </div>
      </div>
      <!-- /.container-fluid -->
    </section>

    <!-- Main content -->
    <section class="content">
      <div class="container-fluid">
        <div class="row">
          <div class="col-md-4">
            <!-- Profile Image -->
            <div class="card card-primary card-outline">
              <div class="card-body box-profile">
                <div class="text-center">
                  <img class="profile-user-img img-fluid img-circle" :src="tempImage" alt="User profile picture" />
                  <input @change="onChangeImage" type="file" class="d-none"
                    :accept="allowedExtensions.map((ext) => '.' + ext).join(', ')" id="file-input" />
                  <div class="mt-1">
                    <label :for="'file-input'">
                      <a type="button" class="m-1 btn btn-primary btn-sm"><i class="fas fa-upload"></i></a>
                    </label>
                    <a type="button" @click="onDeleteImage()" class="m-1 btn btn-danger btn-sm"><i
                        class="fas fa-trash"></i></a>
                    <a type="button" @click="onResetImage()" class="m-1 btn btn-secondary btn-sm"><i
                        class="fas fa-undo-alt"></i></a>
                    <a v-if="imageChanged" type="button" @click="saveProfileImage()"
                      class="m-1 btn btn-success btn-sm"><i class="fas fa-check"></i></a>
                  </div>
                </div>

                <h3 class="profile-username text-center">{{ userStore.name }}</h3>

                <p class="text-muted text-center">{{ userStore.email }}</p>
              </div>
            </div>
          </div>
          <div class="col-md-8">
            <div class="card">
              <div class="card-header p-2">
                <ul class="nav nav-pills">
                  <li class="nav-item">
                    <a class="nav-link active" href="#password_settings" data-toggle="tab">Password Settings</a>
                  </li>
                </ul>
              </div>
              <div class="card-body">
                <div class="tab-content">
                  <div class="active tab-pane" id="password_settings">
                    <form @submit.prevent="savePassword" class="form-horizontal">
                      <div v-if="!userStore.password_null" class="form-group row">
                        <label class="col-sm-3 col-form-label">Current Password</label>
                        <div class="col-sm-9">
                          <input v-model="user.current_password" type="password" class="form-control"
                            placeholder="Current Password" :class="!!userError.current_password ? 'is-invalid' : ''" />
                          <div class="invalid-feedback">
                            {{ userError.current_password }}
                          </div>
                        </div>
                      </div>
                      <div class="form-group row">
                        <label class="col-sm-3 col-form-label">New Password</label>
                        <div class="col-sm-9">
                          <input v-model="user.new_password" type="password" class="form-control"
                            placeholder="New Password" :class="!!userError.new_password ? 'is-invalid' : ''" />
                          <div class="invalid-feedback">
                            {{ userError.new_password }}
                          </div>
                        </div>
                      </div>
                      <div class="form-group row">
                        <label class="col-sm-3 col-form-label">Confirm Password</label>
                        <div class="col-sm-9">
                          <input v-model="user.new_password_confirmation" type="password" class="form-control"
                            placeholder="Confirm Password" />
                        </div>
                      </div>

                      <div class="form-group row">
                        <div class="offset-sm-2 col-sm-10">
                          <button type="reset" class="mx-3 btn btn-danger">Cancel</button>
                          <button type="submit" class="mx-3 btn btn-outline-primary">
                            Save
                          </button>
                        </div>
                      </div>
                    </form>
                  </div>
                  <!-- /.tab-pane -->
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>
  </div>
</template>

<script setup>
import emptyImage from "@/assets/images/emptyImage.png";
import { CloseModal, LoadingModal, MessageModal } from "@/functions/swal";
import { useRouter } from "vue-router";
import { computed, reactive, ref, watch } from "vue";
import {
  apiChangePassword,
  apiCreatePassword,
  apiDeleteProfileImage,
  apiUpdateProfileImage,
} from "@/functions/api/auth";
import { useUserStore } from "@/stores/user";
const router = useRouter();
const userStore = useUserStore();

const user = reactive({
  current_password: "",
  new_password: "",
  new_password_confirmation: "",
});

const userError = reactive({
  current_password: "",
  new_password: "",
});

const defaultUser = JSON.parse(JSON.stringify(user));
const defaultUserError = JSON.parse(JSON.stringify(userError));

function resetAllState() {
  Object.assign(user, defaultUser);
  Object.assign(userError, defaultUserError);
}

async function savePassword() {
  try {
    LoadingModal('Saving password...');
    const response = userStore.password_null
      ? await apiCreatePassword(
        user.new_password,
        user.new_password_confirmation
      )
      : await apiChangePassword(
        user.current_password,
        user.new_password,
        user.new_password_confirmation
      );
    resetAllState();
    await MessageModal({ icon: "success", title: "Success", text: response.data.message, }, () => router.push({ name: "auth.signin" }));
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

const tempImage = ref(null);
const selectedImageFile = ref(null);
const profileImage = computed(() => userStore.profile_image);
watch(
  () => profileImage.value,
  (nv) => (tempImage.value = nv ?? emptyImage),
  { immediate: true }
);

const imageChanged = computed(
  () => tempImage.value !== (profileImage.value ?? emptyImage)
);
const allowedExtensions = ["jpg", "jpeg", "png"];

function onChangeImage(event) {
  const files = event.target.files;
  if (files && files.length > 0) {
    const extFile = files[0].name.split(".").pop()?.toLowerCase();
    if (!allowedExtensions.includes(extFile)) {
      return MessageModal({ icon: "error", title: "Error", text: "Only jpg/jpeg and png files are allowed!" });
    }
    const reader = new FileReader();
    reader.onloadend = function () {
      const img = new Image();
      img.onload = function () {
        const canvas = document.createElement("canvas");
        const ctx = canvas.getContext("2d");

        // Set canvas size to 454x454
        canvas.width = 454;
        canvas.height = 454;

        // Calculate crop dimensions (center crop)
        const size = Math.min(img.width, img.height);
        const x = (img.width - size) / 2;
        const y = (img.height - size) / 2;

        // Draw image cropped and resized to 454x454
        ctx.drawImage(img, x, y, size, size, 0, 0, 454, 454);

        canvas.toBlob((blob) => {
          if (!blob) {
            return MessageModal({ icon: "error", title: "Error", text: "Failed to process image. Please try again." });
          }

          selectedImageFile.value = new File([blob], "profile.png", { type: "image/png" });
          tempImage.value = canvas.toDataURL("image/png");
        }, "image/png");
      };
      img.src = reader.result;
    };
    reader.readAsDataURL(files[0]);
    event.target.value = null;
  }
}
function onDeleteImage() {
  selectedImageFile.value = null;
  tempImage.value = emptyImage;
}
function onResetImage() {
  selectedImageFile.value = null;
  tempImage.value = userStore.profile_image ? userStore.profile_image : emptyImage;
}

async function saveProfileImage() {
  try {
    LoadingModal('Saving profile image...');
    const isDeleting = tempImage.value === emptyImage;
    const response = isDeleting
      ? await apiDeleteProfileImage()
      : await apiUpdateProfileImage(selectedImageFile.value);
    userStore.profile_image = isDeleting ? null : response.data.profile_image;
    userStore.profile_thumbnail = isDeleting ? null : response.data.profile_thumbnail;
    selectedImageFile.value = null;
    await MessageModal({ icon: "success", title: "Success", text: response.data.message });
  } catch (error) {
    return MessageModal({ icon: "error", title: "Error", text: error.response?.data?.message || error.message });
  }
}
</script>

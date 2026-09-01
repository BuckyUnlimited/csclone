<template>
  <div class="content-wrapper" style="min-height: 1175px;">
    <div class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-6">
            <h1 class="m-0">Create Group Chat</h1>
          </div>
          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
              <li class="breadcrumb-item"><a href="#">Home</a></li>
              <li class="breadcrumb-item active">Create Group Chat</li>
            </ol>
          </div>
        </div>
      </div>
    </div>
    <div class="content">
      <div class="container-fluid">
        <!-- Profile Image -->
        <div class="card card-primary card-outline">
          <div class="card-body box-profile">
            <form @submit.prevent="createGroup">
              <div class="form-group text-center">
                <img class="profile-user-img img-fluid img-circle" :src="chat.avatar || emptyImage"
                  :class="{ 'is-invalid': !!chatError.avatar }" alt="User profile picture" />
                <div class="invalid-feedback">{{ chatError.avatar }}</div>
                <input @change="onChangeImage" :accept="allowedExtensions.map((ext) => '.' + ext).join(', ')"
                  type="file" class="d-none" id="file-input" />
                <div class="mt-1">
                  <label :for="'file-input'">
                    <a type="button" class="m-1 btn btn-primary btn-sm"><i class="fas fa-upload"></i></a>
                  </label>
                  <a type="button" @click="onDeleteImage" class="m-1 btn btn-danger btn-sm"><i
                      class="fas fa-trash"></i></a>
                </div>
              </div>
              <div class="form-group">
                <label for="userEmail">Name</label>
                <input type="text" class="form-control" v-model="chat.name"
                  :class="{ 'is-invalid': !!chatError.name }" />
                <div class="invalid-feedback">{{ chatError.name }}</div>
              </div>
              <div class="form-group">
                <label for="userEmail">Description</label>
                <textarea class="form-control" v-model="chat.description"
                  :class="{ 'is-invalid': !!chatError.description }"></textarea>
                <div class="invalid-feedback">{{ chatError.description }}</div>
              </div>
              <div class="form-group">
                <button type="submit" class="btn btn-primary btn-block">Create Group</button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
<script setup>
import { reactive, ref } from "vue";
import emptyImage from "@/assets/images/emptyImage.png";
import { MessageModal, LoadingModal, CloseModal } from "@/functions/swal";
import { apiCreateGroupChat } from "@/functions/api/chat";
import { useRouter } from "vue-router";
import { useRecentChatsStore } from "@/stores/recentChats";
const recentChatsStore = useRecentChatsStore();
const router = useRouter();

const selectedImage = ref(null);
const chat = reactive({
  id: null,
  name: "",
  description: "",
  avatar: null,
});
const chatError = reactive({
  name: "",
  description: "",
  avatar: "",
});

const defaultChat = JSON.parse(JSON.stringify(chat));
const defaultChatError = JSON.parse(JSON.stringify(chatError));

function resetAllState() {
  Object.assign(chat, defaultChat);
  Object.assign(chatError, defaultChatError);
}

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

          selectedImage.value = new File([blob], "profile.png", { type: "image/png" });
          chat.avatar = canvas.toDataURL("image/png");
        }, "image/png");
      };
      img.src = reader.result;
    };
    reader.readAsDataURL(files[0]);
    event.target.value = null;
  }
}
function onDeleteImage() {
  selectedImage.value = null;
  chat.avatar = null;
}

async function createGroup() {
  try {
    LoadingModal('Creating Group...');
    const payload = {
      name: chat.name,
      description: chat.description,
      avatar: selectedImage.value,
    };
    const response = await apiCreateGroupChat(payload);
    const { data } = response;
    resetAllState();
    return MessageModal({ icon: "success", title: "Success", text: data.message }, () => {
      recentChatsStore.syncChat(data.chat);
      router.push({ name: "chat.box", params: { chatId: data.chat.id } });
    });
  } catch (error) {
    const { response } = error;
    if (!response) {
      return MessageModal({ icon: "error", title: "Error", text: error.message });
    }
    const { status, data } = response;
    if (status === 422) {
      Object.keys(chatError).forEach((key) => {
        chatError[key] = data.errors[key]
          ? data.errors[key][0]
          : "";
      });
      return CloseModal();
    }
    return MessageModal({ icon: "error", title: "Error", text: data.message });
  }
}
</script>

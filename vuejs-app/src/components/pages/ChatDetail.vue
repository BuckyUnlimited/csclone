<template>
  <div class="content-wrapper" style="min-height: 1175px;">
    <div class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-6">
            <h1 class="m-0">Update Group Chat</h1>
          </div>
          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
              <li class="breadcrumb-item"><a href="#">Home</a></li>
              <li class="breadcrumb-item active">Update Group Chat</li>
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
            <form @submit.prevent="updateGroup">
              <div class="form-group text-center">
                <img class="profile-user-img img-fluid img-circle" :src="chat.avatar || emptyImage"
                  :class="{ 'is-invalid': !!chatError.avatar }" alt="User profile picture" />
                <div class="invalid-feedback">{{ chatError.avatar }}</div>
                <input @change="onChangeImage" :accept="allowedExtensions.map((ext) => '.' + ext).join(', ')"
                  type="file" class="d-none" id="file-input" />
                <div class="mt-1" v-if="chatType === 'group'">
                  <label :for="'file-input'">
                    <a type="button" class="m-1 btn btn-primary btn-sm"><i class="fas fa-upload"></i></a>
                  </label>
                  <a type="button" @click="onDeleteImage" class="m-1 btn btn-danger btn-sm"><i
                      class="fas fa-trash"></i></a>
                </div>
              </div>
              <div class="form-group">
                <label>Name</label>
                <input :disabled="chatType === 'personal'" type="text" class="form-control" v-model="chat.name"
                  :class="{ 'is-invalid': !!chatError.name }" />
                <div class="invalid-feedback">{{ chatError.name }}</div>
              </div>
              <div class="form-group">
                <label>Description</label>
                <textarea :disabled="chatType === 'personal'" class="form-control" v-model="chat.description"
                  :class="{ 'is-invalid': !!chatError.description }"></textarea>
                <div class="invalid-feedback">{{ chatError.description }}</div>
              </div>
              <template v-if="chatType === 'group'">
                <div class="form-group">
                  <button type="submit" class="btn btn-primary btn-block">Update Chat</button>
                </div>
                <div class="form-group">
                  <button type="button" @click="leaveGroupChat" class="btn btn-warning btn-block">Leave Chat</button>
                </div>
                <div class="form-group">
                  <button type="button" @click="deleteChat" class="btn btn-danger btn-block">Delete Chat</button>
                </div>
              </template>
              <template v-else>
                <div class="form-group">
                  <button type="button" @click="deleteChat" class="btn btn-danger btn-block">Delete Chat</button>
                </div>
              </template>
            </form>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
<script setup>
import { reactive, ref, watch } from "vue";
import emptyImage from "@/assets/images/emptyImage.png";
import { MessageModal, LoadingModal, CloseModal } from "@/functions/swal";
import { apiUpdateGroupChat, apiReadChat, apiDeleteChat, apiLeaveGroupChat } from "@/functions/api/chat";
import { useRouter } from "vue-router";
import { useRecentChatsStore } from "@/stores/recentChats";
import Swal from "sweetalert2";
const recentChatsStore = useRecentChatsStore();
const router = useRouter();

const props = defineProps({
  chatId: {
    required: true,
  },
});

watch(
  () => props.chatId,
  async (newChatId) => {
    await readChat();
  },
  { immediate: true }
);

const chatType = ref('personal'); // Default to 'personal', will be updated after reading chat

const selectedImage = ref(null);
const originalAvatar = ref(null);
const avatarDeleted = ref(false);
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
          avatarDeleted.value = false;
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
  avatarDeleted.value = true;
}

async function readChat() {
  try {
    LoadingModal("Loading...");
    const response = await apiReadChat(props.chatId);
    const { data } = response;
    Object.assign(chat, data.chat);
    originalAvatar.value = data.chat.avatar;
    avatarDeleted.value = false;
    selectedImage.value = null;
    chatType.value = data.chat.type; // Update chat type based on the fetched data
    CloseModal();
  } catch (error) {
    const { response } = error;
    if (!response) {
      return MessageModal({ icon: "error", title: "Error", text: error.message });
    }
    const { data } = response;
    return MessageModal({ icon: "error", title: "Error", text: data.message });
  }
}

async function updateGroup() {
  try {
    LoadingModal('Updating Group...');

    const payload = {
      name: chat.name,
      description: chat.description,
    };

    // Handle avatar updates based on user actions
    if (selectedImage.value) {
      // New avatar file uploaded - send the file
      payload.avatar = selectedImage.value;
    } else if (avatarDeleted.value) {
      // Avatar was explicitly deleted - send null to delete
      payload.avatar = null;
    }
    // If neither condition is true, don't include avatar (keeps existing)

    const response = await apiUpdateGroupChat(props.chatId, payload);
    const { data } = response;
    resetAllState();
    Object.assign(chat, data.chat);
    originalAvatar.value = data.chat.avatar;
    avatarDeleted.value = false;
    selectedImage.value = null;
    chatType.value = data.chat.type; // Update chat type based on the fetched data
    recentChatsStore.syncChat(data.chat);
    return MessageModal({ icon: "success", title: "Success", text: data.message });
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

async function deleteChat() {
  Swal.fire({
    icon: "question",
    title: "Delete Chat",
    text: "Are you sure you want to delete this chat?",
    showCancelButton: true,
    confirmButtonColor: "#d33",
    confirmButtonText: "Yes, delete it!",
  }).then(async (result) => {
    if (result.isConfirmed) {
      try {
        LoadingModal('Deleting chat...');
        const response = await apiDeleteChat(props.chatId);
        const { data } = response;
        recentChatsStore.removeChat(props.chatId);
        return MessageModal({ icon: "success", title: "Success", text: data.message }, () => {
          router.push({ name: "dashboard" });
        });
      } catch (error) {
        return MessageModal({ icon: "error", title: "Error", text: error.response?.data?.message || error.message });
      }
    }
  });
}

async function leaveGroupChat() {
  Swal.fire({
    icon: "question",
    title: "Leave Group Chat",
    text: "Are you sure you want to leave this group chat?",
    showCancelButton: true,
    confirmButtonColor: "#d33",
    confirmButtonText: "Yes, leave it!",
  }).then(async (result) => {
    if (result.isConfirmed) {
      try {
        LoadingModal('Leaving group...');
        const response = await apiLeaveGroupChat(props.chatId);
        const { data } = response;
        recentChatsStore.removeChat(props.chatId);
        return MessageModal({ icon: "success", title: "Success", text: data.message }, () => {
          router.push({ name: "dashboard" });
        });
      } catch (error) {
        return MessageModal({ icon: "error", title: "Error", text: error.response?.data?.message || error.message });
      }
    }
  });
}
</script>

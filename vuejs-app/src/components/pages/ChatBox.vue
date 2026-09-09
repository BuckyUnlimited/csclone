<template>
  <div class="content-wrapper">
    <section class="content pt-3">
      <div class="container-fluid">
        <div class="card card-primary card-outline direct-chat direct-chat-primary">
          <div class="card-header d-flex align-items-center">
            <h3 class="card-title">
              <img class="direct-chat-img elevation-3" :src="emptyImage" />
            </h3>
            <h3 class="card-title mx-3"></h3>
            <div class="card-tools ml-auto">
              <RouterLink :to="{ name: 'chat.details', params: { chatId: props.chatId } }" type="button"
                class="btn btn-tool">
                <i class="fas fa-list text-primary"></i>
              </RouterLink>
            </div>
          </div>
          <div class="card-body">
            <div class="direct-chat-messages" style="min-height: calc(100vh - 280px)">
              <template v-for="message in chat?.messages" :key="message.id">
                <div class="direct-chat-msg" :class="isOwnMessage(message) ? 'right' : 'left'">
                  <div class="direct-chat-infos clearfix">
                    <span class="direct-chat-timestamp mx-1"
                      :class="isOwnMessage(message) ? 'float-right' : 'float-left'">{{
                        formatChatTime(message.created_at) }}</span>
                  </div>
                  <img class="direct-chat-img" :src="message.creator.profile_thumbnail || emptyImage"
                    alt="message user image">
                  <div class="direct-chat-text"
                    :class="isOwnMessage(message) ? 'text-right float-right' : 'text-left float-left'">
                    <template v-if="editingMessageId === message.id">
                      <div class="input-group input-group-sm">
                        <input v-model="editContent" type="text" class="form-control" maxlength="5000"
                          @keyup.enter="saveEdit(message.id)" @keyup.esc="cancelEdit">
                        <span class="input-group-append">
                          <button type="button" class="btn btn-success btn-sm" @click="saveEdit(message.id)"
                            :disabled="!editContent.trim()">
                            <i class="fas fa-check"></i>
                          </button>
                          <button type="button" class="btn btn-secondary btn-sm" @click="cancelEdit">
                            <i class="fas fa-times"></i>
                          </button>
                        </span>
                      </div>
                    </template>
                    <template v-else-if="message.type === 'voice'">
                      <audio controls :src="message.fileBlob" style="max-width: 250px;"></audio>
                    </template>
                    <template v-else>
                      {{ message.content }}
                    </template>
                  </div>
                </div>
                <div class="direct-chat-infos clearfix">
                  <span class="direct-chat-name" :class="isOwnMessage(message) ? 'float-right' : 'float-left'">{{
                    message.creator.name }}</span>
                  <i v-if="isOwnMessage(message)" @click="deleteMessage(message.id)"
                    class="fas fa-trash-alt text-danger float-right mt-1 mx-1" style="cursor: pointer;"
                    title="Delete message"></i>
                  <i v-if="isOwnMessage(message) && isTextMessage(message)" @click="startEditMessage(message)"
                    class="fas fa-edit text-primary float-right mt-1 mx-1" style="cursor: pointer;"
                    title="Edit message"></i>
                </div>
                <hr>
              </template>

            </div>
            <!--/.direct-chat-messages-->
          </div>
          <div class="card-footer">
            <form @submit.prevent="sendMessage">
              <div class="input-group">
                <template v-if="isRecording">
                  <span class="form-control d-flex align-items-center text-danger">
                    <i class="fas fa-circle mr-2"></i> {{ formatRecordingTime(recordingSeconds) }}
                  </span>
                </template>
                <template v-else-if="recordedBlob">
                  <span class="form-control d-flex align-items-center">
                    <i class="fas fa-microphone mr-2 text-secondary"></i> {{ formatRecordingTime(recordingSeconds) }}
                  </span>
                </template>
                <template v-else>
                  <input v-model="messageContent" type="text" name="message" placeholder="Type Message ..."
                    class="form-control" maxlength="5000">
                </template>
                <span class="input-group-append">
                  <button v-if="recordedBlob && !isRecording" type="button" class="btn btn-secondary"
                    @click="resetRecordingState">
                    <i class="fas fa-trash-alt"></i>
                  </button>
                  <button v-if="!recordedBlob" type="button" class="btn"
                    :class="isRecording ? 'btn-danger' : 'btn-secondary'" @click="toggleRecording">
                    <i class="fas fa-microphone"></i>
                  </button>
                  <button type="submit" class="btn btn-primary"
                    :disabled="!recordedBlob && !messageContent.trim()">Send</button>
                </span>
              </div>
            </form>
          </div>
        </div>
      </div>
    </section>
  </div>
</template>

<script setup>
import { watch, ref, onMounted, computed } from "vue";
import emptyImage from "@/assets/images/emptyImage.png";
import { useUserStore } from '@/stores/user';
import { useRecentChatsStore } from "@/stores/recentChats";
import { formatChatTime } from "@/functions/datetime";
import { apiGetChatMessages, apiCreateChatMessage, apiCreateVoiceChatMessage, apiUpdateChatMessage, apiDeleteChatMessage, apiMarkAllChatMessagesAsSeen } from "@/functions/api/chat";
import $ from "jquery";
import { apiReadChat } from "@/functions/api/chat";
import Swal from "sweetalert2";
import { MessageModal } from "@/functions/swal";

const userStore = useUserStore();
const recentChatsStore = useRecentChatsStore();

const props = defineProps({
  chatId: {
    required: true,
  },
});

// Local state for messages (independent of store)

// Message input
const messageContent = ref('');
const chat = computed(() => recentChatsStore.getChatById(props.chatId));

// Edit state
const editingMessageId = ref(null);
const editContent = ref('');


function isOwnMessage(message) {
  if (!message) return false;
  return (message.creator.id === userStore.id);
}

function isTextMessage(message) {
  return message?.type === 'text';
}

function startEditMessage(message) {
  editingMessageId.value = message.id;
  editContent.value = message.content;
}

function cancelEdit() {
  editingMessageId.value = null;
  editContent.value = '';
}


// Voice recording state
const isRecording = ref(false);
const mediaRecorder = ref(null);
const audioChunks = ref([]);
const recordedBlob = ref(null);
const recordingSeconds = ref(0);
let recordingTimer = null;

function resetRecordingState() {
  recordedBlob.value = null;
  recordingSeconds.value = 0;
  clearInterval(recordingTimer);
  mediaRecorder.value?.stop();
  isRecording.value = false;
}
function formatRecordingTime(seconds) {
  const m = Math.floor(seconds / 60);
  const s = seconds % 60;
  return `${m}:${s.toString().padStart(2, '0')}`;
}

async function toggleRecording() {
  if (isRecording.value) {
    clearInterval(recordingTimer);
    mediaRecorder.value?.stop();
    isRecording.value = false;
  } else {
    try {
      const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
      mediaRecorder.value = new MediaRecorder(stream);
      audioChunks.value = [];
      recordingSeconds.value = 0;

      mediaRecorder.value.ondataavailable = (e) => {
        audioChunks.value.push(e.data);
      };

      mediaRecorder.value.onstop = () => {
        recordedBlob.value = new Blob(audioChunks.value, { type: 'audio/webm' });
        stream.getTracks().forEach(track => track.stop());
      };

      mediaRecorder.value.start();
      isRecording.value = true;
      recordingTimer = setInterval(() => {
        recordingSeconds.value++;
        if (recordingSeconds.value >= 60) { // Limit recording to 60 seconds
          clearInterval(recordingTimer);
          mediaRecorder.value?.stop();
          isRecording.value = false;
        }
      }, 1000);
    } catch (error) {
      return MessageModal({ icon: "error", title: "Error", text: "Microphone access denied." });
    }
  }
}

async function sendVoiceMessage(blob) {
  try {
    const response = await apiCreateVoiceChatMessage(props.chatId, blob);
    recentChatsStore.syncChatMessage(props.chatId, response.data.chat_message);
    scrollToBottom();
  } catch (error) {
    return MessageModal({ icon: "error", title: "Error", text: error.response?.data?.message || error.message });
  }
}

async function saveEdit(messageId) {
  if (!editContent.value.trim()) {
    return;
  }

  try {
    const response = await apiUpdateChatMessage(props.chatId, messageId, editContent.value);
    recentChatsStore.syncChatMessage(props.chatId, response.data.chat_message);
    editingMessageId.value = null;
    editContent.value = '';
  } catch (error) {
    return MessageModal({ icon: "error", title: "Error", text: error.response?.data?.message || error.message });
  }
}


async function sendMessage() {
  if (recordedBlob.value) {
    await sendVoiceMessage(recordedBlob.value);
    resetRecordingState();
    return;
  }

  if (!messageContent.value.trim()) {
    return;
  }

  try {
    const response = await apiCreateChatMessage(props.chatId, messageContent.value);

    // Add message to store
    recentChatsStore.syncChatMessage(props.chatId, response.data.chat_message);

    // Clear input
    messageContent.value = '';

    scrollToBottom();
  } catch (error) {
    return MessageModal({ icon: "error", title: "Error", text: error.response?.data?.message || error.message });
  }
}

async function deleteMessage(messageId) {
  Swal.fire({
    icon: "warning",
    title: "Delete Message",
    text: "Are you sure you want to delete this message?",
    showCancelButton: true,
    confirmButtonColor: "#d33",
    confirmButtonText: "Yes, delete it!",
  }).then(async (result) => {
    if (result.isConfirmed) {
      try {
        const response = await apiDeleteChatMessage(props.chatId, messageId);
        recentChatsStore.removeChatMessage(props.chatId, messageId);
        return MessageModal({ icon: "success", title: "Success", text: response.data.message });
      } catch (error) {
        return MessageModal({ icon: "error", title: "Error", text: error.response?.data?.message || error.message });
      }
    }
  });
}


const currentPage = ref(1);
const lastPage = ref(1);
const pageSize = ref(25);
const isLoadingMore = ref(false);

async function loadChat() {
  try {
    if (chat.value) {
      return; // Chat already exists in store
    }
    // If not in store, fetch from API
    const response = await apiReadChat(props.chatId);
    recentChatsStore.syncChat(response.data.chat);
  } catch (error) {
    console.error('Error loading chat:', error);
  }
}

async function loadMessages(page = 1) {
  try {
    // Fetch from API
    const response = await apiGetChatMessages(props.chatId, {
      page: page,
      per_page: pageSize.value,
    });

    recentChatsStore.syncMultiChatMessages(props.chatId, [...response.data.chat_messages, ...chat.value.messages]);

    currentPage.value = response.data.meta.current_page;
    lastPage.value = response.data.meta.last_page;
  } catch (error) {
    console.error('Error loading messages:', error);
  }
}

async function markMessagesAsSeen() {
  try {
    await apiMarkAllChatMessagesAsSeen(props.chatId);
  } catch (error) {
    console.error('Error marking messages as seen:', error);
  }
}

async function loadMoreMessages() {
  if (isLoadingMore.value) {
    return;
  }

  if (currentPage.value >= lastPage.value) {
    return;
  }

  isLoadingMore.value = true;

  await loadMessages(currentPage.value + 1);

  isLoadingMore.value = false;
}

function scrollToBottom() {
  const chatContainer = $('.direct-chat-messages');
  if (chatContainer.length > 0) {
    chatContainer.scrollTop(chatContainer[0].scrollHeight);
  }
}

function setupScrollListener() {
  const chatContainer = $('.direct-chat-messages');

  // Remove existing listener
  chatContainer.off('scroll');

  // Add scroll listener for infinite scroll
  chatContainer.on('scroll', async function () {
    if (isLoadingMore.value) {
      return;
    }

    if (currentPage.value >= lastPage.value) {
      return;
    }

    if (currentPage.value >= lastPage.value) {
      return; // No more pages to load
    }
    // Load more when scrolling near the top
    const scrollTop = this.scrollTop;
    if (scrollTop > 150) {
      return; // Not near the top yet
    }
    const previousScrollHeight = this.scrollHeight;
    await loadMoreMessages();

    // Maintain scroll position after prepending messages
    const newScrollHeight = this.scrollHeight;
    this.scrollTop = newScrollHeight - previousScrollHeight + scrollTop;
  });
}

// Watch for chat changes
watch(() => props.chatId, async () => {
  // Reset state
  isLoadingMore.value = false;
  currentPage.value = 1;
  lastPage.value = 1;
  messageContent.value = ''; // Clear message input when switching chats
  editingMessageId.value = null; // Cancel any ongoing edit
  editContent.value = '';
  resetRecordingState(); // Reset recording state when switching chatss

  await loadChat();
  // await loadMessages(1);
  scrollToBottom();
  setupScrollListener();
  await markMessagesAsSeen();
});

// Initial load
onMounted(async () => {
  await loadChat();
  await loadMessages(1);
  scrollToBottom();
  setupScrollListener();
  await markMessagesAsSeen();
});

</script>

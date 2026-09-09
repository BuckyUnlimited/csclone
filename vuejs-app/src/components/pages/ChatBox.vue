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
                    {{ message.content }}
                  </div>
                </div>
                <div class="direct-chat-infos clearfix">
                  <span class="direct-chat-name" :class="isOwnMessage(message) ? 'float-right' : 'float-left'">{{
                    message.creator.name }}</span>
                  <i v-if="isOwnMessage(message)" class="fas fa-trash-alt text-danger float-right mt-1 mx-3"></i>
                </div>
                <hr>
              </template>

            </div>
            <!--/.direct-chat-messages-->
          </div>
          <div class="card-footer">
            <form action="#" method="post">
              <div class="input-group">
                <input type="text" name="message" placeholder="Type Message ..." class="form-control">
                <span class="input-group-append">
                  <button type="button" class="btn btn-primary">Send</button>
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
import { watch, ref, onMounted, nextTick, computed } from "vue";
import emptyImage from "@/assets/images/emptyImage.png";
import { useUserStore } from '@/stores/user';
import { useRecentChatsStore } from "@/stores/recentChats";
import { formatChatTime } from "@/functions/datetime";
import { apiGetChatMessages } from "@/functions/api/chat";
import $ from "jquery";
import { apiReadChat } from "@/functions/api/chat";

const userStore = useUserStore();
const recentChatsStore = useRecentChatsStore();

const props = defineProps({
  chatId: {
    required: true,
  },
});

// Local state for messages (independent of store)
const chat = computed(() => recentChatsStore.getChatById(props.chatId));


function isOwnMessage(message) {
  if (!message) return false;
  return (message.creator.id === userStore.id);
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
    console.log('Scrolling to bottom');
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

  await loadChat();
  // await loadMessages(1);
  scrollToBottom();
  setupScrollListener();
});

// Initial load
onMounted(async () => {
  await loadChat();
  await loadMessages(1);
  scrollToBottom();
  setupScrollListener();
});

</script>

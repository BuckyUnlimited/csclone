<template>
  <div class="modal fade" ref="AddChatMemberModal" aria-modal="true" role="dialog">
    <div class="modal-dialog modal-lg">
      <div class="modal-content">
        <div class="modal-header">
          <h4 class="modal-title">Add Chat Member</h4>
          <button type="button" class="close" @click="hideModal" aria-label="Close">
            <span aria-hidden="true">×</span>
          </button>
        </div>
        <div class="modal-body">
          <CustomTablePaginated :title="'Users'" :data="users" :columns="columns" v-model:currentPage="currentPage"
            v-model:lastPage="lastPage" v-model:total="total" v-model:pageSize="pageSize" v-model:keyword="keyword"
            @search-change="handleSearchChange" />
        </div>
      </div>
    </div>
  </div>

</template>

<script setup>
import emptyImage from "@/assets/images/emptyImage.png";
import { ref, watch, h } from "vue";
import { LoadingModal, CloseModal, MessageModal } from "@/functions/swal";
import { apiAddGroupChatMember } from "@/functions/api/chat";
import $ from "jquery";
import { apiGetChatUsers } from "@/functions/api/chat";
import CustomTablePaginated from "@/components/includes/controls/CustomTablePaginated.vue";

const props = defineProps({
  chatId: {
    required: true,
  },
  members: {
    type: Array,
    required: true,
  },
  onMemberAdded: {
    type: Function,
    required: true,
  },
});

const AddChatMemberModal = ref();
const users = ref([]);
const columns = [
  {
    header: "Profile",
    accessorKey: "profile_thumbnail",
    cell: (info) => h("img", {
      src: info.getValue() || emptyImage,
      alt: "Profile Thumbnail",
      class: "img-circle elevation-2",
      style: "width: 40px; height: 40px; object-fit: cover;"
    })
  },
  {
    header: "Name",
    accessorKey: "name",
  },
  {
    header: "Email",
    accessorKey: "email",
  },
  {
    header: "Action",
    accessorKey: "id",
    cell: (cell) =>
      h("button", {
        disabled: isMember(cell.getValue()),  // Disable if already a member
        class: "btn btn-primary btn-sm",
        onClick: () => addMember(cell.getValue())
      }, "Add")
  }
];

// Pagination state
const currentPage = ref(1);
const pageSize = ref(25);
const total = ref(0);
const lastPage = ref(1);
const keyword = ref("");  // Track search keyword

async function generateUsers(searchKeyword = "", page = 1, per_page = 25) {
  try {
    LoadingModal();
    const response = await apiGetChatUsers({
      keyword: searchKeyword,
      page: page,
      per_page: per_page,
    });

    // Update all pagination state from API response
    users.value = response.data.users;
    currentPage.value = response.data.meta.current_page;
    pageSize.value = response.data.meta.per_page;
    total.value = response.data.meta.total;
    lastPage.value = response.data.meta.last_page;
    return CloseModal();
  } catch (error) {
    return MessageModal({ icon: "error", title: "Error", text: error.response?.data?.message || error.message });
  }
}

// Watch for pagination changes to fetch data
watch(currentPage, async (newPage, oldPage) => {
  if (newPage !== oldPage) {
    await generateUsers(keyword.value, newPage, pageSize.value);
  }
});

watch(pageSize, async (newSize, oldSize) => {
  if (newSize !== oldSize) {
    await generateUsers(keyword.value, 1, newSize);
  }
});

async function handleSearchChange(searchKeyword) {
  await generateUsers(searchKeyword, 1, pageSize.value);
}

async function addMember(userId) {
  try {
    const response = await apiAddGroupChatMember(props.chatId, userId);
    props.onMemberAdded(); // Call the parent function to refresh the member list
    return MessageModal({ icon: "success", title: "Success", text: response.data.message });
  } catch (error) {
    return MessageModal({ icon: "error", title: "Error", text: error.response?.data?.message || error.message });
  }
}
function isMember(userId) {
  return props.members.some(member => member.user.id === userId);
}
function hideModal() {
  $(AddChatMemberModal.value).modal('hide');
}
function showModal() {
  $(AddChatMemberModal.value).modal('show');
}
defineExpose({
  showModal,
  hideModal
});


</script>

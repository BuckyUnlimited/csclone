<template>
  <div class="content-wrapper" style="min-height: 1416px">
    <section class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-6">
            <h1>Users</h1>
          </div>
          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
              <li class="breadcrumb-item">
                <router-link :to="{ name: 'dashboard' }">Home</router-link>
              </li>
            </ol>
          </div>
        </div>
      </div>
    </section>

    <section class="content">
      <div class="container-fluid">
        <CustomTablePaginated :title="'Users'" :data="users" :columns="columns" v-model:currentPage="currentPage"
          v-model:lastPage="lastPage" v-model:total="total" v-model:pageSize="pageSize" v-model:keyword="keyword"
          @search-change="handleSearchChange" />
      </div>
    </section>
  </div>
  <div class="modal fade" ref="userModal" aria-modal="true" role="dialog">
    <form @submit.prevent="saveUser">
      <div class="modal-dialog modal-lg">
        <div class="modal-content">
          <div class="modal-header">
            <h4 class="modal-title">User</h4>
            <button type="button" class="close" @click="hideModal" aria-label="Close">
              <span aria-hidden="true">×</span>
            </button>
          </div>
          <div class="modal-body">
            <div class="form-group">
              <label for="userName">Name</label>
              <input type="text" class="form-control" v-model="user.name" :class="{ 'is-invalid': !!userError.name }" />
              <div class="invalid-feedback">{{ userError.name }}</div>
            </div>
            <div class="form-group">
              <label for="userEmail">Email</label>
              <input type="email" class="form-control" v-model="user.email"
                :class="{ 'is-invalid': !!userError.email }" />
              <div class="invalid-feedback">{{ userError.email }}</div>
            </div>
            <div class="form-group">
              <label for="userPassword">Password</label>
              <input type="password" class="form-control" v-model="user.password"
                :class="{ 'is-invalid': !!userError.password }" />
              <div class="invalid-feedback">{{ userError.password }}</div>
            </div>
          </div>
          <div class="modal-footer justify-content-between">
            <button type="button" class="btn btn-default" @click="hideModal">
              Close
            </button>
            <button type="submit" class="btn btn-primary">Save changes</button>
          </div>
        </div>
      </div>
    </form>
  </div>
</template>

<script setup>
import $ from "jquery";
import Swal from "sweetalert2";
import { apiGetUsers, apiCreateUser, apiUpdateUser, apiReadUser, apiDeleteUser, apiToggleUserStatus } from "@/functions/api/user";
import { CloseModal, LoadingModal, MessageModal } from "@/functions/swal";
import { onMounted, ref, h, reactive, watch } from "vue";
import CustomTablePaginated from "@/components/includes/controls/CustomTablePaginated.vue";

const userModal = ref(null);
const users = ref([]);

// Pagination state
const currentPage = ref(1);
const pageSize = ref(25);
const total = ref(0);
const lastPage = ref(1);
const keyword = ref("");  // Track search keyword

const user = reactive({
  id: null,
  name: "",
  email: "",
  password: "",
});

const userError = reactive({
  name: "",
  email: "",
  password: "",
});

const defaultUser = JSON.parse(JSON.stringify(user));
const defaultUserError = JSON.parse(JSON.stringify(userError));

function resetAllState() {
  Object.assign(user, defaultUser);
  Object.assign(userError, defaultUserError);
}

const columns = [
  {
    header: "ID",
    accessorKey: "id",
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
    header: "Status",
    accessorKey: "status",
    cell: ({
      row: {
        original: { status },
      },
    }) => h(
      "span",
      {
        class: status === "ENABLED" ? "badge badge-success" : "badge badge-danger",
      },
      status
    ),
  },
  {
    accessorKey: "action",
    header: () => [
      "Actions",
      h(
        "button",
        {
          onClick: () => showModal(),
          class: "btn btn-sm btn-success ml-3",
        },
        "Create"
      ),
    ],
    cell: ({
      row: {
        original: { id, status },
      },
    }) => [
        // delete btn
        h(
          "button",
          {
            onClick: () => removeUser(id),
            class: "btn btn-sm btn-outline-danger mx-1",
          },
          h("i", { class: "fa fa-trash" })
        ),
        // view btn
        h(
          "button",
          {
            onClick: () => viewUser(id),
            class: "btn btn-sm btn-outline-secondary mx-1",
          },
          h("i", { class: "fa fa-pen" })
        ),
        // toggle status btn
        h(
          "button",
          {
            onClick: () => toggleUserStatus(id),
            class: status === "ENABLED" ? "btn btn-sm btn-danger mx-1" : "btn btn-sm btn-success mx-1",
            title: status === "ENABLED" ? "Disable User" : "Enable User",
          },
          h("i", { class: status === "ENABLED" ? "fa fa-ban" : "fa fa-check" })
        ),
      ],
    enableSorting: false,
  },
];

onMounted(async () => {
  $(userModal.value).on("hide.bs.modal", function () {
    resetAllState();
  });
  try {
    LoadingModal();
    await generateUsers(keyword.value, currentPage.value, pageSize.value);
    return CloseModal();
  } catch (error) {
    return MessageModal({ icon: "error", title: "Error", text: error.response?.data?.message || error.message });
  }
});

async function generateUsers(searchKeyword = "", page = 1, per_page = 25) {
  const response = await apiGetUsers({
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
}

// Watch for pagination changes to fetch data
watch(currentPage, async (newPage, oldPage) => {
  if (newPage !== oldPage) {
    try {
      LoadingModal();
      await generateUsers(keyword.value, newPage, pageSize.value);
      CloseModal();
    } catch (error) {
      MessageModal({ icon: "error", title: "Error", text: error.response?.data?.message || error.message });
    }
  }
});

watch(pageSize, async (newSize, oldSize) => {
  if (newSize !== oldSize) {
    try {
      LoadingModal();
      currentPage.value = 1; // Reset to first page when changing page size
      await generateUsers(keyword.value, 1, newSize);
      CloseModal();
    } catch (error) {
      MessageModal({ icon: "error", title: "Error", text: error.response?.data?.message || error.message });
    }
  }
});

async function handleSearchChange(searchKeyword) {
  try {
    LoadingModal();
    await generateUsers(searchKeyword, 1, pageSize.value);
    CloseModal();
  } catch (error) {
    MessageModal({ icon: "error", title: "Error", text: error.response?.data?.message || error.message });
  }
}


async function saveUser() {
  try {
    LoadingModal();
    let response = null;
    if (user.id) {
      response = await apiUpdateUser(user.id, user);
      onUserUpdate(response.data.user);
    } else {
      response = await apiCreateUser(user);
      onUserCreate(response.data.user);
    }

    // Implement save user logic here
    hideModal();
    return MessageModal({ icon: "success", title: "Success", text: response.data.message });
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

async function viewUser(id) {
  try {
    LoadingModal();
    const response = await apiReadUser(id);
    Object.assign(user, response.data.user);
    showModal();
    return CloseModal();
  } catch (error) {
    return MessageModal({ icon: "error", title: "Error", text: error.response?.data?.message || error.message });
  }
}

async function removeUser(id) {
  Swal.fire({
    icon: "warning",
    title: "Delete User",
    text: "Are you sure you want to delete this user? This action cannot be undone.",
    showCancelButton: true,
    confirmButtonColor: "#d33",
    confirmButtonText: "Yes, delete it!",
  }).then(async (result) => {
    if (result.isConfirmed) {
      try {
        LoadingModal();
        const response = await apiDeleteUser(id);
        onUserDelete(id);
        return MessageModal({ icon: "success", title: "Success", text: response.data.message });
      } catch (error) {
        return MessageModal({ icon: "error", title: "Error", text: error.response?.data?.message || error.message });
      }
    }
  });
}

async function toggleUserStatus(id) {
  try {
    LoadingModal();
    const response = await apiToggleUserStatus(id);
    onUserUpdate(response.data.user);
    return MessageModal({ icon: "success", title: "Success", text: response.data.message });
  } catch (error) {
    return MessageModal({ icon: "error", title: "Error", text: error.response?.data?.message || error.message });
  }
}


function showModal() {
  $(userModal.value).modal("show");
}

function hideModal() {
  $(userModal.value).modal("hide");
}

function onUserCreate(user) {
  users.value = [user, ...users.value];
}
function onUserUpdate(user) {
  users.value = users.value.map((u) => (u.id === user.id ? user : u));
}
function onUserDelete(id) {
  users.value = users.value.filter((u) => u.id !== id);
}
</script>
